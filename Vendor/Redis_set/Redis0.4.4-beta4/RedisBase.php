<?php
namespace Redis;
use MNLogger\TraceLogger;
use MNLogger\DATALogger;
use Exception;

/**
 *
 * @author xinhuag@jumei.com
 */
abstract class RedisBase {

    const STRING = 1;
    const SET = 2;
    const LISTS = 3;
    const ZSET = 4;
    const HASH = 5;

    const CONNECT_PROXY  = 1;
    const CONNECT_DIRECT = 0;
    const CONNECT_FAILED = -1;

    public $config = array();

    protected $redis = array();

    public $connectState = self::CONNECT_FAILED;

    /**
     * multi默认为ATOMIC模式（默认模式）.
     *
     * @var integer
     */
    public $multiMode = \Redis::ATOMIC;

    /**
     * multi命令缓存.
     *
     * @var array
     */
    public $multiCache = array();

    /*
     * 所有的读操作
     */
    protected $ReadFun = array(
    );
    /*
     * 所有的写操作
     */
    protected $WriteFun = array(
    );

    /**
     * @var bool
     *
     * 是否使用连接池，在使用连接前的基础上，再判断useOldPool是否走老版连接池
     */
    protected $usePool    = true;

    /**
     * @var bool
     *
     * 是否使用老的连接池
     */
    protected $useOldPool = false;


    protected function __construct() {
        if (!class_exists('redis_connect_pool')) {
            $this->useOldPool = false;
        }
    }

    /**
    * 节点映射
    * array('master'=>'master-alia')
    */
    protected $mapMasterToAlia = array();

    /*
     * 暂时不支持的函数
     */
    protected $DisableFun = array(
        "KEYS", "BLPOP", "MSETNX", "BRPOP", "RPOPLPUSH", "BRPOPLPUSH", "SMOVE", "SINTER", "SINTERSTORE", "SUNION", "SUNIONSTORE", "SDIFF", "SDIFFSTORE", "ZINTER", "ZUNION",
        "FLUSHDB", "FLUSHALL", "RANDOMKEY", "SELECT", "MOVE", "RENAMENX", "DBSIZE", "BGREWRITEAOF", "SLAVEOF", "SAVE", "BGSAVE", "LASTSAVE"
    );

    /*
     * 本次调用的具体物理机,用于调试
     */
    protected $target = '';

    public function __call($name, $arguments) {
        if ($this->multiMode != \Redis::ATOMIC) {
            // multi模式，缓存命令和参数
            $this->multiCache[] = array('name' => $name, 'arg' => $arguments);
            return true;
        }
        if (in_array(strtoupper($name), $this->DisableFun)) {
            throw new Exception("call the disable function!");
        }

        $obj = $this->ConnectTarget($arguments[0]);
        if (empty($obj)) {//节点失效了，但是ping还没踢掉呢
            return false;
        }

        $exCaller = $this::getExternalCaller();
        // 需先初始化MNLogger
        $logger = TraceLogger::instance('trace');
        $logger->REDIS_CS($this->target, $exCaller['class'] . '::' . $exCaller['method'], serialize($arguments));
        try {
            $ret = call_user_func_array(array($obj, $name), $arguments);
            if (($ret === FALSE || empty($ret)) && isset(static::$configs['auto_rehash']) && static::$configs['auto_rehash']) {
                // trigger lazy rehash
                if ($this->_lazyRehash($arguments[0])) {
                    //Do again
                    $ret = call_user_func_array(array($obj, $name), $arguments);
                }
            }
        } catch (Exception $ex) {
            if ($this->_isUseOldPool()) {
                $obj->release();
            }
            unset($this->redis[$this->target]);
            $logger->REDIS_CR("EXCEPTION", 0);
            throw new Exception('Redis operation error. node: '.$arguments[0].' details: '.$ex->getMessage(), 32001, $ex);
        }
        if ($this->_isUseOldPool()) {
            $obj->release();
        }
        $logger->REDIS_CR("SUCCESS", strlen(serialize($ret)));

        return $ret;
    }

