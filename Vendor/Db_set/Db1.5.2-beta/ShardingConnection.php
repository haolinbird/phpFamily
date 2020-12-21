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
class ShardingConnection extends Connection {
	protected static $instance;
	protected static $configs;
	protected static $connections = array();

	/**
	 * 关联的主库规则
	 *
	 * @var \Db\ShardingRule
	 */
	protected static $dataCenterRule = null; // 数据中心Rule规则

	/**
	 * 关联的主库dsn配置
	 *
	 * @var array
	 */
	protected static $relatedDcConfigs = array();
	protected static $badConnectionAlias;


	protected $currentRule = null;

	/**
	 * 构造方法.
	 *
	 * @param string $dsn  PDO使用的dsn.
	 * @param string $username 用户名.
	 * @param string $passwd 密码.
	 * @param array $options 需要配置的选项.
	 *
	 * @access protected
	 */
	protected function __construct($config = null) {
		if (!static::$configs) {
			if (!class_exists('\Config\DbSharding')) {
				throw new Exception('Neither configurations are set nor  Config\DbSharding are found!');
			}
			// 这里在DbSharing的构造函数会用global dsn替换每个db的dsn
			static::$configs = (array) new \Config\DbSharding();
		}

		return parent::__construct($config);
	}

	/**
	 * @return boolean
	 */
	public function issetDataCenterRule() {
		return !is_null(self::$dataCenterRule);
	}

	/**
	 * 设置数据中心路由规则,从库需要设置主库规则，保证写在一个机房，只能双活Sharding的有用.
	 *
	 * @param ShardingRule $rule 主表规则
	 * @param string $type 读连接还是写连接
	 *
	 * @return \Db\ShardingConnection 自己
	 */
	public function setDataCenterRule(ShardingRule $rule = null, $type = 'write') {
		if ($rule == null) {
			self::$dataCenterRule = null;
			self::$relatedDcConfigs = array();
			return $this;
		}

		self::$dataCenterRule = $rule;

		$name = $rule->getCfgName();
		$dbname = $rule->getDbName();

		if (!isset(static::$configs[$type][$name])) {
			$this->throwException("Can't find the Sharding Config", 40041);
		}

		$randKey = array_rand(static::$configs[$type][$name]);
		$cfg = static::$configs[$type][$name][$randKey];

		$dcConfigs = (array) self::$dataCenter->getDataCenterCfg($cfg['db'], $dbname, $type);

		if (empty($dcConfigs)) {
			$dcConfigs = array(
				array(
					'host' => $cfg['host'],
					'port' => $cfg['port'],
					'weight' => $cfg['weight'],
					'dc' => 'default',
					'dsn' => "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$dbname}",

				));
		}

		self::$relatedDcConfigs = $dcConfigs;

		return $this;
	}

	/**
	 * 重新计算,获取配置: DSN, HOST等,
	 * 1. 使用ShardingRule权重轮询得到库表；
	 * 2. 使用表名,数据库名 计算出数据库名使用哪一个数据中心配置；
	 * 3. 如果没有配置数据中心则第1步的配置,
	 *
	 * @param ShardingRule $rule database name
	 * @param string		$conn_type
	 * @param string 		$conn_name
	 * @return array
	 */
	protected function getShardingConfig(ShardingRule $rule, $conn_type = null, $conn_name = null) {
		$name = $rule->getCfgName();
		
		$type = $conn_type == null ? $this->type : $conn_type;
		if (!isset(static::$configs[$type][$name])) {
			throw new Exception($type . ' configuration of "' . $name . '" is not found.', 42003);
		}

		$configs = static::$configs[$type][$name];

		$db = $this->getFieldFromConfigs($configs, 'db');

		// 优先使用关联库规则计算出的dsn
		if (!empty(self::$relatedDcConfigs) && self::$dataCenterRule != null) {
			$dcConfigs = self::$relatedDcConfigs;
		} else {
			// 这里尝试按照db配置的dsn规则(在Config/DataCenter.php中)获取
			$dcConfigs = (array) self::$dataCenter->getDataCenterCfg($db, $rule->getDbName(), $this->type);
		}
		
		if($conn_name) {
			$configs['name'] = $conn_name;
		}
		$config = parent::getConfig($configs, $dcConfigs, $rule);

		// 由于sharding配置的user和password与非sharding不一致，会导致重连的时候字段有问题，因此这里要增加冗余字段
		$config['user'] = $config['username'];
		$config['password'] = $config['passwd'];
		return $config;
	}

