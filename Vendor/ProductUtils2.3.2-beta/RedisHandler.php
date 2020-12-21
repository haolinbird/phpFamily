<?php
/**
 * Redis相关操作.
 *
 * @author quans<quans@jumei.com>
 */

namespace ProductUtils;

/**
 * Redis相关操作.
 */
class RedisHandler
{

    private static $instance;

    /**
     * 获取静态对象.
     * 
     * @return RedisHandler
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(static::$instance[$class])) {
            self::$instance[$class] = new self();
        }

        return self::$instance[$class];
    }

    /**
     * 根据配置获取redis配置.
     * 
     * @param string $config 配置项.
     * 
     * @return Redis
     */
    public static function redis($config)
    {
        // return self::getRandomNode($config);
        return \Redis\RedisMultiStorage::getInstance($config);
    }

    /**
     * 根据配置获取redis配置.
     *
     * @param string $config 配置项.
     *
     * @return Redis
     */
    public static function setRedisConfig($config)
    {
        // return self::getRandomNode($config);
        return \Redis\RedisMultiStorage::config($config);
    }

    /**
     * 随机获取一个节点.
     * 
     * @param string $config Redis配置.
     * 
     * @return array
     */
    public static function getRandomNode($config)
    {
        $redis = null;
        $redisConfig = (array)new \Config\Redis;
        if (isset($redisConfig[$config])) {
            if (!empty($redisConfig[$config]['nodes'])) {
                $count = count($redisConfig[$config]['nodes']);
                $info = $redisConfig[$config]['nodes'][rand(1, $count) - 1];
                $ipInfo = $info['master'];
                if (isset($info['master-alia'])) {
                    $ipInfo = $info['master-alia'];
                }
                $ipInfo = explode(":", $ipInfo);
                $redis = new \Redis();
                $redis->connect($ipInfo[0], $ipInfo[1]);

                if (isset($redisConfig[$config]['db'])) {
                    $redis->select($redisConfig[$config]['db']);
                }
            }
        }
        
        return $redis;
    }

    /**
     * 设置数据到redis中,HashTable形式.
     * 
     * @param string  $key           缓存key.
     * @param array   $hashTableData 缓存数据.
     * @param string  $redisConfig   Redis配置.
     * @param integer $time          设置过期时间.
     *
     * @return array
     */
    public function setHashTableData($key, $hashTableData, $redisConfig, $time = 0)
    {
        $redis = self::redis($redisConfig);
        $res = $redis->hmset($key, $hashTableData);
        if ($res && $time > 0) {
            $res = $redis->expire($key, $time);
        }
        return $res;
    }

    /**
     * 批量设置数据.
     *
     * @param array  $data        存储数据.
     * @param array  $times       缓存时间.
     * @param string $mode        数据类型.
     * @param string $redisConfig Redis配置.
     *
     * @return array
     */
    public function batchSetData($data, $times, $mode, $redisConfig)
    {
        /**
         * 参数说明.
         * $data : array('缓存key' => string/array)
         * $time : array('缓存key' => 过期时间)
         * $mode : string/hashTable
         */

        if (\ProductUtils\CartSetting::isStringModel($mode)) {
            return self::batchSetStringData($data, $times, $redisConfig);
        }

        if (\ProductUtils\CartSetting::isProtocModel($mode)) {
            return self::batchSetProtocData($data, $times, $redisConfig);
        }

        return self::batchSetHashTableData($data, $times, $redisConfig);
    }

    /**
     * 批量设置数据[购物车].
     *
     * @param array  $data        存储数据.
     * @param array  $times       缓存时间.
     * @param string $mode        数据类型.
     * @param array  $dataFrom    数据来源.
     * @param string $redisConfig Redis配置.
     *
     * @return array
     */
    public function batchSetDataForCart($data, $times, $mode, $dataFrom, $redisConfig)
    {
        /**
         * 参数说明.
         * $data : array('基础key' => string/array)
         * $time : array('基础key' => 过期时间)
         * $mode : string/hashTable/mix
         */
        $keyMap = self::genKeyAndCacheKeyRelationship($data, $mode, $dataFrom);
        $data = self::appendKeyForCart($data, $keyMap);
        $times = self::appendKeyForCart($times, $keyMap);
        $result = self::batchSetData($data, $times, $mode, $redisConfig);

        $flipKeyMap = array_flip($keyMap);
        return self::appendKeyForCart($result, $flipKeyMap);
    }

