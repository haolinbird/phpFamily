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

    //是否使用长连接
    protected $persistent = false;

    public  $globalDSN = array(
        "write" => "mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=tuanmei",
        "read" =>  "mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=tuanmei",
    );

     /**
      * @var array 机房配置 机房名=> [读写DSN]
      * @var key #{Res.MultiDC.Datacenter}
      */
    public $datacenter = array(
        "zw" => array(
            "read"=>"192.168.20.71:9001:1,192.168.20.71:9001:2",
            "write"=>"192.168.20.71:9001:3,192.168.20.71:9001:3",
        ),
        "mjq" => array(
            "read"=>"mjq:3306:1,mjq:3306:2",
            "write"=>"mjq:3306:3,mjq:3306:4",
        ),
    );
    
    /**
     * @var local 当前机房名字
     * @var notroute 只读写本地机房的库,表
     * @var map 数据库在哪些机房可读写,如果是多个机房,则会根据库分库下标对机房个数取模计算读写在哪一个机房；优先notroute配置
     * @var key #{Res.MultiDC.DB2DC}
     */
    public $db2dc = array(
        "map"   => array(
            "user" => array("zw"),
            "dbtest" => array("zw", "mjq"),
        ),
        "local" => "mjq",
        "notroute" => array(
            "dbtest",
        ),
    );
    
    public $read = array(
        't_fen_0' => array(
            'dsn'          => 'mysql:host=192.168.20.71:9001:1,192.168.20.71:9001:2,192.168.20.71:9001:3;port=3306;dbname=dbtest',
            'username'     => 'dev',
            'passwd'       => 'jmdevcd',
            "dbname"        => 'dbtest',
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
// var_dump(DbSharding::isUseLocalPool());