    protected function _lazyRehash($key)
    {
        if (!isset(static::$configs[$this->configName . '_previous'])) {
            // 没有旧配置，返回false
            return false;
        }
        $className = get_called_class();
        $redis_previous = $className::getInstance($this->configName . '_previous');
        $_previousTarget = $redis_previous->getConnectTarget($key);
        $redis_previous->real_connect($_previousTarget, $key);
        list($host, $port) = explode(":", $this->target);
        $db = isset($this->config['db']) ? $this->config['db'] : 0;

        $ret = $redis_previous->redis[$_previousTarget]->migrate($host, $port, $key, $db, 3600);
        $str = $ret ? "_SUCCESS" : "_FAILED";
        DATALogger::instance($str)->log($key, "MIGRATE{$str} from {$_previousTarget} to {$this->target}");
        return $ret;
    }
    public function _rehash($_previousTargets = array())
    {
        static::$configs['auto_rehash'] = false;
        $match = isset(static::$configs['match']) ? static::$configs['match'] : '*';
        $count = isset(static::$configs['count']) ? static::$configs['count'] : 3000;

        $configName = explode("_", $this->configName);
        $className = get_called_class();
        $redis_new = $className::getInstance($configName[0]);
        foreach ($_previousTargets as $_previousTarget) {
            $this->real_connect($_previousTarget, '');
            $this->redis[$_previousTarget]->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
            $it = null;
            while($keyArray = $this->redis[$_previousTarget]->scan($it, $match, $count)) {
                foreach ($keyArray as $key) {
                    $_destTarget = $redis_new->getConnectTarget($key);
                    if (strcmp($_destTarget, $_previousTarget) !== 0) {
                        list($host, $port) = explode(':', $_destTarget);
                        $db = isset($this->config['db']) ? $this->config['db'] : 0;
                        $ret = $this->redis[$_previousTarget]->migrate($host, $port, $key, $db, 3600);
                        $str = $ret ? "_SUCCESS" : "_FAILED";
                        DATALogger::instance($str)->log($key, "MIGRATE{$str} from {$_previousTarget} to {$_destTarget}");
                    }
                }
            }
        }
       return ;
    }

    /**
     * 提供pipeline支持.
     *
     * @return boolean
     */
    public function pipeline()
    {
        $this->multiMode = \Redis::PIPELINE;
        return true;
    }

    public function MULTI()
    {
        $this->multiMode = \Redis::MULTI;
        return true;
    }

    public function EXEC() {
        // 缓存multi mode,将成员变量复位
        $multiMode = $this->multiMode;
        $this->multiMode = \Redis::ATOMIC;
        $key = null;

        foreach ((array) $this->multiCache as $cache) {
            $arguments = $cache['arg'];
            // 强一致性，要求key必须全部一致
            // 若非强一致性的模式（比如配置的集群都是一个节点的slave，数据是完全一致的）那可以随便挑一个节点来执行multi，不要保证key一致
            if ($this->_isConsistent()) {
                if (isset($key) && strcmp($key, $arguments[0]) !== 0) {
                    throw new Exception("Multi error! Need same key in 【consistent mode】 but multi key passed");
                }
            }
            $key = $arguments[0];
        }
        $obj = $this->ConnectTarget($key);
        $obj->MULTI($multiMode);
        foreach ((array) $this->multiCache as $cache) {
            call_user_func_array(array($obj, $cache['name']), $cache['arg']);
        }
        $ret = $obj->EXEC();

        unset($this->multiCache);
        if ($this->_isUseOldPool()) {
            $obj->release();
        }

        return $ret;
    }

