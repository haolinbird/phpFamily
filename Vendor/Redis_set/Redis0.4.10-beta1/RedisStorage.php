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
    /**
     * 物理节点
     * @var array
     */
    private $targets;

    /**
     * 共享内存管理
     *
     * @var \SharedMemory\HaConfig
     */
    protected $haConfigManager;

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
        $this->targets = array();
        foreach ($this->config['nodes'] as $value) {
            if(isset($value['master-alia-ha'])) {
                $r_node_str = $value['master-alia-ha'];
            } elseif (isset($value['master-alia'])) {
                $r_node_str = $value['master-alia'];
            } else {
                $r_node_str = $value['master'];
            }

            $r_nodes = explode(',', $r_node_str);
            $configs = array();
            foreach($r_nodes as $r_node) {
                $configs[] = $this->haConfigManager->formatAddress($r_node);
            }
            $this->targets[] = $configs;
        }

        /*是否需要排序(兼容其他语言算法)*/
        if (isset($this->config['sort_before_hash']) && $this->config['sort_before_hash'] == true) {
            sort($this->targets);
        }
    }

    /*
     * 根据key和实际结点建立链接
     */
    public function ConnectTarget($key) {
        // 配置'consistent'来做算法选择判断，为true时，使用hash散列，false则为随机选取，默认为true..
        if (! isset($this->config['consistent']) || $this->config['consistent'] == true) {
            $configs = $this->hash($key);
        } else {
            $configs = $this->random();
        }

        $tryCount = count($configs);
        $expMsgs = '';
        for ($i = 0; $i < $tryCount; $i++) {
            $config = $this->haConfigManager->findOneConfig($configs);
            try {
                $isConnected = false;
                $target = "{$config['host']}:{$config['port']}";
                $this->target = $target;
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
     * 取模算法
     * @param $key
     *
     * @return string
     */
    private function hash($key) {
        $hash = abs(crc32($key));
        $count = count($this->targets);
        return $this->targets[$hash % $count];

    }

    /**
     * 随机选择redis，适用于salve.
     *
     * @return string
     */
    private function random() {
        $pos = rand(0, count($this->targets) - 1);
        return $this->targets[$pos];
    }

    /**
     * 从一组节点中获取一个可用的
     */
    private function getOneNode($nodes) {

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
