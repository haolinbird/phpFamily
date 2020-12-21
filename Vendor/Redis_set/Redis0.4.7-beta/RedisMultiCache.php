<?php
namespace Redis;
use Config\Redis;

/**
 * Description of RedisMultiStorage
 *
 * @author guoxinhua
 */
class RedisMultiCache {

    public static $instance;
    public static $config;

    /**
     * @param $name 配置名称
     * @return \Redis\RedisCache
     */
    public static function getInstance($name) {
        if(!static::$config) {
            static::$config = (array) new \Config\Redis();
            RedisCache::config(static::$config);
        }
        $insKey = RedisBase::getInsKey($name);
        if (!isset(static::$instance[$insKey])) {
            static::$instance[$insKey] = RedisCache::getInstance($name);
        }
        return static::$instance[$insKey];
    }

    public static function config(array $config) {
        static::$config = $config;
        RedisCache::config(static::$config);
    }

    public static function close(){
        $closeExMsg = null;
        foreach ((array)static::$instance as $inst) {
            try {
                $inst->close();
            } catch(\Exception $ex) {
                $closeExMsg[] = $ex->getMessage();
            }
        }
        if($closeExMsg) {
            throw new \RedisException(implode("\n", $closeExMsg), 2, $ex);
        }
        static::$instance = array();
    }

}
