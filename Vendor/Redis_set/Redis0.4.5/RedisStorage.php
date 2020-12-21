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
    /*
     * 目标物理结点
     */

    private $targets;

    /*
     * 单例
     */
    private static $instance;
    protected static $configs;

    /**
    *配置名称
    */
    public $configName;

    protected function __construct() {
        // 是否使用连接池
        if (defined('JM_PHP_CONN_POOL_ON')) {
            $this->usePool = (bool) JM_PHP_CONN_POOL_ON;
        }

        // 是否使用老的连接池
        if (defined('JM_PHP_CONN_POOL_OLD_ON')) {
            $this->useOldPool = (bool) JM_PHP_CONN_POOL_OLD_ON;
        }
        parent::__construct();
    }

    /*
     * 关闭socket
     */

    public function close() {
        $failedTargets = array();
        foreach ((array) $this->redis as $target => $value) {
            try {
                unset($this->redis[$target]);
                $this->_isUseOldPool() ? $value->release() : $value->close();
            } catch (Exception $exc) {
                $failedTargets[] = $target;
            }
        }
        if (!empty($failedTargets)) {
            throw new \RedisException('Redis close error. last closing: ' . $exc->getMessage() . '. Failed targets: ' . implode(',', $failedTargets));
        }
    }

    public static function getInstance($name = 'default') {
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = new self;
        }

        if (!static::$configs) {
            static::$configs = (array) new \Config\Redis();
        }

        if (!self::$instance[$name]->config) {
            self::$instance[$name]->config = static::$configs[$name];
//            self::$instance[$name]->config = static::$configs;
        }
        self::$instance[$name]->configName = $name;
        self::$instance[$name]->Init();
        return self::$instance[$name];
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