    /**
     * 批量获取数据[购物车].
     *
     * @param array  $keys        存储数据.
     * @param string $mode        数据类型.
     * @param array  $dataFrom    数据来源.
     * @param string $redisConfig Redis配置.
     *
     * @return array
     */
    public function batchGetDataForCart($keys, $mode, $dataFrom, $redisConfig)
    {
        /**
         * 参数说明.
         * $keys : array('基础key', '基础Key2')
         * $mode : string/hashTable/mix
         * $dataFrom : D/T
         */

        $data = array();
        $fileKeys = array_fill_keys($keys, '');
        $keyMap = self::genKeyAndCacheKeyRelationship($fileKeys, $mode, $dataFrom);
        if (\ProductUtils\CartSetting::isStringModel($mode) || \ProductUtils\CartSetting::isProtocModel($mode)) {
            $data = self::batachGetStringData($keyMap, $redisConfig);
        } else {
            $data = self::batchGetHashTableData($keyMap, $redisConfig);
        }

        $flipKeyMap = array_flip($keyMap);
        return self::appendKeyForCart($data, $flipKeyMap);
    }

    /**
     * 生成缓存Key和数据key的关系.
     *
     * @param array  $data     缓存数据.
     * @param string $mode     缓存数据类型.
     * @param string $dataFrom 数据来源.
     *
     * @return array
     */
    public function genKeyAndCacheKeyRelationship($data, $mode, $dataFrom)
    {
        $keyMap = array();
        if (empty($data)) {
            return $keyMap;
        }

        $func = $dataFrom == 'D' ? 'getCacheKey' : 'getSkuCacheKey';
        foreach ($data as $k => $v) {
            $key = \ProductUtils\CartSetting::$func($k, $mode);
            $keyMap[$k] = $key;
        }
        return $keyMap;
    }

    /**
     * 将过期时间附加为可直接放入redis.
     *
     * @param array  $data   存储数据.
     * @param string $keyMap 缓存Key和数据key的关系.
     *
     * @return array
     */
    public function appendKeyForCart($data, $keyMap)
    {
        $return = array();
        // D : 主数据, T : TPI数据.
        foreach ($data as $k => $v) {
            $return[$keyMap[$k]] = $v;
        }
        return $return;
    }

    /**
     * 批量设置hashTable数据.
     *
     * @param array  $hashTableDatas 存储数据.
     * @param array  $times          过期时间.
     * @param string $redisConfig    Redis配置.
     *
     * @return mixed
     */
    public function batchSetHashTableData($hashTableDatas, $times, $redisConfig)
    {
        // 这里只能采用脚本或者管道的形式.
        $redis = self::redis($redisConfig);
        $redis->pipeline();
        $result = array();
        foreach ($hashTableDatas as $k => $v) {
            $redis->hmset($k, $v);
            if (isset($times[$k]) && $times[$k] > 0) {
                $redis->expire($k, $times[$k]);
            }

        }

        $keys = array_keys($hashTableDatas);
        $data = $redis->exec();
        foreach ($keys as $k => $v) {
            $result[$v] = $data[$k];
        }
        return $result;

    }

    /**
     * 设置数据到redis中,Key/Value形式.
     * 
     * @param string  $key         缓存key.
     * @param array   $stringData  缓存数据.
     * @param string  $redisConfig Redis配置.
     * @param integer $time        过期时间.
     *
     * @return boolean
     */
    public function setStringData($key, $stringData, $redisConfig, $time = 0)
    {

        if ($time > 0) {
            return self::redis($redisConfig)->setex($key, $time, $stringData);
        }
        return self::redis($redisConfig)->set($key, $stringData);
    }

    /**
     * 批量设置缓存,string模式.
     *
     * @param array  $stringDatas 存储数据.
     * @param array  $times       时间.
     * @param string $redisConfig Redis配置.
     *
     * @return mixed
     */
    public function batchSetStringData($stringDatas, $times, $redisConfig)
    {

        $redis = self::redis($redisConfig);
        $redis->pipeline();
        $result = array();
        foreach ($stringDatas as $k => $v) {
            // $v = !empty($v) ? base64_encode(gzcompress($v)) : '';
            if (isset($times[$k]) && $times[$k] > 0) {
                $redis->setex($k, $times[$k], $v);
            } else {
                $redis->set($k, $v);
            }
        }

        $keys = array_keys($stringDatas);
        $data = $redis->exec();
        foreach ($keys as $k => $v) {
            $result[$v] = $data[$k];
        }
        return $result;
    }