    public function real_connect($target, $key) {
        if (!isset($this->redis[$target])) {//每个物理机对应一个new redis
            $logger = TraceLogger::instance('trace');

            // 获取连接池需要用到的属性(来自于config，兼容老的db配置模式)
            $property = $this->_getProperty();

            do{
                // 使用老版连接池
                if ($this->_isUseOldPool()) {
                    $this->redis[$target] = new \redis_connect_pool();
                    break;
                }

                // 使用新版连接池
                if ($this->usePool && extension_loaded('jmredisproxy')) {
                    $this->redis[$target] = new \JMRedisProxy();
                    // 先假设能使用代理，实际调用connect方法时候，由于代理不可用，可能会再次退化为直连
                    $this->connectState = self::CONNECT_PROXY;
                    $this->redis[$target]->setProperty($property);
                    break;
                }

                // 直连
                $this->redis[$target] = new \Redis();
            } while(0);

            $ip_port = explode(":", $target);
            $logger->REDIS_CS($ip_port[0] . ':' . $ip_port[1], get_class($this->redis[$target]) . '::connect', '');
            //$this->redis[$target]->connect($ip_port[0], $ip_port[1], 10);
            if (false == $this->_ConnectTarget($target, $ip_port[0], $ip_port[1], 10)) {
                $logger->REDIS_CR('exception', 0);
                unset($this->redis[$target]);
                throw new Exception('Connect redis error:' . "!\nKey: " . $key . "\nTarget:" . $target . "\nDB:" . $this->config['db']);
            }

            $logger->REDIS_CR('success', 0);

            // 只有直连或者旧版连接池的状态允许的方法
            if (self::CONNECT_DIRECT === $this->connectState) {
                if (isset($property['password'])) {
                    $this->redis[$target]->auth($property['password']);
                }

                if (isset($property['db'])) {
                    $this->redis[$target]->select($property['db']);
                }
            }
        }
    }

    /*
     * 分布式缓存需要特殊处理
     * 尽量少用,可以用集合代替呀
     */

    public function Mget(array $keys) {
        // 配置'consistent'来做算法选择判断，为true时，采用一致性hash，false则为随机选取，默认为true.
        if (! isset($this->config['consistent']) || $this->config['consistent'] == true) {
            $ret = array();
            foreach ($keys as $key) {
                $obj = $this->ConnectTarget($key); //返回redis对象
                if (!$obj)//链接失败
                    continue;
                $ret[] = $obj->get($key);
            }
            return $ret;
        } else {
            $obj = $this->ConnectTarget($keys[0]);
            return $obj->mget($keys);
        }
    }

    public function getMultiple(array $keys) {
        return $this->Mget($keys);
    }

    public function Mset(array $KeyValue) {
        $ObjValue = array();
        $ObjArr = array(); //对象数组
        $socketNum = 0;
        foreach ($KeyValue as $key => $value) {
            $obj = $this->ConnectTarget($key); //返回redis对象
            if (!$obj)//链接失败
                continue;
            $ObjArr[$socketNum] = $obj;
            $ObjValue[$socketNum][$key] = $value;
            $socketNum++;
        }
        foreach ($ObjValue as $socketNum => $kv) {
            $obj = $ObjArr[$socketNum];
            if (!$obj->mset($kv)) {
                return false;
            };
        }
        return true;
    }

    public function delete($key) {
        if (is_array($key)) {
            foreach ($key as $k) {
                $redis = $this->ConnectTarget($k);
                if (!$redis)//链接失败
                    continue;
                $redis->delete($k);
            }
        } else {
            $redis = $this->ConnectTarget($key); //返回redis对象
            $redis->delete($key);
        }
        return true;
    }

    public function GetTarget() {
        return $this->target;
    }

    /*
     * rename前端app用
     * $key1 原key
     * $key2 生成的新key
     * return 原来key的值
     */

    public function rename($key1, $key2) {
        $redis = $this->ConnectTarget($key1);
        $target1 = $this->target;
        $this->ConnectTarget($key2);
        $target2 = $this->target;
//        if ((int) $redis->socket === (int) $redisTarget->socket) {//key1,key2刚好在一台机器
        if (strcmp($target1, $target2) === 0) {//key1,key2刚好在一台机器
            return $redis->rename($key1, $key2);
        }
        $type = $redis->type($key1);
        switch ($type) {
            case self::STRING:
                return $this->renameString($key1, $key2);
            case self::SET:
                return $this->renameSet($key1, $key2);
            case self::LISTS:
                return $this->renameList($key1, $key2);
            case self::ZSET:
                return $this->renameZSet($key1, $key2);
            case self::HASH:
                return $this->renameHash($key1, $key2);
            default:
                return false;
        }
    }

    private function renameString($key1, $key2) {
        $redis = $this->ConnectTarget($key1);
        $data = $redis->get($key1);
        if ($data !== false) {
            $redisTarget = $this->ConnectTarget($key2);
            $redisTarget->delete($key2);
            if ($redisTarget->set($key2, $data) === FALSE) {
                return false;
            }
        } else {
            return false;
        }
        $redis->delete($key1);
        return true;
    }

