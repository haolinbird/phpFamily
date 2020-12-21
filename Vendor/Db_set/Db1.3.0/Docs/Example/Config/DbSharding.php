<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

/**
 * Database Sharding 配置
 */
namespace Config;

// define('JM_PHP_MYSQL_LOCAL_POOL_ON', true);
// define('JM_PHP_MYSQL_PROXY_POOL_ON', true);
include("../../../ConfigSchema.php");

#{ServiceName.LocalPool.Use} 是否使用本地连接池, 默认false:关闭本地连接池, 使用中间件, 如果设为true, 走本地连接池,但如果未安装本地连接池,则走直连数据库
if(!defined('JM_PHP_MYSQL_LOCAL_POOL_ON')) define('JM_PHP_MYSQL_LOCAL_POOL_ON', "#{ServiceName.LocalPool.Use}");

// #{ServiceName.ProxyPool.Use} 是否使用中间件连接池, 默认true,使用中间件; 如果设为false, 则走直连数据库
if(!defined('JM_PHP_MYSQL_PROXY_POOL_ON')) define('JM_PHP_MYSQL_PROXY_POOL_ON', "#{ServiceName.ProxyPool.Use}");

class DbSharding extends \Db\ConfigSchema {

    public  $globalDSN = array(
        "write" => "mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=dbtest",
        "read" =>  "mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=dbtest",
    );

    public $read = array(
        't_fen_0' => array(
            'dsn'          => 'mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        't_fen_1' => array(
            'dsn'          => 'mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        't_fen_2' => array(
            'dsn'          => 'mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        't_fen_3' => array(
            'dsn'          => 'mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
    );

    public $write = array(
         't_fen_0' => array(
            'dsn'         => 'mysql:host=127.0.0.1;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        't_fen_1' => array(
            'dsn'         => 'mysql:host=127.0.0.1;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        't_fen_2' => array(
            'dsn'         => 'mysql:host=127.0.0.1;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        't_fen_3' => array(
            'dsn'         => 'mysql:host=127.0.0.1;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
    );

}


// print_r((array) new DbSharding());
var_dump(DbSharding::isUseLocalPool());
var_dump(DbSharding::isUseProxyPool());