	/**
	 * 按照rule获取读库.
	 *
	 * @param object $rule Sharding Rule.
	 *
	 * @access public
	 * @return object
	 * @throws Exception 初始化失败.
	 * @throws \Exception rule不是ShardingRule的实例.
	 */
	public function read($rule = null) {
		if ($rule != null && !$rule instanceof ShardingRule) {
			throw new Exception("sharding rule must be an instance of ShardingRule.");
		}
		if ($rule == null && func_num_args() == 0 && $this->currentRule) {
			$rule = $this->currentRule;
		}

		$this->type = self::READ;

		try {
			$connection = $this->getShardingConnection($rule);
		} catch (\Exception $e) {
			throw new \Exception('尝试连接所有的epg ' . $this->type . '节点失败， 错误如下：' . PHP_EOL . $e->getMessage(), $e->getCode());
		} finally {
			// 重置partition dsn配置,避免下个请求拿到错误的dsn配置
			$this->toRestoreDsnConfig();
		}

		static::$connections[$this->type][$connection->connectionCfg['alias']] = $connection;

		return $connection;
	}

	/**
	 * 按照rule获取读库.
	 *
	 * @param ShardingRule|string $rule  Sharding Rule.
	 * @return object
	 * @throws Exception 初始化失败.
	 * @throws \Exception rule不是ShardingRule的实例.
	 * @access public
	 */
	public function write($rule = null) {
		if ($rule != null && !$rule instanceof ShardingRule) {
			throw new \Exception("sharding rule must be an instance of ShardingRule.");
		}
		if ($rule == null && func_num_args() == 0 && $this->currentRule) {
			$rule = $this->currentRule;
		}

		$this->type = self::WRITE;

		try {
			$connection = $this->getShardingConnection($rule);
		} catch (\Exception $e) {
			throw new \Exception('尝试连接所有的epg ' . $this->type . '节点失败， 错误如下：' . PHP_EOL . $e->getMessage(), $e->getCode());
		} finally {
			// 重置partition dsn配置,避免下个请求拿到错误的dsn配置
			$this->toRestoreDsnConfig();
		}

		static::$connections[$this->type][$connection->connectionCfg['alias']] = $connection;

		return $connection;
	}

	/**
	 * 按照rule获取所有读库链接对象数组.
	 *
	 * @param object $rule Sharding Rule.
	 *
	 * @access public
	 * @return array
	 * @throws Exception 初始化失败.
	 * @throws \Exception rule不是ShardingRule的实例.
	 */
	public function readAll($rule = null) {
		return $this->readWriteAll($rule, 'read');
	}

	/**
	 * 按照rule获取所有写库链接对象数组.
	 *
	 * @param ShardingRule|string $rule  Sharding Rule.
	 * @return array
	 * @throws Exception 初始化失败.
	 * @throws \Exception rule不是ShardingRule的实例.
	 * @access public
	 */
	public function writeAll($rule = null) {
		return $this->readWriteAll($rule, 'write');
	}

	/**
	 * 获取所有分区指定数据库(主/从)库链接对象
	 *
	 * @param object $rule Sharding Rule.
	 * @param string $type 操作类型
	 *
	 * @throws Exception
	 * @return array
	 */
	private function readWriteAll($rule, $type) {
		if ($rule != null && !$rule instanceof ShardingRule) {
			throw new \Exception("sharding rule must be an instance of ShardingRule.");
		}
		if ($rule == null && $this->currentRule) {
			$rule = $this->currentRule;
		}

		$this->type = $type;

		// 优先使用partitionDsn配置, 没有设置则使用globalDsn配置
		if (isset(static::$configs['partitionDsn'])) {
			$partitionDsnConfig = static::$configs['partitionDsn'];

			// 循环所有分区配置
			foreach ($partitionDsnConfig['partition']['dsn'] as $partitionIndex => $partitionDsnConfig) {
				// 设置当前使用的分区标识, 用于后续拼接复用链接标识
				$this->partitionIndex = $partitionIndex;

				// 保存现有配置信息
				$this->configBeForePartition = static::$configs;

				// 使用分区dsn覆盖原有config配置
				static::$configs = \Db\ConfigSchema::replaceGlobalDsnCfg(static::$configs, $partitionIndex);

				try {
					$allConnections[] = $this->getShardingConnection($rule);
				} catch (\Exception $e) {
					throw new \Exception('尝试连接所有的epg ' . $this->type . '节点失败， 错误如下：' . PHP_EOL . $e->getMessage(), $e->getCode());
				} finally {
					// 重置partition dsn配置,避免下个请求拿到错误的dsn配置
					$this->toRestoreDsnConfig();
				}
			}
		} else {
			try {
				$allConnections[] = $this->getShardingConnection($rule);
			} catch (\Exception $e) {
				throw new \Exception('尝试连接所有的epg ' . $this->type . '节点失败， 错误如下：' . PHP_EOL . $e->getMessage(), $e->getCode());
			} finally {
				// 重置partition dsn配置,避免下个请求拿到错误的dsn配置
				$this->toRestoreDsnConfig();
			}
		}

		return $allConnections;
	}

