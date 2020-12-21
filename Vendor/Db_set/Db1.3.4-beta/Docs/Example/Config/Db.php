<?php
namespace Config;

class Db{
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
    public $DEBUG_LEVEL = 1;

    /**
     * Configs of database.
     * @var array
     */
    public $read = array(
        'stats'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei_stats',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        ),
        //lvs负载的配置方式
        'tuanmei'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        ),
        //客户端LB的配置方式(这样配 即采用了客户端负载)
       'tuanmei' => array(
           array(
               'dsn' => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei',
               'user' => 'dev',
               'password' => 'jmdevcd',
               'confirm_link' => true, //required to set to TRUE in daemons.
               'options' => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                   \PDO::ATTR_TIMEOUT => 3
               ),
               'weight' => 2
           ),
           array(
               'dsn' => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei',
               'user' => 'dev',
               'password' => 'jmdevcd',
               'confirm_link' => true, //required to set to TRUE in daemons.
               'options' => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                   \PDO::ATTR_TIMEOUT => 3
               ),
               'weight' => 1
           )
       ),
        
        
        'test' => array('dsn'      => 'mysql:host=127.0.0.1;port=3306;dbname=test',
            'user'     => 'root',
            'password' => '123456',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        ),
        'balance_test' => array('dsn'      => 'mysql:host=127.0.0.1,127.0.0.1:3306,127.0.0.1:3306:2,127.0.0.1::3;port=3306;dbname=test',
            'user'     => 'dev',
            'password' => '123456',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
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
        'tuanmei'=>array('dsn'      => 'mysql:host=192.168.20.71;port=9001;dbname=tuanmei',
            'user'     => 'dev',
            'password' => 'jmdevcd',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        )
         ,
        'test' => array('dsn'      => 'mysql:host=127.0.0.1;port=3306;dbname=test',
            'user'     => 'root',
            'password' => '123456',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        )
        ,
        'balance_test' => array('dsn'      => 'mysql:host=127.0.0.1,127.0.0.1:3306,127.0.0.1:3306:2,127.0.0.1::3;port=3306;dbname=test',
            'user'     => 'dev',
            'password' => '123456',
            'confirm_link' => true,//required to set to TRUE in daemons.
            'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                \PDO::ATTR_TIMEOUT=>3
            )
        )
    );
}
