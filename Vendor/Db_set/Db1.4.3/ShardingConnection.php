<?php
/**
 * Class DbShardingConnection
 *
 * @author Haojie Huang<haojieh@jumei.com>
 */
namespace Db;


/**
 * DbShardingConnection.
 */
class ShardingConnection extends Connection
{

    /**
     * instance of the DbConnection
     */
    protected static $instance;

    protected static $configs;

    protected static $dataCenterRule = null; // 数据中心Rule规则

    public static $dcCfg = array(); //$dcCfg{host:'',port:'', dc:'', weight:''}

    protected $currentRule = null;

    protected static $writeConnections = array();

    protected static $readConnections = array();

    protected static $writeAsyncConnections = array();

    protected static $readAsyncConnections = array();

    protected static $asyncConnections = array();

    protected $isPdo = true;

    protected static $mysqliPdoOptions = array(
        MYSQLI_INIT_COMMAND => \PDO::MYSQL_ATTR_INIT_COMMAND,
        MYSQLI_OPT_CONNECT_TIMEOUT => \PDO::ATTR_TIMEOUT,
        \PDO::ATTR_PERSISTENT => \PDO::ATTR_PERSISTENT
    );

    /**
     * 构造方法.
     *
     * @param string $host
     *            Host.
     * @param string $username
     *            用户名.
     * @param string $passwd
     *            密码.
     * @param string $db
     *            数据库名.
     * @param integer $port
     *            端口.
     * @param array $options
     *            需要配置的选项.
     *            
     * @access protected
     */
    protected function __construct($host = null, $username = null, $passwd = null, $db = null, $port = null, array $options = array())
    {
        $this->usePool = false;//1.4.0这个版本以后,不再支持本地连接池
        // set it, when connect error, will use it.
        $this->connectionCfg = array(
            'host' => $host,
            'username' => $username,
            'db'    => $db,
            'port'  => $port,
            'passwd' => $passwd,
            'options' => $options
        );
        if (! self::$configs) {
            if (! class_exists('\Config\DbSharding')) {
                throw new Exception('Neither configurations are set nor  Config\DbSharding are found!');
            }
            self::$configs = (array) new \Config\DbSharding();
        }
        if (! self::$dataCenter) {
            if (! class_exists('\Config\DataCenter')) {
                throw new Exception('Neither configurations are set nor  Config\DataCenter are found!');
            }
            self::$dataCenter = new \Config\DataCenter();
        }
        if (! is_null($host)) {
            return $this->connect($host, $username, $passwd, $db, $port, $options);
        }
        return null;
    }

