<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

/**
 * Database Sharding 配置
 */
namespace Config;

if(!defined('JM_PHP_MYSQL_PROXY_POOL_ON')) define('JM_PHP_MYSQL_PROXY_POOL_ON', true);

if(!defined('JM_PHP_MYSQL_LOCAL_POOL_ON')) define('JM_PHP_MYSQL_LOCAL_POOL_ON', false);

class DbSharding extends \Db\ConfigSchema
{
    // public $DEBUG = TRUE;
    // public $DEBUG_LEVEL = 1;
    /**
     * @var bool 是否关闭使用连接池代理的功能.默认不关闭.
     */
    public $disableConnectionPoolProxy = true;
    protected $persistent = true;

    public $globalDSN   = array(
        'write' => 'mysql:host=172.20.4.48:46603,172.20.4.48:56603;dbname=',
        'read'  => 'mysql:host=172.20.4.48:26603,172.20.4.48:36603;dbname='
    );

    public $read = array(
        'trusteeship_data_0' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6001;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
        'trusteeship_data_1' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6002;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
        'trusteeship_data_2' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6003;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
        'trusteeship_data_3' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6004;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
    );

    public $write = array(
         'trusteeship_data_0' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6001;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
        'trusteeship_data_1' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6002;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
        'trusteeship_data_2' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6003;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
        'trusteeship_data_3' => array(
            'dsn'          => 'mysql:host=192.168.20.72;port=6004;dbname=encrypt',
            'db'           => 'jumei_encrypt',
            'username'     => 'fuckorder',
            'passwd'       => 'jmordercd2013',
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                \PDO::ATTR_TIMEOUT => 3
            ),
        ),
    );

}