	/**
	 * 尝试添加一个链接.
	 *
	 * @param $rule
	 *
	 * @return \Db\ShardingConnection
	 */
	protected function getShardingConnection($rule) {
		$name = $rule->getCfgName();
		$configs = static::$configs[$this->type][$name];
		$nodesCount = count($configs) > 1 ? count($configs) : 2;

		$expMsgs = '';
		$connection = null;

		for ($i = 0; $i < $nodesCount; $i++) {
			$cfg = $this->getShardingConfig($rule);

			// 复用链接
			if (!empty(static::$connections[$this->type][$cfg['alias']])) {
				return static::$connections[$this->type][$cfg['alias']];
			}

			$options = isset($cfg['options']) ? $cfg['options'] : array();

			// 没有异常则使用这个配置并停止尝试
			try {
				$connection = new static($cfg);
				break;
			} catch (\Exception $e) {
				$expMsgs .= "连接epg节点[{$cfg['dsn']}]错误: " . $e->getMessage() . PHP_EOL;

				// 2002:connection refused 或者 connection timeout
				// 非2002是其它问题（鉴权错误等）,这类错误就不用重试，也不用加黑名单，直接报错
				// 可能是PHPServer抛出的超时异常，所以需要重新封装异常，否则无法知道报错的epg节点信息
				if ($e->getCode() != 2002) {
					throw new \Exception($expMsgs, $e->getCode());
				}

				$this->kickConfig($cfg);
			}
		}

		if (!$connection) {
			throw new \Exception($expMsgs);
		}

		$connection->currentRule = $rule;
		$connection->connectionCfg = $cfg;

		$this->logDataCenter($cfg);

		return $connection;

	}

	protected function logDataCenter($dcCfg = array()) {
		if (isset(static::$configs['DEBUG']) && static::$configs['DEBUG'] && isset(static::$configs['DEBUG_LEVEL'])) {
			$logString = sprintf("DataCenter Config, dc: %s, host: %s, port:%s, weight:%s \n", $dcCfg['dc'], $dcCfg['host'], $dcCfg['port'], $dcCfg['weight']);
			\Log\Handler::instance('db')->log($logString);
		}
	}

	/**
	 * 设置Sharding规则.
	 *
	 * @param object $rule 规则对象.
	 *
	 * @return static
	 * @throws \Exception rule不是ShardingRule的实例.
	 */
	public function setRule($rule) {
		if (!$rule instanceof ShardingRule) {
			throw new \Exception("sharding rule must be an instance of ShardingRule.");
		}
		$this->currentRule = $rule;
		return $this;
	}

	/**
	 * 查询.
	 *
	 * @param string $sql 查询sql.
	 * @param boolean $async 是否异步.
	 *
	 * @return PDOStatement
	 * @throws Exception No WHERE condition in SQL(UPDATE, DELETE) to be executed! please make sure it's safe.
	 */
	public function query($sql = null) {
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
			'DELETE',
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
			if ($retryCount < 3 && $this->needConfirmConnection()) {
				// connection lost
				if (2006 == $errorInfo[1] || self::EPG_RECONNECT_STATUS == $errorInfo[1]) {
					$retryCount += 1;
					$this->reConnectEpg();
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
	 * Get Sharding rule.
	 *
	 * @return object|null
	 */
	public function getRule() {
		return $this->currentRule ? $this->currentRule : null;
	}
}
