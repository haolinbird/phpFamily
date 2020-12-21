<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

/**
 * Database Sharding 配置
 */
namespace Config;

 if(!defined('JM_PHP_MYSQL_POOL_ON')) define('JM_PHP_MYSQL_POOL_ON', "#{TrusteeshipData.Db.usepool}");
// if(!defined('JM_PHP_MYSQL_POOL_ON')) define('JM_PHP_MYSQL_POOL_ON', "on");
// if(!defined('JM_PHP_MYSQL_POOL_ON')) define('JM_PHP_MYSQL_POOL_ON', "off");
// if(!defined('JM_PHP_MYSQL_POOL_ON')) define('JM_PHP_MYSQL_POOL_ON', "1");
// if(!defined('JM_PHP_MYSQL_POOL_ON')) define('JM_PHP_MYSQL_POOL_ON', "0");
// if(!defined('JM_PHP_MYSQL_POOL_ON')) define('JM_PHP_MYSQL_POOL_ON', "true");
// if(!defined('JM_PHP_MYSQL_POOL_ON')) define('JM_PHP_MYSQL_POOL_ON', "false");




class DbSharding{
    // public $DEBUG = TRUE;
    // public $DEBUG_LEVEL = 1;

    // trans dsn info
    public function __construct()
    {
        $parseDsn = function($pdoDsn)
        {
            $dsnInfo = parse_url($pdoDsn);
            if ( empty($dsnInfo['scheme'])) {
                throw new \Exception("invalid dsn:$pdoDsn");
            }
            $pathInfo = explode(';', $dsnInfo['path']);
            foreach ($pathInfo as $p) {
                $itemInfo = explode('=', $p);
                if (count($itemInfo) != 2) {
                    throw new \Exception("invalid dsn:$pdoDsn");
                }
                $dsnInfo[$itemInfo[0]] = $itemInfo[1];
            }
            return $dsnInfo;
        };

        foreach ($this->read as $instanceName => $r) {
            $dsn =  $this->read[$instanceName]['dsn'];
            $dsnInfo = $parseDsn($dsn);
            $this->read[$instanceName]['db']  = $dsnInfo['dbname'];
            $this->read[$instanceName]['port']  = $dsnInfo['port'];
            $this->read[$instanceName]['host']  = $dsnInfo['host'];
            unset($this->read[$instanceName]['dsn']);
        }

        foreach ($this->write as $instanceName => $r) {
            $dsn =  $this->write[$instanceName]['dsn'];
            $dsnInfo = $parseDsn($dsn);
            $this->write[$instanceName]['db']  = $dsnInfo['dbname'];
            $this->write[$instanceName]['port']  = $dsnInfo['port'];
            $this->write[$instanceName]['host']  = $dsnInfo['host'];
            unset($this->write[$instanceName]['dsn']);
        }

    }


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
