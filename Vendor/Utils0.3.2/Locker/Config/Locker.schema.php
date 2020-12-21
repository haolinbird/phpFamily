<?php
namespace Config;

class Locker
{

    /**
     * 使用redis锁的配置.
     *
     * @var array
     */
    public static $redis = array(
        'prefix' => JM_APP_NAME, //锁的key前缀.
        'ttl' => 30, // 默认过期时间,30秒.
        'name' => 'default', // 加锁的Redis实例名称.
    );

}