    /**
     * 连接数据库方法.
     *
     * @param string $host
     *            Host.
     * @param string $username
     *            用户名.
     * @param string $passwd
     *            密码.
     * @param string $db
     *            数据库名.
     * @param string $port
     *            端口号.
     * @param array $options
     *            需要配置的选项.
     *            
     * @access public
     * @return object
     * @throws \Exception connecton error.
     */
    public function connect($host, $username = null, $passwd = null, $db = null, $port = null, array $options = array())
    {
        if (is_array($host)) {
            extract($host);
        }
        
        if ($this->connection) {
            return $this;
        } else {
//          $rule = $this->currentRule;
//          $db = $rule->getDbName($db);
            $dsn = strpos($host, "mysql:") === 0 ? $host : "mysql:host={$host};port={$port};dbname={$db}";
            $this->connectionCfg['dsn'] = $dsn;
            if ($this->isPdo) {
                try {
                    if ($this->usePool) {
                        $this->connection = new \pdo_connect_pool($dsn, $username, $passwd, $this->genPdoOptions($options));
                    } else {
                        $this->connection = new \PDO($dsn, $username, $passwd, $this->genPdoOptions($options));
                    }
                } catch (\Exception $ex) {
                    if (strstr($ex->getMessage(), "connect to pool_server fail")) { // 端口连不上
                        $this->connection = new \PDO($dsn, $username, $passwd, $this->genPdoOptions($options));
                        $this->usePool = false;
                    } else {
                        throw $ex;
                    }
                }
            } else {
                $this->connection = new \Mysqli($host, $username, $passwd, $db, $port, $options);
                if ($this->connection->connect_errno === 2002) {
                    // Retry once on connection error.
                    $this->connection = new \Mysqli($host, $username, $passwd, $db, $port, $options);
                } elseif ($this->connection->connect_errno) {
                    throw new Exception('Mysqli connection error:(' . $this->connection->connect_errno . ') ' . $this->connection->connect_error);
                }
            }
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function issetDataCenterRule()
    {
        return !is_null(self::$dataCenterRule);
    }

    /**
     * 设置数据中心路由规则
     * 只能双活Sharding的有用
     *
     * @param ShardingRule|null $rule 主表规则
     * @param string $type 读连接还是写连接
     * @return Object 自己
     */
    public function setDataCenterRule(ShardingRule $rule = null, $type = 'write')
    {
        if ($rule == null){
            self::$dataCenterRule = null;
            self::$dcCfg = array();
        } else {
            self::$dataCenterRule = $rule;
            $name = $rule->getCfgName();
            if (!isset(self::$configs[$type][$name])) {
                $this->throwException("Can't find the Sharding Config",40041);
            }
            $cfg = self::$configs[$type][$name];
            $cfg = array_shift($cfg);
            $dcCfg = (array) self::$dataCenter->getDataCenterCfg($cfg['db'], $rule->getDbName(), $type);
            if (empty($dcCfg)){
                $dcCfg = array(
                    array(
                    'host'  => $cfg['host'],
                    'port'  => $cfg['port'],
                    'weight'=> $cfg['weight'],
                    'dc'    => 'default',
                ));
            }
            self::$dcCfg = $this->getOneNode($dcCfg);
            $this->logDataCenter($cfg);
        }
        return $this;
    }

    /**
     * 重新计算,获取配置: DSN, HOST等,
     * 1. 使用ShardingRule权重轮询得到库表；
     * 2. 使用表名,数据库名 计算出数据库名使用哪一个数据中心配置；
     * 3. 如果没有配置数据中心则第1步的配置,
     *
     * @param ShardingRule $rule
     *            database name
     * @param array $config
     *            table name
     * @param string $type
     *            read or write
     * @return array
     */
    public function getCfg(ShardingRule $rule, array $config, $type = "read")
    {
        $cfg = array_shift($config);
        $cfg['dc'] = 'default';
        $dcCfg = (array) self::$dataCenter->getDataCenterCfg($cfg['db'], $rule->getDbName(), $type);
        if (!empty($dcCfg)) {
            $dcCfg = $this->getOneNode($dcCfg);
        }
        $cfg = array_merge($cfg, $dcCfg);
        //如果设置了数据中心, 则使用设置的数据中心
        if ( !empty(self::$dcCfg) && self::$dataCenterRule != null) {
            $cfg = array_merge($cfg, self::$dcCfg);
        }
        $cfg['db'] = $rule->getDbName($cfg['db']);
        $cfg['alias'] = "{$cfg['dc']}:{$cfg['db']}";

        return $cfg;
    }

    /**
     * 按照rule获取读库.
     *
     * @param object $rule
     *            Sharding Rule.
     *            
     * @access public
     * @return object
     * @throws Exception 初始化失败.
     * @throws \Exception rule不是ShardingRule的实例.
     */
    public function read($rule = null)
    {
        if ($rule != null && ! $rule instanceof ShardingRule) {
            throw new Exception("sharding rule must be an instance of ShardingRule.");
        }
        if ($rule == null && func_num_args() == 0 && $this->currentRule) {
            $rule = $this->currentRule;
        }

        $name = $rule->getCfgName();
        if (isset(self::$configs['read'][$name])) {
	    $cfg = $this->getCfg($rule, self::$configs['read'][$name]);
            $conn = null;
            //使用缓存的连接或新建
            if (empty(self::$readConnections[$cfg['alias']])) {
                $conn = $this->addReadConnection($rule);
                if (!$conn) {
                    throw new Exception('No available read connections. Please use addReadConnection to initialize  first', 42001);
                }
            } else {
                $conn = self::$readConnections[$cfg['alias']];
            }
            $this->logDataCenter($cfg);
            return $conn;
        } else {
            throw new Exception('Read configuration of "' . $name . '" is not found.', 42003);
        }
    }

    /**
     * 按照rule获取读库.
     *
     * @param ShardingRule|string $rule
     *            Sharding Rule.
     * @return object
     * @throws Exception 初始化失败.
     * @throws \Exception rule不是ShardingRule的实例.
     * @access public
     */
    public function write($rule = null)
    {
        if ($rule != null && ! $rule instanceof ShardingRule) {
            throw new \Exception("sharding rule must be an instance of ShardingRule.");
        }
        if ($rule == null && func_num_args() == 0 && $this->currentRule) {
            $rule = $this->currentRule;
        }

        $name = $rule->getCfgName();
        if (isset(self::$configs['write'][$name])) {
	        $cfg = $this->getCfg($rule, self::$configs['write'][$name], "write");
            $conn = null;
            //使用缓存的连接或新建
            if (empty(self::$writeConnections[$cfg['alias']])) {
                $conn = $this->addWriteConnection($rule);
                if (!$conn) {
                    throw new Exception('No available write connections. Please use addWriteConnection to initialize  first', 42001);
                }
            } else {
                $conn = self::$writeConnections[$cfg['alias']];
            }
            $this->logDataCenter($cfg);
            return $conn;
        } else {
            throw new Exception('Write configuration of "' . $name . '" is not found.', 42003);
        }
    }

    /**
     * Initialize read connections.
     *
     * @param ShardingRule|string $rule
     *            Sharding Rule.
     *            
     * @access public
     * @return object
     * @throws Exception 初始化失败.
     */
    public function addReadConnection( $rule = null)
    {
        $name = $rule->getCfgName();
        if (isset(self::$configs['read'][$name])) {
            
            // 客户端负载模式, 选取访问节点
            $cfg = $this->getCfg($rule, self::$configs['read'][$name]);

            $options = isset($cfg['options']) ? $cfg['options'] : array();
            $connection = new self();
            $connection->isPdo = true;
            $connection->currentRule = $rule;
            $connection = $connection->connect($cfg['host'], $cfg['username'], $cfg['passwd'], $cfg['db'], $cfg['port'], $options);
            $connection->connectionCfg = $cfg;
            self::$readConnections[$cfg['alias']] = $connection;
            return $connection;
        } else {
            throw new Exception('Read configuration of "' . $name . '" is not found.', 42003);
        }
    }

    /**
     * Initialize write connections.
     *
     * @param ShardingRule|string $rule
     *            Sharding Rule.
     *            
     * @access public
     * @return object
     * @throws Exception 初始化失败.
     */
    public function addWriteConnection( $rule = null)
    {
        $name = $rule->getCfgName();
        if (isset(self::$configs['write'][$name])) {
            
            // 客户端负载模式, 选取访问节点
            $cfg = $this->getCfg($rule, self::$configs['write'][$name], 'write');

            $options = isset($cfg['options']) ? $cfg['options'] : array();
            $connection = new self();
            $connection->isPdo = true;
            $connection->currentRule = $rule;
            $connection = $connection->connect($cfg['host'], $cfg['username'], $cfg['passwd'], $cfg['db'], $cfg['port'], $options);
            $connection->connectionCfg = $cfg;
            self::$writeConnections[$cfg['alias']] = $connection;
            return $connection;
        } else {
            throw new Exception('Write configuration of "' . $name . '" is not found.', 42003);
        }
    }

    public function logDataCenter($dc = array()){
	if (isset(static::$configs['DEBUG']) && static::$configs['DEBUG'] && isset(static::$configs['DEBUG_LEVEL'])) {
        $dcCfg = self::$dcCfg;
	    if (empty(self::$dcCfg)){
            $dcCfg = $dc;
        }
	    $logString = sprintf("DataCenter Config, dc: %s, host: %s, port:%s, weight:%s \n", $dcCfg['dc'], $dcCfg['host'], $dcCfg['port'], $dcCfg['weight']);
//	    print_r($logString);
	    \Log\Handler::instance('db')->log($logString);
	}	    
    }

    /**
     * 按照rule获取读库.
     *
     * @param object $rule
     *            Sharding Rule.
     *            
     * @access public
     * @return object
     * @throws Exception 初始化失败.
     * @throws \Exception rule不是ShardingRule的实例.
     */
    public function readAsync($rule = null)
    {
        if ($rule != null && ! $rule instanceof ShardingRule) {
            throw new \Exception("sharding rule must be an instance of ShardingRule.");
        }
        if ($rule == null && func_num_args() == 0 && $this->currentRule) {
            $rule = $this->currentRule;
        }
        if (empty(self::$readAsyncConnections[$rule->getAtomName()]) && ! $this->addReadAsyncConnection($rule)) {
            throw new Exception('No available read connections. Please use addReadConnection to initialize  first', 42001);
        }
        return self::$readAsyncConnections[$rule->getAtomName()];
    }

    /**
     * 按照rule获取读库.
     *
     * @param object $rule
     *            Sharding Rule.
     *            
     * @access public
     * @return object
     * @throws \Exception rule不是ShardingRule的实例.
     * @throws Exception 初始化失败.
     */
    public function writeAsync($rule = null)
    {
        if ($rule != null && ! $rule instanceof ShardingRule) {
            throw new \Exception("sharding rule must be an instance of ShardingRule.");
        }
        if ($rule == null && func_num_args() == 0 && $this->currentRule) {
            $rule = $this->currentRule;
        }
        if (empty(self::$writeAsyncConnections[$rule->getAtomName()]) && ! $this->addWriteAsyncConnection($rule)) {
            throw new Exception('No available write connections. Please use addWriteConnection to initialize  first', 42001);
        }
        return self::$writeAsyncConnections[$rule->getAtomName()];
    }

    /**
     * Initialize read connections.
     *
     * @param object $rule
     *            Sharding Rule.
     *            
     * @access public
     * @return object
     * @throws Exception 初始化失败.
     */
    public function addReadAsyncConnection($rule = null)
    {
        $name = $rule->getCfgName();
        if (isset(self::$configs['read'][$name])) {
            $cfg = $this->getOneNode(self::$configs['read'][$name]);
            $options = isset($cfg['options']) ? $cfg['options'] : array();
            $connection = new self();
            $connection->isPdo = false;
            $connection->currentRule = $rule;
            $connection->connectionCfg = $cfg;
            $connection = $connection->connect($cfg['host'], $cfg['username'], $cfg['passwd'], $cfg['db'], $cfg['port'], $options);
            self::$readAsyncConnections[$rule->getAtomName()] = $connection;
            return self::$readAsyncConnections[$rule->getAtomName()];
        } else {
            throw new Exception('Read configuration of "' . $name . '" is not found.', 42003);
        }
    }

    /**
     * Initialize write connections.
     *
     * @param object $rule
     *            Sharding Rule.
     *            
     * @access public
     * @return object
     * @throws Exception 初始化失败.
     */
    public function addWriteAsyncConnection($rule = null)
    {
        $name = $rule->getCfgName();
        if (isset(self::$configs['write'][$name])) {
            $cfg = $this->getOneNode(self::$configs['write'][$name]);
            $options = isset($cfg['options']) ? $cfg['options'] : array();
            $connection = new self();
            $connection->isPdo = false;
            $connection->currentRule = $rule;
            $connection->connectionCfg = $cfg;
            $connection = $connection->connect($cfg['host'], $cfg['username'], $cfg['passwd'], $cfg['db'], $cfg['port'], $options);
            self::$writeAsyncConnections[$rule->getAtomName($name)] = $connection;
            return self::$writeAsyncConnections[$rule->getAtomName($name)];
        } else {
            throw new Exception('Write configuration of "' . $name . '" is not found.', 42003);
        }
    }

    /**
     * 设置Sharding规则.
     *
     * @param object $rule
     *            规则对象.
     *            
     * @return static
     * @throws \Exception rule不是ShardingRule的实例.
     */
    public function setRule($rule)
    {
        if (! $rule instanceof ShardingRule) {
            throw new \Exception("sharding rule must be an instance of ShardingRule.");
        }
        $this->currentRule = $rule;
        return $this;
    }

    /**
     * 设置异步获取数据.
     *
     * @return \Db\ShardingConnection
     */
    public function async()
    {
        $this->async = true;
        $name = $this->currentRule->getTableName($this->currentRule->getCfgName());
        static::$asyncConnections[$name] = static::$writeConnections[$name];
        return $this;
    }

    /**
     * 查询.
     *
     * @param string $sql
     *            查询sql.
     * @param boolean $async
     *            是否异步.
     *            
     * @return PDOStatement
     * @throws Exception No WHERE condition in SQL(UPDATE, DELETE) to be executed! please make sure it's safe.
     */
    public function query($sql = null, $async = false)
    {
        static $retryCount = 0;
        
        $withCache = false;
        $this->withCache = true;
        
        if (empty($sql)) {
            $this->lastSql = $this->getSelectSql(); // 不需要trim，拼接函数保证以SELECT开头
        } else {
            $this->lastSql = trim($this->buildSql($sql));
        }
        $sqlCmd = strtoupper(substr($this->lastSql, 0, 6));
        if (in_array($sqlCmd, array(
            'UPDATE',
            'DELETE'
        )) && stripos($this->lastSql, 'where') === false) {
            throw new Exception('no WHERE condition in SQL(UPDATE, DELETE) to be executed! please make sure it\'s safe', 42005);
        }
        
        if ($this->allowRealExec || $sqlCmd == 'SELECT') {
            $cacheKey = md5($this->lastSql);
            if ($withCache && isset($this->cachedPageQueries[$cacheKey])) {
                return $this->cachedPageQueries[$cacheKey];
            }
            
            $trace = $this::getExternalCaller();
            \MNLogger\TraceLogger::instance('trace')->MYSQL_CS($this->connectionCfg['dsn'], $trace['class'] . '::' . $trace['method'], $this->lastSql, $trace['file'] . ':' . $trace['line']);
            $this->memoryUsageBeforeFetch = memory_get_usage();
            $this->queryBeginTime = microtime(true);
            try {
                if ($async) {
                    $this->lastStmt = $this->connection->query($this->lastSql, $async);
                } else {
                    $this->lastStmt = $this->connection->query($this->lastSql);
                }
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
                $connectionLost = false;
                if (! $this->usePool) {
                    $connectionLost = 2006 == $errorInfo[1];
                } else {
                    if (isset($queryEx)) { // 目前只能通过message字符串匹配,不是很严谨.
                        $connectionLost = stripos($queryEx->getMessage(), 'gone away') !== false;
                        trigger_error($queryEx, E_USER_WARNING);
                        if (! $connectionLost) {
                            $this->throwException($queryEx);
                        }
                    } else {
                        if ($errorInfo) {
                            $errorInfo = $errorInfo[2];
                        } else {
                            $errorInfo = 'Un-handleable connect pool error. sql: ' . $this->lastSql;
                        }
                        $this->throwException(new Exception($errorInfo));
                    }
                }
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
        return $this->lastStmt;
    }

    /**
     * 获取所有数据.
     *
     * @return array
     * @throws \Exception 查询失败.
     */
    public static function asyncFetchAll()
    {
        $allLinks = self::$asyncConnections;
        $asyncData = array();
        $processed = 0;
        do {
            $links = $errors = $reject = array();
            foreach ($allLinks as $link) {
                $links[] = $errors[] = $reject[] = $link;
            }
            if (! mysqli_poll($links, $errors, $reject, 1)) {
                continue;
            }
            foreach ($links as $link) {
                $result = $link->reap_async_query();
                if ($result) {
                    $asyncData = array_merge($asyncData, $result->fetch_all(MYSQLI_ASSOC));
                    $result->free();
                } else {
                    throw new \Exception("sync query failed!");
                }
                $processed ++;
            }
        } while ($processed < count($allLinks));
        static::clearAsyncConnections();
        return $asyncData;
    }

    /**
     * 异步查询.
     *
     * @return mixed
     */
    public function asyncQuery()
    {
        // 暂时禁止直接SQL查询.
        $sql = null;
        $name = $this->currentRule->getAtomName();
        static::$asyncConnections[$name] = $this->connection;
        return $this->query($sql, true);
    }

    /**
     * 把Mysqli的Option转换为PDO需要的.
     *
     * @param array $options
     *            Mysqli设置选项.
     *            
     * @return array
     */
    protected function genPdoOptions(array $options)
    {
        if ($this->isPdo) {
            return $options;
        }
        $pdoOptions = array();
        foreach ($options as $key => $op) {
            if (isset(static::$mysqliPdoOptions[$key])) {
                $pdoOptions[static::$mysqliPdoOptions[$key]] = $op;
            } else {
                new \Exception("Sharding Db 配置项中没有在 \DbShardingConnection::\$mysqliPdoOptions 找到对应的PDO项. MYSQLI KEY:$key");
            }
        }
        return $pdoOptions;
    }

    /**
     * Clear async connection.
     *
     * @return void
     */
    public static function clearAsyncConnections()
    {
        static::$writeAsyncConnections = array();
        static::$readAsyncConnections = array();
        static::$asyncConnections = array();
    }

    /**
     * Close all connections.
     *
     * @return boolean
     */
    public function closeAll()
    {
        foreach (static::$readConnections as $k => $v) {
            if ($v->isPdo) {
                $v->close();
            }
            $v = null;
            unset(static::$readConnections[$k]);
        }
        foreach (static::$writeConnections as $k => $v) {
            if ($v->isPdo) {
                $v->close();
            }
            $v = null;
            unset(static::$writeConnections[$k]);
        }
        foreach (static::$writeAsyncConnections as $k => $v) {
            if ($v->isPdo) {
                $v->close();
            }
            $v = null;
            unset(static::$writeAsyncConnections[$k]);
        }
        foreach (static::$readAsyncConnections as $k => $v) {
            if ($v->isPdo) {
                $v->close();
            }
            $v = null;
            unset(static::$writeAsyncConnections[$k]);
        }
        return true;
    }

    /**
     * Get Sharding rule.
     *
     * @return object|null
     */
    public function getRule()
    {
        return $this->currentRule ? $this->currentRule : null;
    }
}


