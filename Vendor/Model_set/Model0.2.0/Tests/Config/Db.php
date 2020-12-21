<?php
/**
 * Database 配置
 */
namespace Config;

define('JM_PHP_CONN_POOL_ON', "#{Res.php-connectionpool.global.enable}");

class Db{
    public $DEBUG = false;
    /**
     * available options are 1,2<br />
     * 1 log the SQL and time consumed;<br />
     * 2 logs including the traceback.<br />
     * <b>IMPORTANT</b><br />
     * please take care of option "confirm_link",when set as TRUE, each query will try to do an extra query to confirm that the link is still usable,this is mostly used in daemons.
     * @var INT
     */
    public $DEBUG_LEVEL = 1;
    public $read = array(
        'tuanmei'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3600
            )
        ),
        'stats'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei_stats',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3600
            )
        )
    );
    public $write = array(
        'tuanmei'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3600
            )
        ),
        'stats'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei_stats',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3600
            )
        )
    );
}
