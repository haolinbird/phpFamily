<?php
class ConnectionTest extends  \PHPUnit_Framework_TestCase{
    /**
     * @param $orgin
     * @param $parsed
     * @dataProvider cfgData
     */
    public function testParseCfg($orgin, $parsed)
    {
        $myParsed = \Db\Connection::parseCfg($orgin);
        $this->assertEquals($myParsed, $parsed);
    }

    public function cfgData()
    {
        return array(
            // 第一组数据(来自配置系统)：需要被解析
            array(array('dsn'      => 'mysql:dbname=tuanmei_operation;host=192.168.20.71:9001:2,192.168.20.72:9001:1',
                'user'     => 'dev',
                'password' => 'jmdevcd',
                'confirm_link' => true,//required to set to TRUE in daemons.
                'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                    \PDO::ATTR_TIMEOUT=>3
                )
                )
                ,
                array(
                    array('dsn'      => 'mysql:dbname=tuanmei_operation;host=192.168.20.71;port=9001',
                    'weight' => '2',
                    'host'  => '192.168.20.71',
                    'port' => '9001',
                    'user'     => 'dev',
                    'password' => 'jmdevcd',
                    'confirm_link' => true,//required to set to TRUE in daemons.
                    'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                        \PDO::ATTR_TIMEOUT=>3
                    )
                    )
                    ,
                    array('dsn'      => 'mysql:dbname=tuanmei_operation;host=192.168.20.72;port=9001',
                        'weight' => '1',
                        'host'  => '192.168.20.72',
                        'port' => '9001',
                        'user'     => 'dev',
                        'password' => 'jmdevcd',
                        'confirm_link' => true,//required to set to TRUE in daemons.
                        'options'  => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                            \PDO::ATTR_TIMEOUT=>3
                        )
                    )
                )
            )
            ,
            // 第二组数据（标准形式的配置）: 不需要被解析
            array(
                array(
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
                        'dsn' => 'mysql:host=192.168.20.72;port=9001;dbname=tuanmei',
                        'user' => 'dev',
                        'password' => 'jmdevcd',
                        'confirm_link' => true, //required to set to TRUE in daemons.
                        'options' => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                            \PDO::ATTR_TIMEOUT => 3
                        ),
                        'weight' => 1
                    )
                ),
                array(
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
                        'dsn' => 'mysql:host=192.168.20.72;port=9001;dbname=tuanmei',
                        'user' => 'dev',
                        'password' => 'jmdevcd',
                        'confirm_link' => true, //required to set to TRUE in daemons.
                        'options' => array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'latin1\'',
                            \PDO::ATTR_TIMEOUT => 3
                        ),
                        'weight' => 1
                    )
                )
            )
        );
    }
} 