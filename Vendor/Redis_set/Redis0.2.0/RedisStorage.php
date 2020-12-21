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

    /*
     * redis实例
     */
    private $redis = array();
    /*
     * config
     */
    public $config = array();

    private function __construct() {

    }

    /*
     * 关闭socket
     */

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
        if(!empty($failedTargets))
        {
            throw new \RedisException('Redis close error. last closing: '.$exc->getMessage().'. Failed targets: '.implode(',', $failedTargets));
        }
    }

    public static function getInstance($name = 'default') {
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = new self;
        }

        if(!static::$configs)
        {
            static::$configs = new \Config\Redis();
        }

        if(!self::$instance[$name]->config)
        {
            self::$instance[$name]->config = static::$configs[$name];
        }
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
        if (empty($ShmList)) {//内存中没有，可能ping脚本没启,直接用配置
            foreach ($this->config['nodes'] as $value) {
                $list[] = $value['master'];
            }
        } else {
            $list = $ShmList;
        }
        $this->targets = $list; //和cache不一样，失效后是false不能剔除
    }

    /*
     * 根据key和实际结点建立链接
     */

    public function ConnectTarget($key) {
        $this->target = $target = $this->hash($key);
        if (!$target) {//主从都down了
            return false;
        }
        if (!isset($this->redis[$target])) {//每个物理机对应一个new redis
            $this->redis[$target] = new \Redis();
            $ip_port = explode(":", $target);
            $logger = TraceLogger::instance('trace');
            if (false === $this->redis[$target]->connect($ip_port[0], $ip_port[1], 3)) {
                if (false === $this->redis[$target]->connect($ip_port[0], $ip_port[1], 3)) {
                    $logger->REDIS_CR("EXCEPTION", "connect redis error");
                    unset($this->redis[$target]);
                    $e = new Exception("Connect redis error!\nKey: " . $key . "\nTarget:" . $target . "\nDB:" . $this->config['db']);
                    $ex_logger = EXLogger::instance('exception');
                    $ex_logger->log($e);
                    throw $e;
                }
            }
                if (isset($this->config['db'])) {//如果设置了db
                    $this->redis[$target]->select($this->config['db']);
                }
        }

        return $this->redis[$target];
    }

    /*
     * 取模打散
     */

    private function hash($key) {

        $hash = abs(crc32($key));
        $count = count($this->targets);
        $mod = $hash % $count;
        return $this->targets[$mod];
    }

}
