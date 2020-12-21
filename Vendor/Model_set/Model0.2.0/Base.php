<?php
/**
 * Base of all models.
 *
 * @author Su Chao<chaos@jumei.com>
 */

namespace Model;

/**
 * Abstract model,included commond methods for data access and manipulations for derived classes.
 * @uses Db\Connection
 */
abstract class Base{
    /**
     *
     * Instances of the derived classes.
     * @var array
     */
    protected static $instances = array();

    /**
     * Get instance of the derived class.
     *
     * @param bool $noSingleton 是否获取单件。默认 true.
     *
     * @return static
     */
    public static function instance($singleton=true)
    {
        $className = get_called_class();
        if(!$singleton)
        {
            return new $className;
        }
        if (!isset(self::$instances[$className]))
        {
            self::$instances[$className] = new $className;
        }
        return self::$instances[$className];
    }


    /**
     * Get a redis instance.
     *
     * @param string $endpoint connection configruation name.
     * @param string $as use redis as "cache" or storage. default: storage
     * @return \Redis\RedisCache
     * @throws \Model\Exception
     */
    public function redis($endpoint = 'default', $as='storage')
    {
        if($as === 'storage')
        {
            $className = '\Redis\RedisMultiStorage';
        }
        else if($as === 'cache')
        {
            $className = '\Redis\RedisMultiCache';
        }
        else
        {
            throw new Exception('Redis instance can only be "as" "cache" or "storage".');
        }
        return $className::getInstance($endpoint);
    }
}
