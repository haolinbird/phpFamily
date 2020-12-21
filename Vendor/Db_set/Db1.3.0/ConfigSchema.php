<?php
/**
 * ConfigSchema
 * 
 * @author junl<junl@jumei.com>
 */

namespace Db;

// key: #{ServiceName.LocalPool.Use} 是否使用本地连接池, 默认false:关闭本地连接池, 使用中间件, 如果设为true, 走本地连接池,但如果未安装本地连接池,则走直连数据库
//if(!defined('JM_PHP_MYSQL_LOCAL_POOL_ON')) define('JM_PHP_MYSQL_LOCAL_POOL_ON', false);

// key: #{ServiceName.ProxyPool.Use} 是否使用中间件连接池, 默认true,使用中间件; 如果设为false, 则走直连数据库
//if(!defined('JM_PHP_MYSQL_PROXY_POOL_ON')) define('JM_PHP_MYSQL_PROXY_POOL_ON', true);

/**
 * Config Schema Base Class.
 */

class ConfigSchema
{

    // #{Res.php-connectionpool.Proxy.XXXXDsn} 如果不使用本地连接池, 则使用全局中间件;可以在子类中重写
    public $globalDSN = array(
        'write' => "#{Res.php-connectionpool.Proxy.WriteDsn}",
        'read' => "#{Res.php-connectionpool.Proxy.ReadDsn}",
    );

    public $read = array();
    public $write = array();
    
    /**
     * Constructor.
     */
    public function __construct()
    {
        foreach ($this->read as $name => &$r) {
            // had parsed. or is template parsed.
            if (!isset($r['dsn'])) {
                if (self::isUseProxyPool()) {
                    foreach ($r as $k => &$v) {
                        $v['options'][\PDO::ATTR_PERSISTENT] = true;
                    }
                }
                continue;
            }
            if (self::isUseProxyPool()) {
                // 不使用本地连接池,使用全局连接池中间件的DSN
                $r['dsn'] = $this->globalDSN['read'];
                // 使用中间件时,建立PDO长连接, 提高性能
                $r['options'][\PDO::ATTR_PERSISTENT] = true;
            }
            $r = self::parseCfg($r);
        }

        foreach ($this->write as $name => &$w) {
            // had parsed. or is template parsed.
            if (!isset($w['dsn'])) {
                if (self::isUseProxyPool()) {
                    foreach ($w as $k => &$v) {
                        $v['options'][\PDO::ATTR_PERSISTENT] = true;
                    }
                }
                continue;
            }
            if (self::isUseProxyPool()) {
                // 不使用本地连接池,使用全局连接池中间件的DSN
                $w['dsn'] = $this->globalDSN['write'];
                // 使用中间件时,建立PDO长连接, 提高性能
                $w['options'][\PDO::ATTR_PERSISTENT] = true;
            }
            $w = self::parseCfg($w);
        }
    }

    /**
     * Is Use The Local Connection Pool or not.
     * 
     * @return bool $use default is false
     */
    public static function isUseLocalPool()
    {
        $use = false;
        if ((defined('JM_PHP_MYSQL_LOCAL_POOL_ON') && JM_PHP_MYSQL_LOCAL_POOL_ON === true) && extension_loaded('connect_pool')) {
            $use = true;
        }
        return $use;
    }

    /**
     * Is Use The Proxy Connection Pool or not.
     * 
     * @return bool $use default is true.
     */
    public static function isUseProxyPool()
    {
        if ((defined('JM_PHP_MYSQL_LOCAL_POOL_ON') && JM_PHP_MYSQL_LOCAL_POOL_ON === false) && 
            (!defined('JM_PHP_MYSQL_PROXY_POOL_ON') || JM_PHP_MYSQL_PROXY_POOL_ON === true)) {
            return true;
        }
        return false;
    }

    /**
     * 将配置中的DSN(负载均衡会配置多组ip:host、权重等)转成多条标准的pdo dsn格式,原始格式如："mysql:dbname=tuanmei_operation;host=192.168.20.71:9001:1,192.168.20.72:9001:1".
     * 
     * @param array $cfg 数据库的原始配置.
     * 
     * @return array $parsed 一个或多个包含标准dsn配置;除以上信息外还会保留原来的额外配置.
     * 
     * @throws \Exception  Invalid dsn.
     */
    public static function parseCfg($cfg)
    {
        // 没有配dsn，可能已经手动拆分好了各节点.
        if (!isset($cfg['dsn'])) {
            return $cfg;
        }
        $dsn = $cfg['dsn'];
        unset($cfg['dsn']);
        $mainPortions = explode(':', $dsn, 2);
        $driverType = $mainPortions[0];
        $dbPortions = explode(';', $mainPortions[1]);
        foreach ($dbPortions as $dbPortion) {
            $tempPortionPair = explode('=', $dbPortion);
            switch ($tempPortionPair[0]) {
                case 'dbname':
                    $dbname = $tempPortionPair[1];
                    continue;
                case 'host':
                    $hostRaw = $tempPortionPair[1];
                    continue;
                case 'port':
                    $defaultPort = $tempPortionPair[1];
            }
        }

        $dbname = !empty($cfg['db']) ? $cfg['db'] : (!empty($cfg['dbname']) ? $cfg['dbname'] : $dbname);

        if (!isset($dbname) || !isset($hostRaw)) {
            throw new \Exception('Invalid dsn to parse!');
        }
        $hosts = explode(',', $hostRaw);
        $parsed = array();
        foreach ($hosts as $host) {
            $hostPortions = explode(':', $host);
            $parsedDsn = $driverType . ':dbname=' . $dbname . ';host=' . $hostPortions[0] . ';';
            $port = isset($hostPortions[1]) && !empty($hostPortions[1]) ? $hostPortions[1] :
                    (isset($defaultPort) ? $defaultPort : 3306);
            $parsedDsn .= 'port=' . $port;
            $weight = isset($hostPortions[2]) ? $hostPortions[2] : 1;
            $parsed[] = array_merge($cfg, array('dsn' => $parsedDsn, 'db' => $dbname, 'weight' => $weight, 'host' => $hostPortions[0], 'port' => $port));
        }
        return $parsed;
    }

}

