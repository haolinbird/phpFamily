<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

namespace Config;

class Db extends \Db\ConfigSchema {

    //是否使用长连接
    protected $persistent = true;

    // #{Res.php-connectionpool.Proxy.XXXXDsn} 如果不使用本地连接池, 则使用全局中间件;可以在子类中重写
    public $globalDSN = array(
        'write' => 'mysql:host=172.20.4.48:16603,172.20.4.48:66603;dbname=',
        //'read' =>  'mysql:host=172.20.4.48:46603,172.20.4.48:56603;dbname='
        'read' =>  'mysql:host=192.168.16.31:9001,172.20.4.48:56603;dbname='
    );

    /**
     * 打印日志需要开启本项
     * @var bool
     */
    public $DEBUG = TRUE;
    /**
     * available options are 1,2<br />
     * 1 log the SQL and time consumed;<br />
     * 2 logs including the traceback.<br />
     * <b>IMPORTANT</b><br />
     * please take care of option "confirm_link",when set as TRUE, each query will try to do an extra query to confirm that the link is still usable,this is mostly used in daemons.
     * @var INT
     */
    public $DEBUG_LEVEL = 2;

    /**
     * Configs of database.
     * @var array
     */
    public $read = array(
        'stats' => array('dsn'      => 'mysql:host=192.168.20.72;port=9001;dbname=tuanmei_stats',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        ),
        'jumei' => array('dsn'      => 'mysql:host=192.168.20.72;port=9001;dbname=jumei',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        ),
        'key_db' => array(
            'dbname'      =>  'key_db',
            'dsn'          => 'mysql:host=192.168.20.71;port=6001;dbname=key_db',
            'user'     => 'fuckorder',
            'password' => 'jmordercd2013',
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 1
            )
        )
    );

    public $write = array(
        'stats'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei_stats',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        ),
        'jumei'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=jumei',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        ),
        'key_db' => array(
            'dbname'      =>  'key_db',
            'dsn'          => 'mysql:host=192.168.20.71;port=6001;dbname=key_db',
            'user'     => 'fuckorder',
            'password' => 'jmordercd2013',
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 1
            )
        )

    );

}
