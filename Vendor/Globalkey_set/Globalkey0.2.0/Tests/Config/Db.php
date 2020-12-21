<?php
/**
 * Database 配置
 */
namespace Config;

/**
 * 是否使用连接池。和配置系统集成后，可由配置管理后台统一控制启停。
 */
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

    public $write = array(
        'key_db' => array(
            'dsn'          => 'mysql:host=192.168.20.71;port=6001;dbname=key_db',
            'user'         => 'fuckorder',
            'password'     => 'jmordercd2013',
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 3600
            )
        )
    );
}
