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
    /**
     * 一致性hash对象
     *
     * @var ConsistentHash
     */
    private $hash;

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
        $list = array();
        $this->mapMasterToAlia = array();

        foreach ($this->config['nodes'] as $node) {
            $list[] = $node['master'];
            if (isset($node['master-alia-ha'])) {
                $r_node_str = $node['master-alia-ha'];
            } elseif (isset($node['master-alia'])) {
                $r_node_str = $node['master-alia'];
            } else {
                $r_node_str = $node['master'];
            }

            // r_nodes mean real nodes, 真实的节点
            $r_nodes = explode(',', $r_node_str);
            $configs = array();
            foreach($r_nodes as $r_node) {
                $configs[] = $this->haConfigManager->formatAddress($r_node);
            }
            $this->mapMasterToAlia[$node['master']] = $configs;
        }

        $this->hash->addTargets($list);
    }

    /*
     * 根据key和实际结点建立链接
     */

    public function ConnectTarget($key) {
        $vnode = $this->hash->lookup($key);
        $tryCount = count($this->mapMasterToAlia[$vnode]);
        $expMsgs = '';
        for ($i = 0; $i < $tryCount; $i++) {
            $config = $this->haConfigManager->findOneConfig($this->mapMasterToAlia[$vnode]);
            try {
                $isConnected = false;
                $target = "{$config['host']}:{$config['port']}";
                $isConnected = $this->real_connect($target, $key);
                break;
            } catch (\Exception $e) {
                $expMsgs .= $e->getMessage() . PHP_EOL;
                $this->haConfigManager->kickConfig($config);
                $this->close();
            }
        }

        if ($isConnected !== true) {
            throw new \Exception($expMsgs);
        }

        return $this->redis[$target];
    }

    /**
    * 返回当前数据存入节点
    */
    protected function getConnectTarget($key) {
        $target = $this->hash->lookup($key);
        return isset($this->mapMasterToAlia[$target]) ? $this->mapMasterToAlia[$target] : $target;
    }

    /**
     * 返回所有的实际结点
     * @return array
     */
    protected function getAllTargets() {
        return array_keys($this->hash->getVTargets());
    }
}
