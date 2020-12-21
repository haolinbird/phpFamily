<?php
/**
 * Google Protocbuf压缩相关操作.
 */

namespace ProductUtils;

/**
 * Google Protocbuf压缩操作类.
 */
class ProtocHandler
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
     * 获取protoc获取数据方法.
     *
     * @param string $key   字段.
     * @param string $split 分割符.
     *
     * @return string
     */
    public static function getProtocGetFunc($key, $split = null)
    {
        return 'get' . self::getSuffixOfFunc($key, $split);
    }

    /**
     * 获取protoc设置数据方法.
     *
     * @param string $key   字段.
     * @param string $split 分割符.
     *
     * @return string
     */
    public static function getProtocSetFunc($key, $split = null)
    {
        return 'set' . self::getSuffixOfFunc($key, $split);
    }

    /**
     * 获取protoc设置数据方法.
     *
     * @param string $key   字段.
     * @param string $split 分割符.
     *
     * @return string
     */
    public static function getSuffixOfFunc($key, $split = null)
    {
        // PHP version > 5.4.32
        // return str_replace('_', '', ucwords($key, $split));
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
    }

}