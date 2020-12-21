<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */


/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

namespace Config;

class Db extends \Db\ConfigSchema {
    /**
     * @var bool 是否关闭使用连接池代理的功能.默认不关闭.
     */
    public $disableConnectionPoolProxy = true;

    //是否使用长连接
    protected $persistent = true;
    public $globalDSN = array(
        'write' => 'mysql:host=192.168.20.73:6603,192.168.20.73:6603;dbname=',
        //'read' =>  'mysql:host=172.20.4.48:46603,172.20.4.48:56603;dbname='
        'read' =>  'mysql:host=192.168.20.73:6603,192.168.20.73:6603;dbname='
    );

    public $DEBUG = true;
    public $DEBUG_LEVEL = 1;
    public $read = array(
        'ankerbox_work' => array(
            'db' => 'jd_work_documents_20181210_tmp',
            'dsn' => 'mysql:host=10.40.30.15;port=6008;dbname=jd_work_documents',

            'confirm_link' => true, //required to set to TRUE in daemons.
            'user' => 'jd_workdoc_srd',
            'password' => 'oJrE8PLC',
            'options' => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
        'jiedian_userlib' => array(
            'dsn' => 'mysql:host=10.40.30.20;port=6006;dbname=jiedian_userlib',
            'confirm_link' => true, //required to set to TRUE in daemons.
            'user' => 'jd_workdoc_srd',
            'password' => 'oJrE8PLC',
            'options' => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
    );
    public $write = array(
        'ankerbox_work' => array(
            'dsn' => 'mysql:host=172.21.16.9;port=6008;dbname=jd_work_documents',
            'db' => 'jd_work_documents_20181210_tmp',
            'confirm_link' => true, //required to set to TRUE in daemons.
            'user' => 'jd_workdoc_swd',
            'password' => 'P14e4g3H',
            'options' => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 3
            )
        ),
    );

    public function __construct()
    {
        $this->parseDsn($this->read);
        $this->parseDsn($this->write);
        parent::__construct();
    }

    protected function parseDsn(&$instance)
    {
        array_walk(
            $instance,
            function (&$v) {

                if (!array_key_exists('dsn', $v)) {
                    return;
                }
                // 解析DSN数据
                $dsn = trim($v['dsn']);
                $matches = array();
                if (preg_match('/dbname=([^;]+)$/', $dsn, $matches)) {
                    $v['db'] = trim($matches[1]);
                }
            }
        );
    }

}
