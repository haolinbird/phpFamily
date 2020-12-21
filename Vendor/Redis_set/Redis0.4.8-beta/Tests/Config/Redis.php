<?php
/**
 * Redis 配置
 */
namespace Config;

#是否使用连接池(如果为false，则直连，否则可能使用新版或者旧版连接池)
if(!defined('JM_PHP_CONN_POOL_ON'))define('JM_PHP_CONN_POOL_ON', true);

#是否使用旧版连接池
if(!defined('JM_PHP_CONN_POOL_OLD_ON'))define('JM_PHP_CONN_POOL_OLD_ON', false);

class Redis{
    /**
     * Configs of Redis.
     * @var array
     */
    public $default = array('nodes' => array(
         array('master' => "127.0.0.1:6379", 'slave' => "127.0.0.1:6379", 'master-alia'=>'127.0.0.1:6381'),
         array('master' => "127.0.0.1:6380"),

    ),
        'db' => 0,
        'password' =>'password123456'
    );
    public $fav = array('nodes' => array(
        array('master' => '192.168.17.16:6379'),
    ),
        'db' => 10,
        'password' =>'password123456',
        'timeout' => 3,
    );
    public $testAuth = array('nodes' => array(
        array('master' => "127.0.0.1:6379", 'slave' => "127.0.0.1:6379"),
    ),
        'db' => 2,
        'password' =>'123456'
    );
}