    private function renameSet($key1, $key2) {
        $redis = $this->ConnectTarget($key1);
        $data = $redis->sMembers($key1);
        if ($data) {
            $redisTarget = $this->ConnectTarget($key2);
            $redisTarget->delete($key2);
            foreach ($data as $value) {
                if ($redisTarget->sadd($key2, $value) === FALSE) {
                    return FALSE;
                }
            }
        } else {
            return FALSE;
        }
        $redis->delete($key1);
        return true;
    }

    private function renameList($key1, $key2) {
        $redis = $this->ConnectTarget($key1);
        $data = $redis->lRange($key1, 0, -1);
        if ($data) {
            $redisTarget = $this->ConnectTarget($key2);
            $redisTarget->delete($key2);
            foreach ($data as $value) {
                if ($redisTarget->rPush($key2, $value) === FALSE) {
                    return false;
                }
            }
        } else {
            return FALSE;
        }
        $redis->delete($key1);
        return true;
    }

    private function renameZSet($key1, $key2) {
        $redis = $this->ConnectTarget($key1);
        $data = $redis->zRange($key1, 0, -1, true);
        if ($data) {
            $redisTarget = $this->ConnectTarget($key2);
            $redisTarget->delete($key2);
            foreach ($data as $value => $score) {
                if ($redisTarget->zadd($key2, $score, $value) === FALSE) {
                    return FALSE;
                }
            }
        } else {
            return FALSE;
        }
        $redis->delete($key1);
        return true;
    }

    private function renameHash($key1, $key2) {
        $redis = $this->ConnectTarget($key1);
        $data = $redis->hGetAll($key1);
        if ($data) {
            $redisTarget = $this->ConnectTarget($key2);
            $redisTarget->delete($key2);
            if ($redisTarget->hMset($key2, $data) === FALSE) {
                return FALSE;
            }
        } else {
            return FALSE;
        }
        $redis->delete($key1);
        return true;
    }

    abstract public function ConnectTarget($key); //redis对象池

    abstract public function Init();

    /**
     * 获取外部调用者.
     *
     * @return array array('file'=>'...', 'line'=>'...', 'method'=>'...', 'class'=>'..')
     */
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
     * 兼容使用jmredis和redis扩展的连接
     *
     * @param $target string 目标dsn
     * @param $ip            目标ip
     * @param $port          目标port
     * @param $timeout       超时时间
     *
     * @return bool
     */
    protected function _ConnectTarget($target, $ip, $port, $timeout)
    {
        // 如果状态为链接新版代理池
        if (self::CONNECT_PROXY === $this->connectState) {

            // connectState可能变成CONNECT_FAILED, CONNECT_DIRECT, CONNECT_PROXY的任意一种。
            $this->connectState = $this->redis[$target]->connect($ip, $port, $timeout);

            if (self::CONNECT_FAILED === $this->connectState) {
                // 再给一次机会
                $this->connectState = $this->redis[$target]->connect($ip, $port, $timeout);
                if (self::CONNECT_FAILED === $this->connectState) {
                    return false;
                }
            }
            return true;
        }

        // 使用的phpredis扩展或者旧版连接池，状态只能为CONNECT_FAILED, CONNECT_DIRECT。
        if (false === $this->redis[$target]->connect($ip, $port, $timeout)) {
            if (false === $this->redis[$target]->connect($ip, $port, $timeout)) {
                $this->connectState = self::CONNECT_FAILED;
                return false;
            }
        }
        $this->connectState = self::CONNECT_DIRECT;
        return true;
    }

    /**
     * 获取代理池需要设置的属性
     *
     * @return array.
     */
    protected function _getProperty()
    {
        $property = array();
        if (isset($this->config['property']) && is_array($this->config['property'])) {
            $property = $this->config['property'];
        }

        if (isset($this->config['db'])) {
            $property['db'] = $this->config['db'];
        }

        if (isset($this->config['password'])) {
            $property['password'] = $this->config['password'];
        }
        return $property;
    }

    protected function _isConsistent()
    {
        return !isset($this->config['consistent']) || $this->config['consistent'] == true;
    }

    protected function _isUseOldPool()
    {
        return $this->usePool && $this->useOldPool;
    }
}
