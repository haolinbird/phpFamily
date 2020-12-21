<?php

namespace Redis;

use MNLogger\TraceLogger;
use MNLogger\EXLogger;
use Exception;

/**
 * 当存储用的redis
 *
 * @author xinhuag@jumei.com
 */
class RedisStorage extends RedisBase {
    /** 物理结点 */
    private $targets;

    private static $instance;
    protected static $configs;

    public $configName;

    protected function __construct() {
        // 是否使用连接池
        if (defined('JM_PHP_CONN_POOL_ON')) {
            $this->usePool = (bool) JM_PHP_CONN_POOL_ON;
        }
        parent::__construct();
    }

    public function close() {
        $failedTargets = array();
        foreach ((array) $this->redis as $target => $value) {
            try {
                unset($this->redis[$target]);
                $value->close();
            } catch (Exception $exc) {
                $failedTargets[] = $target;
            }
        }
        if (!empty($failedTargets)) {
            throw new \RedisException('Redis close error. last closing: ' . $exc->getMessage() . '. Failed targets: ' . implode(',', $failedTargets));
        }
    }

    public static function getInstance($name = 'default') {
        $insKey = self::getInsKey($name);

        if (!isset(self::$instance[$insKey])) {
            self::$instance[$insKey] = new self;
        }

        if (!static::$configs) {
            static::$configs = (array) new \Config\Redis();
        }

        if (!self::$instance[$insKey]->config) {
            $config = static::$configs[$name];
            // 13号db专门给bench环境使用
            if (self::getEnv() == self::ENV_BENCH) {
                $config['db'] = 13;
            } elseif ($config['db'] == 13) {
                    throw new \Exception('禁用13号db，该db只提供给全链路压测使用');
            }
            self::$instance[$insKey]->config = $config;
        }
        self::$instance[$insKey]->configName = $name;
        self::$instance[$insKey]->Init();
        return self::$instance[$insKey];
    }

    public static function config($config, $name = null) {
        if (empty($config)) {
            return static::$configs;
        }
        static::$configs = $config;
    }

    public function Init() {
        $ShmList = ShmConfig::getStorageAvailableAddress($this->config); //从内存中获得可用列表
        $mapMasterToAlia = array();
        if (empty($ShmList)) {//内存中没有，可能ping脚本没启,直接用配置
            foreach ($this->config['nodes'] as $value) {
                $list[] = $value['master'];
                if (isset($value['master-alia'])) {
                    $mapMasterToAlia[$value['master']] = $value['master-alia'];
                }
            }
        } else {
            $list = $ShmList;
        }
        $this->mapMasterToAlia = $mapMasterToAlia;
        $this->targets = $list; //和cache不一样，失效后是false不能剔除
    }

    /*
     * 根据key和实际结点建立链接
     */

    public function ConnectTarget($key) {
        // 配置'consistent'来做算法选择判断，为true时，采用一致性hash，false则为随机选取，默认为true..
        if (! isset($this->config['consistent']) || $this->config['consistent'] == true) {
            $target = $this->hash($key);
        } else {
            $target = $this->random();
        }
        if (!$target) {//主从都down了
            return false;
        }
        $this->target = $target;
        $this->real_connect($target, $key);
        return $this->redis[$target];
    }

    /*
     * 取模打散
     */

    private function hash($key) {

        $hash = abs(crc32($key));
        $count = count($this->targets);
        $mod = $hash % $count;
        return isset($this->mapMasterToAlia[$this->targets[$mod]]) ? $this->mapMasterToAlia[$this->targets[$mod]] : $this->targets[$mod];
    }

    /**
     * 随机选择redis，适用于salve.
     *
     * @return string
     */
    private function random()
    {
        $pos = rand(0, count($this->targets) - 1);
        $target = $this->targets[$pos];

        if (isset($this->mapMasterToAlia[$target])) {
            $target = $this->mapMasterToAlia[$target];
        }

        return $target;
    }

    /**
    * 返回当前数据存入节点
    */
    protected function getConnectTarget($key)
    {
        return $this->hash($key);
    }

    /**
     * 返回所有的实际结点
     * @return array
     */
    protected function getAllTargets()
    {
        return $this->targets;
    }
}