    /**
     * 批量设置缓存,protoc模式.
     *
     * @param array  $stringDatas 存储数据.
     * @param array  $times       时间.
     * @param string $redisConfig Redis配置.
     *
     * @return mixed
     */
    public function batchSetProtocData($protocData, $times, $redisConfig)
    {
        return self::batchSetStringData($protocData, $times, $redisConfig);
    }

    /**
     * 批量获取redis中的数据,HashTbale格式,采用hgetAll.
     * 
     * @param array  $keys        获取的Key.
     * @param string $redisConfig Redis配置.
     * 
     * @return array
     */
    public function batchGetHashTableData($keys, $redisConfig)
    {
        // 这里只能采用脚本或者管道的形式.
        $redis = self::redis($redisConfig);
        $redis->pipeline();
        $result = array();
        $keys = array_values($keys);
        foreach ($keys as $k => $v) {
            $redis->hgetAll($v);
        }

        $data = $redis->exec();
        foreach ($keys as $k => $v) {
            $result[$v] = $data[$k];
        }
        return $result;
    }

    /**
     * 批量获取redis中的数据,HashTbale格式,采用Hmget.
     * 
     * @param array  $keys        获取的Key.
     * @param array  $keyGot      需要获取数据Key.
     * @param string $redisConfig Redis配置.
     * 
     * @return array
     */
    public function batchGetHashTableDataByKeys($keys, $keyGot, $redisConfig)
    {
        // 这里只能采用脚本或者管道的形式.
        $redis = self::redis($redisConfig);
        $redis->pipeline();
        $result = array();
        $keys = array_values($keys);
        foreach ($keys as $k => $v) {
            $redis->hMget($v, $keyGot[$v]);
        }

        $data = $redis->exec();
        foreach ($keys as $k => $v) {
            $result[$v] = $data[$k];
        }
        return $result;
    }

    /**
     * 批量获取redis中的数据,Key/Value数组形式.
     * 
     * @param array  $keys        获取的key.
     * @param string $redisConfig Redis配置.
     * 
     * @return array
     */
    public function batachGetStringData($keys, $redisConfig)
    {
        // 这里采用Mget的方式
        $result = array();
        $keys = array_values($keys);
        $data = self::redis($redisConfig)->mget($keys);
        foreach ($keys as $k => $v) {
            //$data[$k] = !empty($data[$k]) ? gzuncompress(base64_decode($data[$k])) : '';
            $result[$v] = $data[$k];
        }
        return $result;
    }

    /**
     * 批量删除Key.
     *
     * @param array  $keys        需要删除的Key.
     * @param string $redisConfig Redis配置.
     *
     * @return array
     */
    public function batchDeleteStringKeys($keys, $redisConfig)
    {
        /**
         * 参数说明.
         * $keys : array('缓存key1', '缓存Key2')
         */
        $redis = self::redis($redisConfig);
        return $redis->delete($keys);

    }

    /**
     * 批量删除Key.
     *
     * @param array  $keys        需要删除的Key.
     * @param string $redisConfig Redis配置.
     *
     * @return array
     */
    public function batchDeleteKeys($keys, $redisConfig)
    {
        /**
         * 参数说明.
         * $keys : array('缓存key1', '缓存Key2')
         */
        $result = array();
        if (empty($keys)) {

        }
        $redis = self::redis($redisConfig);
        if (is_string($keys)) {
            return array($keys => $redis->delete($keys));
        }

        if (!is_array($keys)) {
            return $result;
        }

        if (count($keys) == 1) {
            $keys = array_shift($keys);
            return array($keys => $redis->delete($keys));
        }

        $redis->pipeline();
        foreach ($keys as $k => $v) {
            $redis->delete($v);
        }

        $data = $redis->exec();
        foreach ($keys as $k => $v) {
            $result[$v] = $data[$k];
        }
        return $result;

    }

}
