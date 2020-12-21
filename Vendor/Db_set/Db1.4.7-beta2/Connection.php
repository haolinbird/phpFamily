<?php
/**
 * Class Db
 * 
 * @author junl <junl@jumei.com>
 */

namespace Db;

use \PDO as PDO;

/**
 * Mysql only.
 * 
 * @todo More detailed SqlLog
 */
class Connection
{

    const REFRESH_BAD_CONFIG_INTERVAL   = 3;
    const PUNISH_BAD_CONFIG_DURATION    = 5;

    /**
     * 坏节点最多在共享内存生存周期(为了保持共享内存与业务配置同步)，超出之后直接剔除.
     */
    const MAX_BAD_CONFIG_TIME           = 3600;

    const READ = 'read';
    const WRITE = 'write';
    /**
     * instance of the Connection
     * @var \Db\Connection
     */
    protected static $instance;
    protected static $configs;
    protected static $badConfigs = array();
    protected static $lastFreshBadConfigsTime;

    /**
     * @var \Config\DataCenter
     */
    protected static $dataCenter;

    /**
     * 已经缓存的connection集合
     *
     * @var array
     */
    protected static $connections = array();

    /**
     * 默认使用的db配置,当创建连接时，没有传入配置名时，使用该配置名
     *
     * @var string
     */
    protected $defaultConfigName = 'default';

    /**
     * 当前链接的类型(读|写).
     * @var string
     */
    protected $type;

    /**
     * shared memory handler.
     *
     * @var \SharedMemory\Shm
     */
    protected $shm;

    /**
     * 进程中是否有事务存在.
     * @var bool
     */
    protected static $inTrans = 0;

    /**
     * 533 连接失败; 57P 服务端主动断开连接; HYT 连接超时; IM0 驱动错误; 080 连接出错;
     *
     * @var array 非查询操作错误代码头三位字符.
     */
    protected static $nonQueryErrorCodeHeaders = array('533' => null, '57P' => null, 'HYT' => null, 'IM0' => null, '080' => null);

    /**
     * Established connection.
     *
     * @var \Pdo
     */
    protected $connection;

    /**
     * If directly return query result from page caches. Use noCache() method to change this value.
     *
     * @var boolean
     */
    protected $withCache = true;

    /**
     * Cached results of queries in the same page/request.
     *
     * @var array
     */
    protected $cachedPageQueries = array();

    /**
     * If in global transaction. refers to {@link self::beginTransaction}
     *
     * @var Boolean
     */
    protected $inGlobalTransaction = false;
    protected $queryBeginTime;
    protected $queryEndTime;

    /*
     * 当前连接使用的配置，这里一定要注意，由于代码架构问题，
     * 只能在实际连接的connection对象的connectionCfg成员，而不能用于管理connection的对象的该成员（有点绕口，需要消化一下）。
     *
     * @var array()
     * */
    protected $connectionCfg = array();
    protected $allowRealExec = true;
    protected $allowSaveToNonExistingPk = false;
    // null: allow but warning.      false: not allowed and throw exception.     true: allowed
    protected $allowGuessConditionOperator = true;
    protected $autoCloseLastStatement = false;
    protected $lastSql;
    protected $lastStmt;
    protected $select_sql_top;
    protected $select_sql_columns;
    protected $select_sql_from_where;
    protected $select_sql_group_having;
    protected $select_sql_order_limit;
    protected $memoryUsageBeforeFetch;
    protected $memoryUsageAfterFetch;

    const UPDATE_NORMAL = 0;
    const UPDATE_IGNORE = 1;
    const INSERT_ON_DUPLICATE_UPDATE = 'ondup_update';
    const INSERT_ON_DUPLICATE_UPDATE_BUT_SKIP = 'ondup_exclude';
    const INSERT_ON_DUPLICATE_IGNORE = 'ondup_ignore';

    /**
     * Construct function.
     *
     * @param string  $dsn       DSN.
     * @param string  $user      UserName.
     * @param string  $password  Passwd.
     * @param array() $options  Options.
     *
     */
    protected function __construct($config = null)
    {
        $IPCKey = crc32(__FILE__);
        $this->shm = new \SharedMemory\Shm($IPCKey);

        $this->connectionCfg = $config;
        if (!static::$configs) {
            if (!class_exists('\Config\Db')) {
                $this->throwException('Neither configurations are set nor  Config\Db are found!');
            }
            static::$configs = (array) (new \Config\Db());
        }
        if (!self::$dataCenter) {
            if (! class_exists('\Config\DataCenter')) {
                throw new Exception('Neither configurations are set nor  Config\DataCenter are found!');
            }
            self::$dataCenter = new \Config\DataCenter();
        }
        if (!is_null($config)) {
            $this->connect($config);
        }
    }

    public function __destruct()
    {
        $this->lastStmt = null;
    }


    /**
     * 将配置中的DSN(负载均衡会配置多组ip:host、权重等)转成多条标准的pdo dsn格式.
     */
    public static function parseCfg($cfg)
    {
        return ConfigSchema::parseCfg($cfg);
    }

    /**
     * Set or get configs for the lib.
     *
     * @param string $config
     *
     * @return boolean
     */
    public static function config($config = null) {
        if (is_null($config)) {
            return static::$configs;
        }
        static::$configs = $config;
        return true;
    }

    /**
     * Get the current connection object. DO NOT heavily use this method in a single script.
     * this function will reconnect whether it's a good or bad connection.
     *
     * @return \PDO
     */
    public function getConn() {
        $this->reConnect();
        return $this->connection;
    }

    public function lastConnectionError() {
        return $this->connection->errorInfo();
    }

    /**
     * Close all connections.
     *
     * @return boolean
     */
    public function closeAll() {
        foreach (static::$connections as $type=>$connection) {
            foreach ($connection as $k => $v) {
                if ($v) {
                    $v->close();
                }
                $v = null;
                unset(static::$connections[$type][$k]);
            }
        }
        return true;
    }

    /**
     * Close current connection of this instance.
     */
    public function close() {
        // 这里用pdo底层的方法判断，有可能链接已经在网络层被断开了.
        if (is_object($this->connection) && $this->connection->inTransaction()) {
            // 有由于未知原因事务未被完成.
            trigger_error(new Exception('There is still active transaction'), E_USER_WARNING);
            $this->connection->rollBack();
        }
        $this->connection = null;
        $this->lastStmt = null;
        return true;
    }

    public function inTrans() {
        return $this::$inTrans;
    }

    public function TransLock() {
        self::$inTrans++;
    }

    public function TransUnlock() {
        if (self::$inTrans > 0) {
            self::$inTrans--;
        }
    }

