<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

/**
 * Database Sharding 配置
 */
namespace Config;

class DbSharding extends \Db\ConfigSchema {
	public $DEBUG = true;
	public $DEBUG_LEVEL = 3;
    //是否使用长连接
    protected $persistent = false;

    public  $globalDSN = array(
        "write" => "mysql:host=192.168.20.73:6603:1",
        "read" =>  "mysql:host=192.168.20.73:6603:1",
    );

    public $read = array(
        'user_0' => array(
            'dsn'          => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        'user_1' => array(
            'dsn'          => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        'user_2' => array(
            'dsn'          => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        'user_3' => array(
            'dsn'          => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
    );

    public $write = array(
         'user_0' => array(
            'dsn'         => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
             "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        'user_1' => array(
            'dsn'         => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        'user_2' => array(
            'dsn'         => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
        'user_3' => array(
            'dsn'         => '',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'user_address',
            'confirm_link' => true,
            'options'      => array(
                    MYSQLI_INIT_COMMAND        => "SET NAMES utf8",
                    MYSQLI_OPT_CONNECT_TIMEOUT => 3600,
            ),
        ),
    );

}


//print_r((array) new DbSharding());
