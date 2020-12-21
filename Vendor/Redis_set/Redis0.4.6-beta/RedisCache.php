<?php

namespace Redis;

use MNLogger\TraceLogger;
use Exception;

/**
 * 当cache用的redis
 *
 * @author xinhuag@jumei.com
 */
class RedisCache extends RedisBase {
    /*
     * hash类的引用
     */

    private $hash;
    private $MasterOrSlave;
    protected static $configs;
    /**
    *配置名称
    */
    public $configName;

    /*
     * 单例
     */
    private static $instance;

    protected function __construct() {
        // 是否使用连接池
        if (defined('JM_PHP_CONN_POOL_ON')) {
            $this->usePool = (bool) JM_PHP_CONN_POOL_ON;
        }

        parent::__construct();
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

    public static function config($config = null) {
        if (empty($config)) {
            return static::$configs;
        }
        static::$configs = $config;
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

    public function Init() {
        $this->hash = new ConsistentHash();
        $this->MasterOrSlave = $ShmList = ShmConfig::getCacheAvailableAddress($this->config); //从内存中获得可用列表
        $list = array();
        $mapMasterToAlia = array();
        if (empty($ShmList)) {//内存中没有，可能ping脚本没启,直接用配置
            foreach ($this->config['nodes'] as $node) {
                $list[] = $node['master'];
                if (isset($node['master-alia'])) {
                    $mapMasterToAlia[$node['master']] = $node['master-alia'];
                }
            }
        } else {
            foreach ($ShmList as $node) {//false已过滤,主/从在逻辑上都hash主的值
                $list[] = $node['master']['target'];
            }
        }
        $this->mapMasterToAlia = $mapMasterToAlia;
        $this->hash->addTargets($list); //传入逻辑结点列表
    }

    /*
     * 根据key和实际结点建立链接
     */

    public function ConnectTarget($key) {
        $target = $this->hash->lookup($key);
        foreach ($this->MasterOrSlave as $node) {
            if (strcmp("slave", $node['use']) === 0 && strcmp($target, $node['master']['target']) === 0) {//因为缓存也做了主从，所以主挂了逻辑上可用，但是实际得用从
                $target = $node['slave']['target'];
            }
        }
        // 如果有节点映射，则使用映射节点
        $target = isset($this->mapMasterToAlia[$target]) ? $this->mapMasterToAlia[$target] : $target;
        $this->target = $target;
        $this->real_connect($target, $key);
        return $this->redis[$target];
    }

    /**
    * 返回当前数据存入节点
    */
    protected function getConnectTarget($key)
    {
        $target = $this->hash->lookup($key);
        return isset($this->mapMasterToAlia[$target]) ? $this->mapMasterToAlia[$target] : $target;
    }
    /**
     * 返回所有的实际结点
     * @return array
     */
    protected function getAllTargets()
    {
        return array_keys($this->hash->getVTargets());
    }
}
