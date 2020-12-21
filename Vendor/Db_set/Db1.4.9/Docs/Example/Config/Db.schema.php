<?php
namespace Config;


class Db extends \Db\ConfigSchema {

    public $DEBUG = "#{servicename.db.debug}";
    public $DEBUG_LEVEL = 1;

    //是否使用长连接
    protected $persistent = "#{Res.php-connectionpool.Proxy.Persistent}";

    // #{Res.php-connectionpool.Proxy.XXXXDsn} 
    // 如果不使用本地连接池(JM_PHP_MYSQL_LOCAL_POOL_ON !== true), 则使用全局中间件;可以在子类中重写;也可以不定义, 使用父类中的值.
    public $globalDSN = array(
        'write' => "#{Res.php-connectionpool.Proxy.WriteDsn}",
        'read' => "#{Res.php-connectionpool.Proxy.ReadDsn}",
    );


    /**
     * Configs of database.
     * @var array
     */
    public $read = array(
        'jumei_cart' => array(
            'dsn' => "#{Res.Database.JumeiCart.Read.Dsn}",
            'dbname' => "jumei_cart",
            'user' => "#{payment-service.db.jumeicart.read.user}",
            'password' => "#{payment-service.db.jumeicart.read.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
        'default' => array(
            'dsn' => "#{Res.Database.Tuanmei.lvs122.read.Dsn}",
            'dbname' => "tuanmei",
            'user' => "#{payment-service.db.tuanmei.read.user}",
            'password' => "#{payment-service.db.tuanmei.read.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
        /* 汇总库 */
        'summary' => array(
            'dsn' => "#{Res.Database.Jumeiorders.10054.read.Dsn}",
            'dbname' => "jumei_orders",
            'user' => "#{payment-service.db.summary.user}",
            'password' => "#{payment-service.db.summary.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
        'jumei_global' => array(
            'dsn' => "#{Res.Database.Jmglobal.Read.Dsn}",
            'dbname' => "jumei_global",
            'user' => "#{payment-service.db.jumeiglobal.read.user}",
            'password' => "#{payment-service.db.jumeiglobal.read.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        )
    );
    public $write = array(
        'jumei_cart' => array(
            'dsn' => "#{Res.Database.JumeiCart.Write.Dsn}",
            'dbname' => "jumei_cart",
            'user' => "#{payment-service.db.jumeicart.write.user}",
            'password' => "#{payment-service.db.jumeicart.write.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
        'default' => array(
            'dsn' => "#{Res.Database.Tuanmei.master.Write.Dsn}",
            'dbname' => "tuanmei",
            'user' => "#{payment-service.db.tuanmei.write.user}",
            'password' => "#{payment-service.db.tuanmei.write.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
        'key_db' => array(
            'dsn' => "#{Res.Database.KeyDb.Write.Dsn}",
            'dbname' => "keydb",
            'user' => "#{payment-service.db.keydb.user}",
            'password' => "#{payment-service.db.keydb.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
        'lbBalance' => array(
            'dsn'      => 'mysql:host=10.0.12.59;port=6006;dbname=liebianyue_info',
            'dbname' => "liebianyue_info",
            'user'     => 'liebianyue_info',
            'password' => 'liebianyue_pass',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                    \PDO::ATTR_TIMEOUT=> 3
            )
        ),
        'jumei_global' => array(
            'dsn' => "#{Res.Database.Jmglobal.Write.Dsn}",
            'dbname' => "jumei_global",
            'user' => "#{payment-service.db.jumeiglobal.write.user}",
            'password' => "#{payment-service.db.jumeiglobal.write.pass}",
            'confirm_link' => true, //required to set to TRUE in daemons.
            'options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        )
    );

}
