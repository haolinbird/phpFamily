<?php

/**
 * 日志组件配置文件
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2020-12-24 10:28:30
 */

namespace Module;

use Elasticsearch\ClientBuilder;

/**
 * ModuleBase.
 */
abstract class ModuleBase
{
    /**
     *
     * Instances of the derived classes.
     * @var array
     */
    protected static $instances = array();

    /**
     * Get instance of the derived class.
     *
     * @return static
     */
    public static function instance()
    {
        $className = get_called_class();
        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className();
        }
        return self::$instances[$className];
    }

    /**
     * 魔术方法 ，用来访问redis或其他方法.
     *
     * @param string $name 参数名.
     *
     * @return $mix       相关对象.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'redis':
                return $this->redis();
                break;
            default:
                trigger_error('try get undefined property: ' . $name . ' of class ' . __CLASS__, E_USER_NOTICE);
                break;
        }
    }

    /**
     * Get a redis instance.
     *
     * @param string $endpoint Connection configruation name.
     * @param string $as       Use redis as "cache" or storage.default: storage.
     *
     * @return \RedisCache|\RedisStorage
     */
    public function redis($endpoint = 'default', $as = 'storage')
    {
        if ($as == 'storage') {
            return \Redis\RedisMultiStorage::getInstance($endpoint);
        } else {
            return \Redis\RedisMultiCache::getInstance($endpoint);
        }
    }

    /**
     * 记录日志.
     *
     * @param string $message  内容.
     * @param string $category 类别.
     *
     * @return void
     */
    public function log($message, $category = '')
    {
        $path = ROOT_PATH . "logs/" . $category;
        if (! file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $filename = date('Ymd');
        $file     = $path . "/{$filename}.log";
        $message  = date('Y-m-d H:i:s') . "\t $message \n";
        error_log($message, 3, $file);
    }

    /**
     * http get请求.
     *
     * @param string $url    链接地址.
     * @param array  $get    参数数组.
     * @param array  $header 头部参数.
     * @return boolean|mixed
     */
    public function get($url, $get = array(), $header = array())
    {
        return $this->httpRequest($url, $get, array(), $header);
    }

    /**
     * http post 请求.
     * @param string $url    链接地址.
     * @param array  $post   参数数组.
     * @param array  $header 头部参数.
     * @return boolean|mixed
     */
    public function post($url, $post, $header = array())
    {
        return $this->httpRequest($url, array(), $post, $header);
    }

    /**
     * 执行http/https请求.
     * @param string $url    链接地址.
     * @param array  $get    参数数组.
     * @param array  $post   参数数组.
     * @param array  $header 头部参数.
     * @return false
     */
    public function httpRequest($url, $get = array(), $post = array(), $header = array())
    {
        if (!empty($get)) {
            $getString = http_build_query($get);
            $url .= '?' . $getString;
        }

        $ch = curl_init($url);
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if (!empty($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200 || curl_error($ch) != '') {
            $result = false;
        } else {
            $result = $response;
        }
        curl_close($ch);

        return $result;
    }

    /**
     * 设置 Redis 分布式锁.
     *
     * @param string  $prefix      锁前缀.
     * @param string  $key         锁的key.
     * @param integer $expireAfter 多久过期,默认为0,即300秒(5分钟)后过期.
     *
     * @return boolean 锁定成功返回true,锁定失败返回false.
     */
    public function setLock($prefix, $key, $expireAfter = 0)
    {
        $timestamp = time();
        $expireAfterSeconds = $expireAfter > 0 ? $expireAfter : 300;
        $expireAt = $timestamp + $expireAfterSeconds + 1;
        $redisKey = $prefix . $key;

        if ($this->redis('default')->set($redisKey, $expireAt, ["NX", "EX" => $expireAfter])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 释放 Redis 锁.
     *
     * @param string $prefix 锁前缀.
     * @param string $key    锁的key.
     *
     * @return boolean 解锁成功返回true,解锁失败返回false.
     */
    public function delLock($prefix, $key)
    {
        $redisKey = $prefix . $key;
        return $this->redis('default')->del($redisKey);
    }
}