    /**
     * Clear all connection query caches of a request page.
     */
    public function clearPageCaches() {
        foreach (static::$connections as $connection) {
            foreach ($connection as $link) {
                $link->destroyPageCache();
            }
        }
        return true;
    }

    /**
     * Clean the query caches of the current connection.
     *
     * @return array
     */
    public function destroyPageCache() {
        return $this->cachedPageQueries = array();
    }

    /**
     * get a instance of \Db\Connection
     * @return static
     */
    public static function instance() {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 设置连接使用的默认配置
     *
     * @param string $name
     *
     */
    public function setCfgName($name) {
        $this->defaultConfigName = $name;
    }

    /**
     *
     * @param string $name
     * @throws Exception
     * @return \Db\Connection
     * @todo connection name select
     */
    public function read($name = 'default') {
        if ($name == 'default' && func_num_args() == 0 && $this->defaultConfigName) {
            $name = $this->defaultConfigName;
        }
        $this->type = self::READ;

        try {
            $connection = $this->getConnection($name);
        } catch (\Exception $e) {
            throw new \Exception('No available ' . $this->type .' connections with msgs: ' . PHP_EOL . $e->getMessage());
        }

        static::$connections[$this->type][$connection->connectionCfg['alias']] = $connection;
        return $connection;
    }

    /**
     *
     * @param string $name
     * @throws Exception
     * @return \Db\Connection
     */
    public function write($name = 'default') {
        if ($name == 'default' && func_num_args() == 0 && $this->defaultConfigName) {
            $name = $this->defaultConfigName;
        }
        $this->type = self::WRITE;

        try {
            $connection = $this->getConnection($name);
        } catch (\Exception $e) {
            throw new \Exception('No available ' . $this->type .' connections with msgs: ' . PHP_EOL . $e->getMessage());
        }

        static::$connections[$this->type][$connection->connectionCfg['alias']] = $connection;
        return $connection;
    }

    /**
     * 是否是查询执行出错，而非连接出错，服务端异常等。
     *
     * @param $errorCode error code(ansi sql error code, 参考: php-src/ext/pdo/pdo_sqlstate.c)
     * @return bool
     */
    public function isQueryError($errorCode) {
        $codeHeader = substr($errorCode, 0, 3);
        return !isset(self::$nonQueryErrorCodeHeaders[$codeHeader]);
    }

    /**
     * 在查询失败后修复连接不可关闭的pdo bug.
     *
     * @param $errorCode error code(ansi sql error code, 参考: php-src/ext/pdo/pdo_sqlstate.c)
     * @return bool
     */
    protected function fixConnectionAfterQueryError($errorCode) {
        if ($this->isQueryError($errorCode)) {
            return (bool) $this->connection->query('select 1');
        }
        return true;
    }

    /**
     * 获取一个可用的连接.
     *
     * @param string $name 配置名字.
     *
     * @return \Db\Connection
     * @throws \Exception
     */
    protected function getConnection($name) {
        if (!isset(self::$configs[$this->type][$name])) {
            $this->throwException($this->type . ' configuration of "' . $name . '" is not found.', 42003);
        }

        $configs = self::$configs[$this->type][$name];

        $expMsgs = '';
        $connection = null;
        $db = $this->getFieldFromConfigs($configs, 'db');

        // 循环N次(N>=2)，保证每个节点都有机会被尝试到（包括坏节点，但优先尝试的都是正常节点）
        $nodesCount = count($configs) > 1 ? count($configs) : 2;
        // 这里尝试按照db配置的dsn规则(在Config/DataCenter.php中)获取
        $dcConfig = (array) self::$dataCenter->getDataCenterCfg($db, 0, $this->type);

        for ($i = 0; $i < $nodesCount; $i++) {
            // 如果节点挂了，要加到bad队列队尾中，恢复时（每5s或者无可用节点），每次从队首pop一个出来
            $cfg = $this->getConfig($configs, $dcConfig);

            // 复用链接
            if (!empty(static::$connections[$this->type][$cfg['alias']])) {
                return static::$connections[$this->type][$cfg['alias']];
            }

            try {
                $connection = new self($cfg);
                break;
            } catch (\Exception $e) {
                $expMsgs .= $e->getMessage() . PHP_EOL;

                // 2002:connection refused 或者 connection timeout
                // 非2002是其它问题（鉴权错误等）,这类错误就不用重试，也不用加黑名单，直接报错
                if ($e->getCode() != 2002) {
                    throw $e;
                }
                $this->kickConfig($cfg);
            }
        }

        if (!$connection) {
            throw new \Exception($expMsgs);
        }

        return $connection;
    }

    /**
     * 根据dbname，以及sharding索引idx，从config中，算出一个可以用的配置(优先使用db对应dsn规则，其次使用全局的dsn)
     *
     * @param array   $configs    原始的config集合.
     * @param string  $dcConfig   按照多中心配置的规则取出来的config
     *
     * @return array
     */
    protected function getConfig($configs, $dcConfigs, $rule = null) {

        // tmp config 可能被修改为dc config
        $tmpConfigs = $configs;
        $config = $configs[array_rand($configs)];
        $config['dc'] = 'default';

        if (!empty($dcConfigs)) {
            $tmpConfigs = $dcConfigs;
        }

        // 选取出来的config，只能使用到ip,port。dsn要在后续拼接，否在在sharding模式下，dsn管理起来比较混乱（主要是dbname）
        $tmpConfig = $this->findOneConfig($tmpConfigs);

        $config = array_merge($config, $tmpConfig);

        // 如果传入了rule，表明是sharding库，需要重新把db修改为带后缀的.
        if (!is_null($rule) && is_object($rule)) {
            $config['db'] = $rule->getDbName($config['db']);
        }

        // 赋值dc，如果是非sharding，这里应该是default
        $config['dc']       = $this->getFieldFromConfigs($tmpConfigs, 'dc', 'default');
        // 修正db相关字段
        $config['dsn']      = "mysql:host={$config['host']};port={$config['port']};dbname={$config['db']}";
        $config['alias']    = "{$config['dc']}:{$config['db']}";

        return $config;
    }

    /**
     * @param array  $configs Db组建定义的config数组.
     * @param string $field   要获取的字段
     * @param null $default   如果获取到字段值，默认的返回值.
     *
     * @return mixed|null
     */
    protected function getFieldFromConfigs($configs, $field, $default = null) {
        $config = array_shift($configs);
        if (!isset($config[$field])) {
            return $default;
        }
        return $config[$field];
    }
    /**
     *
     * @param string $dsn
     * @param string $user
     * @param string $password
     * @param array $options
     * @return \Db\Connection
     */
    public function connect($config) {
        if ($this->connection) {
            return $this->connection;
        }

        try {
            //set use buffered query
            $config['options'][\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = true;
            $this->connection = new \PDO($config['dsn'], $config['user'], $config['password'], $config['options']);
        } catch (\PDOException $ex) {
            $this->throwException($ex, $ex->getCode());
        }

        return $this;
    }

    protected function reConnect() {
        $this->close();
        return $this->connect($this->connectionCfg);
    }

    public function insert($table, $params, $onDup = null) {
        $columns = '';
        $values = '';
        foreach ($params as $column => $value) {
            $columns .= $this->quoteObj($column) . ',';
            $values .= is_null($value) ? "NULL," : ($this->quote($value) . ',');
        }

        $columns = substr($columns, 0, strlen($columns) - 1);
        $values = substr($values, 0, strlen($values) - 1);

        $sql_part_ignore = '';
        $sql_part_on_dup = '';

        if (empty($onDup)) {
            //do nothing, use the default behavior
        } else if ($onDup == self::INSERT_ON_DUPLICATE_IGNORE) {
            $sql_part_ignore = 'IGNORE';
        } else if ($onDup == self::INSERT_ON_DUPLICATE_UPDATE) {
            if (func_num_args() >= 4)
                $update_params = func_get_arg(3);
            else
                $update_params = $params;

            $updates = array();
            foreach ($update_params as $column => $value) {
                if (is_int($column))
                    $updates[] = "$value";
                else
                    $updates[] = $this->quoteObj($column) . "=" . (is_null($value) ? "null" : $this->quote($value));
            }
            if ($updates)
                $sql_part_on_dup = 'ON DUPLICATE KEY UPDATE ' . join(",", $updates);
        }
        else if ($onDup == self::INSERT_ON_DUPLICATE_UPDATE_BUT_SKIP) {
            $noUpdateColumnNames = func_get_arg(3);
            if (!is_array($noUpdateColumnNames))
                $this->throwException('invalid INSERT_ON_DUPLICATE_UPDATE_BUT_SKIP argument');

            $updates = array();
            foreach ($params as $column => $value) {
                if (!in_array($column, $noUpdateColumnNames)) {
                    $column = $this->quoteObj($column);
                    $updates[] = "$column=" . (is_null($value) ? "null" : $this->quote($value));
                }
            }
            $sql_part_on_dup = 'ON DUPLICATE KEY UPDATE ' . join(",", $updates);
        }

        $table = $this->quoteObj($table);
        $sql = "INSERT $sql_part_ignore INTO $table ($columns) VALUES ($values) $sql_part_on_dup";
        $ret = $this->exec($sql, false);

        if ($ret === false) {
            $this->release();
            return false;
        }

        $id = $this->connection->lastInsertId();
        $this->release();
        if ($id)
            return $id;

        return !!$ret;
    }

    public function replace($table, $params) {
        $columns = '';
        $values = '';
        foreach ($params as $column => $value) {
            $columns .= $this->quoteObj($column) . ',';
            $values .= is_null($value) ? "NULL," : ($this->quote($value) . ',');
        }

        $columns = substr($columns, 0, strlen($columns) - 1);
        $values = substr($values, 0, strlen($values) - 1);

        $table = $this->quoteObj($table);
        $sql = "REPLACE INTO $table ($columns) VALUES ($values)";
        $ret = $this->exec($sql);

        if ($ret === false) {
            $this->release();
            return false;
        }

        $id = $this->connection->lastInsertId();
        $this->release();
        if ($id)
            return $id;

        return $ret;
    }

    public function quote($data, $paramType = PDO::PARAM_STR) {
        if (is_array($data) || is_object($data)) {
            $return = array();
            foreach ($data as $k => $v) {
                $return [$k] = $this->quote($v);
            }
            return $return;
        } else {
            $data = $this->connection->quote($data, $paramType);
            if (false === $data)
                $data = "''";
            return $data;
        }
    }

    /**
     * quote object names.<br />
     * e.g. as mysql, a table name "user" will be quoted to "`user`", column name "t1.cl1 as haha" will be quoted to "`t1`.`cl1` AS `haha`"
     *
     * @param string|array $objName
     * @todo only mysql is currently supported.
     * @return mixed
     */
    public function quoteObj($objName) {
        if (is_array($objName)) {
            $return = array();
            foreach ($objName as $k => $v) {
                $return[] = $this->quoteObj($v);
            }
            return $return;
        } else {
            $v = trim($objName);
            $v = str_replace('`', '', $v);
            $v = preg_replace('# +AS +| +#i', ' ', $v);
            $v = explode(' ', $v);
            foreach ($v as $k_1 => $v_1) {
                $v_1 = trim($v_1);
                if ($v_1 == '') {
                    unset($v[$k_1]);
                    continue;
                }
                if (strpos($v_1, '.')) {
                    $v_1 = explode('.', $v_1);
                    foreach ($v_1 as $k_2 => $v_2) {
                        $v_1[$k_2] = '`' . trim($v_2) . '`';
                    }
                    $v[$k_1] = implode('.', $v_1);
                } else {
                    $v[$k_1] = '`' . $v_1 . '`';
                }
            }
            $v = implode(' AS ', $v);
            return $v;
        }
    }

    public function throwException($message = null, $code = null, $previous = null) {
        if (is_object($message)) {
            $message = $message->getMessage();
        }
        $message .= '(dsn: '. (isset($this->connectionCfg['dsn']) ? $this->connectionCfg['dsn']: $this->connectionCfg['host']). ')';

        if ($this->connection) {
            $errorInfo = $this->connection->errorInfo();
            $ex = new Exception($message . ' (DriverCode:' . $errorInfo[1] . ')' . $errorInfo [2], $code, $previous);
        } else {
            $ex = new Exception($message, $code);
        }

        $this->release();
        throw $ex;
    }

    /**
     * Indicates the next query do not use page caches.
     *
     * @return self
     */
    public function noCache() {
        $this->withCache = false;
        return $this;
    }

    /**
     * By default, results (from select statement) are to be get from page caches. Please use the following syntax to get results from database in every query.
     * E.G.<pre>
     * DbConnection::instance()->read()->noCache()->query('....');
     * </pre>
     * @param string $sql
     * @return PDOStatement
     * @see PDO::query()
     * @throws \Db\Exception
     */
    public function query($sql = null) {
        static $retryCount = 0;
        if(!is_object($this->connection)) {
            throw new Exception('connection set to null because of unknown problem!');
        }
        $withCache = false; //$this->withCache;
        //reset withCache to true in every query, so the next query will use cache again.
        $this->withCache = true;

        if (empty($sql)) {
            $this->lastSql = $this->getSelectSql();  // 不需要trim，拼接函数保证以SELECT开头
        } else {
            $this->lastSql = trim($this->buildSql($sql));
        }
        $sqlCmd = strtoupper(substr($this->lastSql, 0, 6));
        if (in_array($sqlCmd, array('UPDATE', 'DELETE')) && stripos($this->lastSql, 'where') === false) {
            $this->throwException('no WHERE condition in SQL(UPDATE, DELETE) to be executed! please make sure it\'s safe', 42005);
        }

        if ($this->allowRealExec || $sqlCmd == 'SELECT') {
            $cacheKey = md5($this->lastSql);
            if ($withCache && isset($this->cachedPageQueries[$cacheKey])) {
                return $this->cachedPageQueries[$cacheKey];
            }
            $this->queryBeginTime = microtime(true);
            $trace = $this::getExternalCaller();
            \MNLogger\TraceLogger::instance('trace')->MYSQL_CS($this->connectionCfg['dsn'], $trace['class'] . '::' . $trace['method'], $this->lastSql, $trace['file'] . ':' . $trace['line']);
            $this->memoryUsageBeforeFetch = memory_get_usage();
            try {// 连接池在查询失败时也会抛异常,而pdo在默认情况下会返回false.
                $this->lastStmt = $this->connection->query($this->lastSql);
            } catch (\Exception $queryEx) {
                $this->lastStmt = false;
            }
        } else {
            $this->lastStmt = true;
        }
        $this->queryEndTime = microtime(true);
        $this->logQuery($this->lastSql);
        if (false === $this->lastStmt) {
            // connection broken, retry one time
            $errorInfo = $this->connection->errorInfo();
            if ($retryCount < 1 && $this->needConfirmConnection()) {
                $connectionLost = 2006 == $errorInfo[1];
                if ($connectionLost) {
                    $retryCount += 1;
                    $this->reConnect();
                    $result = $this->query($sql);
                    $retryCount = 0;
                    return $result;
                }
            } else {
                $this->fixConnectionAfterQueryError($errorInfo[0]);
            }
            $retryCount = 0;
            $errorMsg = isset($queryEx) ? $queryEx->getMessage() : $errorInfo[2];
            $this->throwException('Query failure.SQL:' . $this->lastSql . '. (' . $errorMsg . ')', 42004);
        }

        if ($withCache && isset($cacheKey)) {
            $this->cachedPageQueries[$cacheKey] = $this->lastStmt;
        }

        if ($this->lastStmt instanceof \PDOStatement) {
            return $this->lastStmt;
        }
        $this->throwException('Unexpected type of query statement. Expecting "PDOStatement" but "' . var_export($this->lastStmt, true) . " presents. SQL: " . $this->lastSql, 42010);

    }

    /**
     * @param string $sql
     * @param bool $releaseConnection 执行完是否立即释放连接.如果立即释放,则可能会影响到进程内后续的连接判断。如: 当要获取到lastInsertId时则应当保持连接，否则有可能是获取到其它查询的返回值。
     * @see PDO::exec()
     * @return mixed
     */
    public function exec($sql = null, $releaseConnection = true) {
        static $retryCount = 0;
        $sqlCmd = strtoupper(substr($sql, 0, 6));
        if (in_array($sqlCmd, array('UPDATE', 'DELETE')) && stripos($sql, 'where') === false) {
            $this->throwException(new Exception('no WHERE condition in SQL(UPDATE, DELETE) to be executed! please make sure it\'s safe', 42005));
        }
        $this->lastSql = $sql;
        $this->queryBeginTime = microtime(true);
        $trace = $this::getExternalCaller();
        \MNLogger\TraceLogger::instance('trace')->MYSQL_CS($this->connectionCfg['dsn'], $trace['class'] . '::' . $trace['method'], $this->lastSql, $trace['file'] . ':' . $trace['line']);
        if ($this->allowRealExec) {
            try {
                $re = $this->connection->exec($sql);
            } catch (\Exception $queryEx) {
                $re = false;
            }
        } else {
            $re = true;
        }
        \MNLogger\TraceLogger::instance('trace')->MYSQL_CR($re === false ? 'EXCEPTION' : 'SUCCESS', 0);
        $this->queryEndTime = microtime(true);
        $this->logQuery($sql);
        if (false === $re) {
            // connection broken, retry one time
            if ($retryCount < 1 && $this->needConfirmConnection()) {
                $errorInfo = $this->connection->errorInfo();
                $connectionLost = 2006 == $errorInfo[1];
                if ($connectionLost) {
                    $retryCount += 1;
                    $this->reConnect();
                    $re = $this->exec($sql);
                    $retryCount = 0;
                    return $re;
                }
            }
            $retryCount = 0;
            $errorMsg = isset($queryEx) ? $queryEx->getMessage() : $errorInfo[2];
            $this->throwException('Query failure.SQL:' . $sql . '.(' . $errorMsg . ') ', 42004);
        }
        if ($releaseConnection)
            $this->release();
        return $re;
    }

    /**
     * @param boolean $global If use transaction for all queries.
     *                        If it is set to true, you have to set it to true when "commit" or "rollback" to make all queries effective within it.
     *                        Once in global transaction, any nested in transactions are disabled, and will be included within the global transaction.
     *                        Notice: glboal transaction can not be netsted within any other transactions, it should be stated from the outmost level.
     * @return bool
     */
    public function beginTransaction($global = false) {
        if ($global && $this->connection->inTransaction()) {// Allow one global transaction only.
            $this->connection->rollBack();
            $this->TransUnlock();
            $this->throwException('You cannot begin global transaction at this moment. There are active transactions or GlobalTransaction has already started !', 42101);
        } else if (!$global && $this->inGlobalTransaction) {// If global transaction started, then ignore all normal transactions(just not start them).
            return true;
        } else if ($global) {// Start global transaction.
            $this->inGlobalTransaction = true;
        }

        if (!$this->connection->inTransaction()) {
            // Re-connect before begin a transaction. If inTransaction then skip this step to avoid breaking nested transactions.
            $this->reConnect();
        }
        if ($this->connection->beginTransaction()) {
            $this->TransLock();
            return true;
        } else {
            return false;
        }//在事务里面持有其他连接，这个连接也不释放（即:一次release这个进程持有的所有连接都释放了）
    }

    /**
     *
     * @param boolean $global if commit the global transaction.
     *
     * @return boolean
     */
    public function commit($global = false) {
        if ($this->inGlobalTransaction && !$global) {// Prevent committing a global transaction unexpectedly in a normal transaction.
            return true;
        } else {// Ready to commit the global transaction.
            $this->inGlobalTransaction = false;
        }
        $ret = $this->connection->commit();
        $this->TransUnlock();
        if (!$ret) {
            $errorInfo = $this->connection->errorCode();
            $this->fixConnectionAfterQueryError($errorInfo[0]);
            trigger_error(new Exception($errorInfo[2]), E_USER_WARNING);
        }
        $this->release();
        return $ret;
    }

    /**
     *
     * @param boolean $global if rollback the global transaction.
     *
     * @return boolean
     */
    public function rollback($global = false) {
        if ($this->inGlobalTransaction && !$global) {// Prevent rollback a global transaction unexpectedly in a normal transaction.
            return true;
        } else {// Ready to rollback the global transaction.
            $this->inGlobalTransaction = false;
        }
        $ret = $this->connection->rollBack();
        $this->TransUnlock();
        $this->release();
        return $ret;
    }

    /**
     * Check if confirmation of connection is needed by setting "confirm_link" of configuration  to true.
     * This is mostly used in Daemons which use long connections.
     *
     * @return boolean
     */
    public function needConfirmConnection() {
        if (isset($this->connectionCfg['confirm_link']) && $this->connectionCfg['confirm_link'] !== false) {
            return true;
        }
        return false;
    }

    public function buildWhere($condition = array(), $logic = 'AND') {
        $s = $this->buildCondition($condition, $logic);
        if ($s)
            $s = ' WHERE ' . $s;
        return $s;
    }

    public function buildCondition($condition = array(), $logic = 'AND') {
        if (!is_array($condition)) {
            if (is_string($condition)) {
                //forbid to use a CONSTANT as condition
                $count = preg_match('#\>|\<|\=| #', $condition, $logic);
                if (!$count) {
                    $this->throwException('bad sql condition: must be a valid sql condition');
                }
                $condition = explode($logic[0], $condition);
                if (!is_numeric($condition[0])) {
                    $condition[0] = $this->quoteObj($condition[0]);
                }
                $condition = implode($logic[0], $condition);
                return $condition;
            }

            $this->throwException('bad sql condition: ' . gettype($condition));
        }
        $logic = strtoupper($logic);
        $content = null;
        foreach ($condition as $k => $v) {
            $v_str = null;
            $v_connect = '';

            if (is_int($k)) {
                //default logic is always 'AND'
                if ($content)
                    $content .= $logic . ' (' . $this->buildCondition($v) . ') ';
                else
                    $content = '(' . $this->buildCondition($v) . ') ';
                continue;
            }

            $k = trim($k);

            $maybe_logic = strtoupper($k);
            if (in_array($maybe_logic, array('AND', 'OR'))) {
                if ($content)
                    $content .= $logic . ' (' . $this->buildCondition($v, $maybe_logic) . ') ';
                else
                    $content = '(' . $this->buildCondition($v, $maybe_logic) . ') ';
                continue;
            }

            $k_upper = strtoupper($k);
            //the order is important, longer fist, to make the first break correct.
            $maybe_connectors = array('>=', '<=', '<>', '!=', '>', '<', '=',
                ' NOT BETWEEN', ' BETWEEN', 'NOT LIKE', ' LIKE', ' IS NOT', ' NOT IN', ' IS', ' IN');
            foreach ($maybe_connectors as $maybe_connector) {
                $l = strlen($maybe_connector);
                if (substr($k_upper, -$l) == $maybe_connector) {
                    $k = trim(substr($k, 0, -$l));
                    $v_connect = $maybe_connector;
                    break;
                }
            }
            if (is_null($v)) {
                $v_str = ' NULL';
                if ($v_connect == '') {
                    $v_connect = 'IS';
                }
            } else if (is_array($v)) {
                if ($v_connect == ' BETWEEN') {
                    $v_str = $this->quote($v[0]) . ' AND ' . $this->quote($v[1]);
                } else if (is_array($v) && !empty($v)) {
                    // 'key' => array(v1, v2)
                    $v_str = null;
                    foreach ($v AS $one) {
                        if (is_array($one)) {
                            // (a,b) in ( (c, d), (e, f) )
                            $sub_items = '';
                            foreach ($one as $sub_value) {
                                $sub_items .= ',' . $this->quote($sub_value);
                            }
                            $v_str .= ',(' . substr($sub_items, 1) . ')';
                        } else {
                            $v_str .= ',' . $this->quote($one);
                        }
                    }
                    $v_str = '(' . substr($v_str, 1) . ')';
                    if (empty($v_connect)) {
                        if ($this->allowGuessConditionOperator === null || $this->allowGuessConditionOperator === true) {
                            if ($this->allowGuessConditionOperator === null)
                                \Log\Handler::instance()->log("guessing condition operator is not allowed: use '$k IN'=>array(...)", array('type' => E_WARNING));

                            $v_connect = 'IN';
                        } else
                            $this->throwException("guessing condition operator is not allowed: use '$k IN'=>array(...)");
                    }
                }
                else if (empty($v)) {
                    // 'key' => array()
                    $v_str = $k;
                    $v_connect = '<>';
                }
            } else {
                $v_str = $this->quote($v);
            }

            if (empty($v_connect))
                $v_connect = '=';

            $quoted_k = $this->quoteObj($k);
            if ($content)
                $content .= " $logic ( $quoted_k $v_connect $v_str ) ";
            else
                $content = " ($quoted_k $v_connect $v_str) ";
        }

        return $content;
    }

    protected function buildSql($sql) {
        $realSql = '';
        if (is_string($sql))
            return $sql;
        if (is_array($sql)) {
            $realSql = '';
            foreach ($sql as $k => $v) {
                if (is_int($k))
                    $realSql .= $v . " ";
                else if ($k == 'where' || $k == 'WHERE')
                    $realSql .= " WHERE " . $this->buildCondition($v) . " ";
                else
                    \Log\Handler::instance()->log('unknown key("' . $k . '") in sql.');
            }
        }
        return $realSql;
    }

    public function setAllowRealExec($v) {
        $this->allowRealExec = $v;
    }

    /**
     * 只有在主键不是自增id的时候，调用saveWithoutNull的时候才需要allowSaveToNonExistingPk
     */
    public function setAllowSaveToNonExistingPk($v) {
        $this->allowSaveToNonExistingPk = $v;
    }

    /**
     * 是否允许条件构造的时候，自动推导操作符。例如：是否允许 'a'=>array(1,2) 推导为  a IN (1,2)
     * 如果允许，则对输入数据进行过滤，确保需要提交一个数据的地方，不要被提交上一个数组。
     *
     * @param $v   null: allow but log a warning.      false: not allowed and throw exception.     true: allowed
     */
    public function setAllowGuessConditionOperator($v) {
        $this->allowGuessConditionOperator = $v;
    }

    public function getLastSql() {
        return $this->lastSql;
    }

    public function getSelectSql() {
        return "SELECT {$this->select_sql_top} {$this->select_sql_columns} {$this->select_sql_from_where} {$this->select_sql_group_having} {$this->select_sql_order_limit}";
    }

    /**
     * @param string $columns
     * @return \Db\Connection
     */
    public function select($columns = '*') {
        $this->select_sql_top = '';
        $this->select_sql_columns = $columns;
        $this->select_sql_from_where = '';
        $this->select_sql_group_having = '';
        $this->select_sql_order_limit = '';
        return $this;
    }

    /**
     * @param $n
     * @return \Db\Connection
     */
    public function top($n) {
        $n = intval($n);
        $this->select_sql_top = "TOP $n";
    }

    /**
     * @param $table
     * @return \Db\Connection
     */
    public function from($table) {
        $table = $this->quoteObj($table);
        $this->select_sql_from_where .= " FROM $table ";
        return $this;
    }

    /**
     * @param array|string $cond
     * @return \Db\Connection
     */
    public function where($cond = array()) {
        $cond = $this->buildCondition($cond);
        $this->select_sql_from_where .= $cond ? " WHERE $cond " : '';
        return $this;
    }

    protected function joinInternal($join, $table, $cond) {
        $table = $this->quoteObj($table);
        $this->select_sql_from_where .= " $join $table ";
        if (is_string($cond) && (strpos($cond, '=') === false && strpos($cond, '<') === false && strpos($cond, '>') === false)) {
            $column = $this->quoteObj($cond);
            $this->select_sql_from_where .= " USING ($column) ";
        } else {
            $cond = $this->buildCondition($cond);
            $this->select_sql_from_where .= " ON $cond ";
        }
        return $this;
    }

    /**
     * @param $table
     * @param $cond
     * @return \Db\Connection
     */
    public function join($table, $cond) {
        return $this->joinInternal('JOIN', $table, $cond);
    }

    /**
     * @param $table
     * @param $cond
     * @return \Db\Connection
     */
    public function leftJoin($table, $cond) {
        return $this->joinInternal('LEFT JOIN', $table, $cond);
    }

    /**
     * @param $table
     * @param $cond
     * @return \Db\Connection
     */
    public function rightJoin($table, $cond) {
        return $this->joinInternal('RIGHT JOIN', $table, $cond);
    }

    public function update($table, $params, $cond, $options = 0, $order_by_limit = '') {
        if (empty($params))
            return false;

        if (is_string($params)) {
            $update_str = $params;
        } else {
            $update_str = '';

            foreach ($params as $column => $value) {
                if (is_int($column)) {
                    $update_str .= "$value,";
                } else {
                    $column = $this->quoteObj($column);
                    $value = is_null($value) ? 'NULL' : $this->quote($value);
                    $update_str .= "$column=$value,";
                }
            }
            $update_str = substr($update_str, 0, strlen($update_str) - 1);
        }

        $table = $this->quoteObj($table);
        if (is_numeric($cond))
            $cond = $this->quoteObj('id') . "='$cond'";
        else
            $cond = $this->buildCondition($cond);
        $sql = "UPDATE ";
        if ($options == self::UPDATE_IGNORE)
            $sql .= " IGNORE ";
        $sql .= " $table SET $update_str WHERE $cond $order_by_limit";
        $ret = $this->exec($sql);
        return $ret;
    }

    public function delete($table, $cond) {
        $table = $this->quoteObj($table);
        $cond = $this->buildCondition($cond);
        $sql = "DELETE FROM {$table} WHERE $cond";
        $ret = $this->exec($sql);
        return $ret;
    }

    /**
     * @param $group
     * @return \Db\Connection
     */
    public function group($group) {
        $this->select_sql_group_having .= " GROUP BY $group ";
        return $this;
    }

    /**
     * @param $having
     * @return \Db\Connection
     */
    public function having($cond) {
        $cond = $this->buildCondition($cond);
        $this->select_sql_group_having .= " HAVING $cond ";
        return $this;
    }

    /**
     * @param $order
     * @return \Db\Connection
     */
    public function order($order) {
        $this->select_sql_order_limit .= " ORDER BY $order ";
        return $this;
    }

    public function isDriver($name) {
        $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (is_array($name))
            return in_array($driver, $name);
        return $driver == $name;
    }

    public function queryScalar($sql = null, $default = null) {
        $stmt = $this->query($sql);
        $v = $stmt->fetchColumn(0);
        $this->release();
        $this->memoryUsageAfterFetch = memory_get_usage();
        \MNLogger\TraceLogger::instance('trace')->MYSQL_CR($this->lastStmt ? 'SUCCESS' : 'EXCEPTION', $this->memoryUsageAfterFetch - $this->memoryUsageBeforeFetch);
        if ($v !== false)
            return $v;
        return $default;
    }

    public function querySimple($sql = null, $default = null) {
        return $this->queryScalar($sql, $default);
    }

    /**
     * @param string|null $sql
     * @return array
     */
    public function queryRow($sql = null) {
        $stmt = $this->query($sql);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->release();
        $this->memoryUsageAfterFetch = memory_get_usage();
        \MNLogger\TraceLogger::instance('trace')->MYSQL_CR($this->lastStmt ? 'SUCCESS' : 'EXCEPTION', $this->memoryUsageAfterFetch - $this->memoryUsageBeforeFetch);
        return $data;
    }

    /**
     * @param string|null $sql
     * @return array
     */
    public function queryColumn($sql = null) {
        $stmt = $this->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->release();
        $this->memoryUsageAfterFetch = memory_get_usage();
        \MNLogger\TraceLogger::instance('trace')->MYSQL_CR($this->lastStmt ? 'SUCCESS' : 'EXCEPTION', $this->memoryUsageAfterFetch - $this->memoryUsageBeforeFetch);
        return $data;
    }

    /**
     * @param string|null $sql
     * @param string $key
     * @return array
     */
    public function queryAllAssocKey($sql, $key) {
        $rows = array();
        $stmt = $this->query($sql);
        if ($stmt) {
            while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false)
                $rows[$row[$key]] = $row;
        }
        $this->release();
        $this->memoryUsageAfterFetch = memory_get_usage();
        \MNLogger\TraceLogger::instance('trace')->MYSQL_CR($this->lastStmt ? 'SUCCESS' : 'EXCEPTION', $this->memoryUsageAfterFetch - $this->memoryUsageBeforeFetch);
        return $rows;
    }

    /**
     * @param string|null $sql
     * @param string $key
     * @return array
     */
    public function queryAll($sql = null, $key = '') {
        if ($key)
            return $this->queryAllAssocKey($sql, $key);

        $stmt = $this->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->release();
        $this->memoryUsageAfterFetch = memory_get_usage();
        \MNLogger\TraceLogger::instance('trace')->MYSQL_CR($this->lastStmt ? 'SUCCESS' : 'EXCEPTION', $this->memoryUsageAfterFetch - $this->memoryUsageBeforeFetch);
        return $data;
    }

    public function find($table, $cond, $order = '') {
        if (is_numeric($cond))
            $cond = array('id' => "$cond");
        $table = $this->quoteObj($table);
        $where = $this->buildWhere($cond);

        if ($order && strncasecmp($order, 'ORDER BY', 8) != 0)
            $order = 'ORDER BY ' . $order;
        $sql = "SELECT * FROM $table $where $order";
        return $this->queryRow($sql);
    }

    public function findAll($table, $cond, $order = '') {
        $table = $this->quoteObj($table);
        $where = $this->buildWhere($cond);
        if ($order && strncasecmp($order, 'ORDER BY', 8) != 0)
            $order = 'ORDER BY ' . $order;
        $sql = "SELECT * FROM $table $where $order";
        return $this->queryAll($sql);
    }

    public function count($table, $cond, $columns = '*') {
        $table = $this->quoteObj($table);
        $where = $this->buildWhere($cond);
        $sql = "SELECT COUNT($columns) FROM $table $where";
        return $this->querySimple($sql);
    }

    //general implemention
    public function exists($table, $cond) {
        $table = $this->quoteObj($table);
        $where = $this->buildWhere($cond);
        $sql = "SELECT 1 FROM $table $where LIMIT 1";
        return !!$this->querySimple($sql);
    }

    /**
     * @param $a
     * @param null $b
     * @return \Db\Connection
     */
    public function limit($a, $b = null) {
        if (is_null($b)) {
            $a = intval($a);
            $this->select_sql_order_limit .= " LIMIT $a ";
        } else {
            $a = intval($a);
            $b = intval($b);
            $this->select_sql_order_limit .= " LIMIT $a, $b ";
        }
        return $this;
    }

    public function logQuery($sql) {
        if (isset(static::$configs['DEBUG']) && static::$configs['DEBUG'] && isset(static::$configs['DEBUG_LEVEL'])) {
            $logString = 'Begin:' . date('Y-m-d H:i:s', $this->queryBeginTime) . "\n";
	    $logString .= 'SQL: ' . $sql . "\n";
            switch (static::$configs['DEBUG_LEVEL']) {
                case 2 :
                    //looks ugly
                    $tempE = new \Exception ();
                    $logString .= "Trace:\n" . $tempE->getTraceAsString() . "\n";
                    continue;
                case 1 :
                default :
                    continue;
            }
            $logString .= 'End:' . date('Y-m-d H:i:s', $this->queryEndTime) . '  Total:' . sprintf('%.3f', ($this->queryEndTime - $this->queryBeginTime) * 1000) . 'ms' . "\n";
            \Log\Handler::instance('db')->log($logString);
        }
    }

    public static function getExternalCaller() {
        $trace = debug_backtrace(false);
        $caller = array('class' => '', 'method' => '', 'file' => '', 'line' => '');
        $k = 0;
        foreach ($trace as $k => $line) {
            if (isset($line['class']) && strpos($line['class'], __NAMESPACE__) === 0) {
                continue;
            } else if (isset($line['class'])) {
                $caller['class'] = $line['class'];
                $caller['method'] = $line['function'];
            } else if (isset($line['function'])) {
                $caller['method'] = $line['function'];
            } else {
                $caller['class'] = 'main';
            }
            break;
        }
        if (empty($caller['method'])) {
            $caller['method'] = 'main';
        }
        while (!isset($line['file']) && $k > 0) {// 可能在eval或者call_user_func里调用的。
            $line = $trace[--$k];
        }
        $caller['file'] = $line['file'];
        $caller['line'] = $line['line'];
        return $caller;
    }

    /**
     *
     * @param int $pageNumber
     * @param int $rowsPerPage
     * @param string $countColumnsOrSqlCount
     * @param string $sqlForQueryWithoutLimit
     * @return JMDbConnectionPageResult
     */
    public function getPageResultByNumber($pageNumber, $rowsPerPage, $countColumnsOrSqlCount = '*', $sqlForQueryWithoutLimit = null, $primaryKey = '', $sort = 'ASC') {
        if ($pageNumber <= 0)
            $pageNumber = 1;
        return $this->getPageResultByIndex($pageNumber - 1, $rowsPerPage, $countColumnsOrSqlCount, $sqlForQueryWithoutLimit, $primaryKey, $sort);
    }

    /**
     * 说明：对于有GROUP BY id的查询，需要用 COUNT(DISTINCT id)获取结果集总数，也就是说需要传递第三个参数
     * @param int $pageIndex
     * @param int $rowsPerPage
     * @param string $countColumnsOrSqlForCount
     * @param string $sqlForQueryWithoutLimit
     * @return JMDbConnectionPageResult
     */
    public function getPageResultByIndex($pageIndex, $rowsPerPage, $countColumnsOrSqlForCount = '*', $sqlForQueryWithoutLimit = null, $primaryKey = '', $sort = 'ASC') {
        if ($rowsPerPage < 1)
            $rowsPerPage = 1;
        $o = new JMDbConnectionPageResult();
        if ($pageIndex <= 0)
            $pageIndex = 0;

        if ($sqlForQueryWithoutLimit) {
            $sqlForCount = $countColumnsOrSqlForCount;
            $o->rowCount = intval($this->querySimple($sqlForCount));
            $sqlForQuery = $sqlForQueryWithoutLimit . " LIMIT " . ($pageIndex * $rowsPerPage) . ", " . intval($rowsPerPage);
        } else { // no $sqlForCount, use the chain sql mode
            $sqlForCount = "SELECT COUNT($countColumnsOrSqlForCount) {$this->select_sql_from_where}"; // 说明：对于有GROUP BY id的查询，需要用 COUNT(DISTINCT id)获取结果集总数
            $o->rowCount = intval($this->querySimple($sqlForCount));
            if (empty($primaryKey)) {
                $sqlForQuery = "SELECT {$this->select_sql_columns} {$this->select_sql_from_where} {$this->select_sql_group_having} {$this->select_sql_order_limit} LIMIT " . ($pageIndex * $rowsPerPage) . ", " . intval($rowsPerPage);
            } else {

                $select_sql_from_where = $this->select_sql_from_where;
                if (!stristr($this->select_sql_from_where, 'where')) {
                    $select_sql_from_where .= ' WHERE 1=1';
                }
                $op = " >= ";
                if (strtolower($sort) == 'desc') {
                    $op = " <= ";
                }

                $select_sql_order_limit = $this->select_sql_order_limit;

                $limitRowsNumber = $pageIndex * $rowsPerPage;
                if ($limitRowsNumber >= $o->rowCount)
                    $limitRowsNumber = $o->rowCount - 1;
                if ($limitRowsNumber < 0)
                    $limitRowsNumber = 0;
                if (($o->rowCount / 2) < $limitRowsNumber && stristr($select_sql_order_limit, 'order')) {

                    if (stristr($select_sql_order_limit, 'desc')) {
                        $select_sql_order_limit = str_ireplace('desc', 'ASC', $select_sql_order_limit);
                    } else if (stristr($select_sql_order_limit, 'asc')) {
                        $select_sql_order_limit = str_ireplace('asc', 'DESC', $select_sql_order_limit);
                    }

                    $select_sql_order_limit .= "LIMIT " . ($o->rowCount - $limitRowsNumber - 1) . ", 1";
                } else {
                    $select_sql_order_limit .= "LIMIT " . ($limitRowsNumber) . ", 1";
                }

                $select_sql_from_where .= " AND " . $primaryKey . "{$op} (SELECT {$primaryKey} {$this->select_sql_from_where} {$this->select_sql_group_having} {$select_sql_order_limit})";
                $sqlForQuery = "SELECT {$this->select_sql_columns} {$select_sql_from_where} {$this->select_sql_group_having} {$this->select_sql_order_limit} LIMIT " . intval($rowsPerPage);
            }
        }

        $o->pageCount = ceil($o->rowCount / $rowsPerPage);
        $o->rows = $this->queryAll($sqlForQuery);
        $o->pageIndex = $pageIndex;
        $o->pageNumber = $pageIndex + 1;
        $o->rowsPerPage = $rowsPerPage;
        return $o;
    }

    public function release($force = false) {
    }


    /**
     * 按权重随机數組中的一個.
     *
     * @param array $arr         备选目标
     * @param bool $unsetChoosed 被选中后，是否从备选目标中清掉该节点
     *
     * @return array
     */
    public function getOneNode(array &$arr, $unsetChoosed = false){
        if (count($arr) == 0 ) {
            return array();
        }

        $weight = array();
        $count = 0;

        foreach( $arr as $k => $v){
            if (!isset($v['weight'])) $v['weight'] = 0;
            $count += (int)$v['weight'];
            $weight[$k] = $count;
        }

        $one = mt_rand(1,$count);
        foreach( $weight as $k => $v){
            if ( $one <= $v){
                $ret = $arr[$k];
                if ($unsetChoosed) {
                    unset($arr[$k]);
                }
                return $ret;
            }
        }
        return array();
    }

    /**
     * 剔除一个无法链接的epg节点
     *
     * @param array $config 需要剔除的配置.
     *
     * @return void
     */
    protected function kickConfig($config) {
        $this->shm->lockAndAttach();
        $badConfigs = @$this->shm->getArrVar();

        // aovid push same config
        if (!$this->isInConfig($config, $badConfigs)) {
            // push到队尾
            $badConfigs[] = array(
                'host'      => $config['host'],
                'port'      => $config['port'],
                'kick_time' => time(),
            );

        }
        $this->shm->putVar($badConfigs);
        $this->shm->unlockAndDettach();

        self::$badConfigs = $badConfigs;
    }

    protected function recoverOneConfig($configs) {
        //没有坏节点，不管了
        if (empty(self::$badConfigs)) {
            return false;
        }

        // 从队首拿出一个节点，（如果仍然是坏的，重试后会再放进队尾）
        $now = time();
        $recoverConfig = array();
        // 是否需要刷新共享内存（为了提高效率，大部分请求并不需要刷新共享内存，而刷新shm需要加锁，对性能影响很大）
        $bNeedFreshShm = false;
        foreach (self::$badConfigs as $key => $badconfig) {
            $timeInterval =  $now - $badconfig['kick_time'];

            // 超过一定时限的黑名单，直接删掉.
            if ($timeInterval >= self::MAX_BAD_CONFIG_TIME) {
                unset(self::$badConfigs[$key]);
                $bNeedFreshShm = true;
                continue;
            }

            if ($timeInterval >= self::PUNISH_BAD_CONFIG_DURATION) {
                // 如果dsn已经不在目标中, 有两种可能 1.这个坏节点不属于这个config集合，2.这个坏节点已经被除名了.两种case都不用处理.
                if (!$this->isInConfig($badconfig, $configs)) {
                    continue;
                }
                unset(self::$badConfigs[$key]);
                $bNeedFreshShm = true;

                $recoverConfig = $badconfig;
                break;
            }
        }

        if ($bNeedFreshShm) {
            // 刷新共享内存
            $this->shm->lockAndAttach();
            $this->shm->putVar(self::$badConfigs);
            $this->shm->unlockAndDettach();
        }

        return $recoverConfig;
    }

    protected function isInConfig($needle, $haystack) {
        foreach ($haystack as $item) {
            if ($item['host'] == $needle['host'] && $item['port'] == $needle['port']) {
                return true;
            }
        }
        return false;
    }


    protected function isBadConfig($config) {
        return $this->isInConfig($config, self::$badConfigs);
    }


    /**
     *
     */
    protected function tryFreshBadConfigs() {
        $ts =time();
        if ( $ts - self::$lastFreshBadConfigsTime >= self::REFRESH_BAD_CONFIG_INTERVAL) {
            $this->shm->lockAndAttach();
            self::$badConfigs = @$this->shm->getArrVar();
            $this->shm->unlockAndDettach();
            self::$lastFreshBadConfigsTime = $ts;

        }
    }

    /**
     * 找到一个可以可以用的dsn，在这之前请确认以及个配置好了共享内存的ipcKey以及seqKey.
     *
     * @param array $config 目前使用的配置.
     *
     * @return array|mixed
     */
    protected function findOneConfig($configs) {
        // 首先尝试刷新从共享内存同步一下黑名单.
        $this->tryFreshBadConfigs();

        // 每5s尝试恢复一个坏节点。
        $config = $this->recoverOneConfig($configs);
        if (!empty($config)) {
            return $config;
        }

        // 没有可以恢复的节奏，找到一个正常的节点
        $configsRelica = $configs;
        while (!empty($configsRelica)) {
            $config = $this->getOneNode($configsRelica, true);
            if (!$this->isBadConfig($config)) {
                return $config;
            }
        }

        $randConfig = $configs[array_rand($configs)];

        // 如果都坏了，好吧，随便找一个dsn
        return $randConfig;
    }

}
