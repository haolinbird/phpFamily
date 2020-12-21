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
    public $configList;
    public $configInfo;
    public $configCnt;
    
    protected $configBaseCnt = 15;//预定义redis配置连接数
    protected $hNum = 10000;//计算hash值基数
    /*
     * 配置
     */
    public $config;
    /*
     * redis实例
     */
    public $redis;
    /*
     * 单例
     */
    private static $instance;

    protected function __construct() {
        if (defined('JM_PHP_CONN_POOL_ON')) {
            $this->usePool = (bool) JM_PHP_CONN_POOL_ON;
        };
        parent::__construct();
    }

    public static function getInstance($name = 'default') {
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = new self;
        }

        if (!static::$configs) {
            static::$configs = new \Config\Redis();
        }

        if (!self::$instance[$name]->config) {
//            self::$instance[$name]->config = static::$configs;
            self::$instance[$name]->config = static::$configs[$name];
        }
        self::$instance[$name]->Init();
        return self::$instance[$name];
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
                $this->usePool ? $value->release() : $value->close();
            } catch (Exception $exc) {
                $failedTargets[] = $target;
            }
        }
        if (!empty($failedTargets)) {
            throw new \RedisException('Redis close error. last closing: ' . $exc->getMessage() . '. Failed targets: ' . implode(',', $failedTargets));
        }
    }

    public function Init() {
        $this->MasterOrSlave = $ShmList = ShmConfig::getCacheAvailableAddress($this->config); //从内存中获得可用列表
        
        $this->configCnt = 0;
        if (empty($ShmList)) {//内存中没有，可能ping脚本没启,直接用配置
            foreach ($this->config['nodes'] as $node) {
                $this->configList[]= $node['master'];
                $this->configCnt ++;
            }
        } else {
            foreach ($ShmList as $node) {//false已过滤,主/从在逻辑上都hash主的值
                $this->configList[] = $node['master']['target'];
                $this->configCnt ++;
            }
        }
    }

    /*
     * 根据key和实际结点建立链接
     */

    public function ConnectTarget($key) {
        $target = $this->getHashTarget($key);
        $this->target = $target;
        $this->real_connect($target, $key);
        return $this->redis[$target];
    }
    
    /**
     * 新的获取target算法
     * 
     * @param string $key key.
     * @return string
     */
    public function getHashTarget($key){
        $hid = 0;
        $nNum = hexdec(substr(md5($key), -6, 6)) % $this->hNum; //当前概率,用hNum计算,精度更高
        $bNum = $this->hNum / $this->configBaseCnt; //基础概率
        if ($this->configBaseCnt == $this->configCnt) {
            //这种情况直接取余得到$hid
            for ($i = 0; $i <= $this->configBaseCnt; $i++) {
                if ($nNum < (($i + 1) * $bNum)) {
                    $hid = $i;
                    break;
                }
            }
        } elseif ($this->configCnt > $this->configBaseCnt) {
            //实际配置数大于预计配置数
            $oNum = $bNum - $this->hNum / $this->configCnt; //要分流出来的概率
            for ($i = 0; $i <= $this->configBaseCnt; $i++) {
                if ($nNum <= ($i * $bNum + $oNum) && $nNum >= ($i * $bNum)) {
                    $hid = $this->configBaseCnt + $nNum % ($this->configCnt - $this->configBaseCnt); //计算分流到的hid,并跳出
                    break;
                } else if ($nNum < (($i + 1) * $bNum)) {
                    $hid = $i;
                    break;
                }
            }
            $hid = $hid ? $hid : intval($nNum / $this->hNum * 10); //没取到,表示不用分流,直接取得对应的hid
        } else {
            //实际配置数小于预计配置数
//            return hexdec(substr(md5($key), -5, 5));
            $hid = $nNum >= $bNum * $this->configCnt ? $nNum % $this->configCnt : intval($nNum / $bNum); //在要分流的概率中,则取余当前连接数,否则不用分流,直接取得对应的hid
        }
        return $this->configList[$hid];
    }

}
