<?php
/**
 * ConfigSchema
 *
 * @author junl<junl@jumei.com>
 */
namespace Db;

/**
 * Config Schema Base Class.
 */
class ConfigSchema
{

    //是否使用长连接
    protected $persistent = "#{Res.php-connectionpool.Proxy.Persistent}";

    // #{Res.php-connectionpool.Proxy.XXXXDsn} 如果不使用本地连接池, 则使用全局中间件;可以在子类中重写
    public $globalDSN = array(
        'write' => "#{Res.php-connectionpool.Proxy.WriteDsn}",
        'read' => "#{Res.php-connectionpool.Proxy.ReadDsn}"
    );

    public $read = array();

    public $write = array();

    public static $partitionDnsParseInfos = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        // 如果设置了分区dsn, 默认使用default_partition配置的分区, 并且预解析所有分区dns配置
        if (isset($this->partitionDsn) && $this->partitionDsn && is_array($this->partitionDsn)) {
            if (!isset($this->partitionDsn['default_partition'])) {
                throw new \Exception('partitionDsn miss default_partition');
            }

            $defaultPartition= $this->partitionDsn['default_partition'];
            if (empty($this->partitionDsn['partition']['dsn'][$defaultPartition]) || !is_array($this->partitionDsn['partition']['dsn'][$defaultPartition])) {
                throw new \Exception('miss default_partition info');
            }

            $this->globalDSN['read'] = $this->partitionDsn['partition']['dsn'][$defaultPartition]['read'];
            $this->globalDSN['write'] = $this->partitionDsn['partition']['dsn'][$defaultPartition]['write'];

            // 预解析partitionDsn配置
            $this->parsePartitionDsns();
        }

        foreach (array('read', 'write') as $m) {
            foreach ($this->$m as $name => &$r) {
                // had parsed. or is template parsed.
                if (!isset($r['dsn'])) {
                    continue;
                }
                $r['dsn'] = $this->globalDSN[$m];
                // 使用中间件时,如果没有设置连接参数persistent,则建立PDO长连接, 提高性能
                if ($this->persistent === true && !isset($r['options'][\PDO::ATTR_PERSISTENT])){
                    $r['options'][\PDO::ATTR_PERSISTENT] = true;
                }
                $r = self::parseCfg($r);
            }
        }
    }

    /**
     * 将配置中的DSN(负载均衡会配置多组ip:host、权重等)转成多条标准的pdo dsn格式,原始格式如："mysql:dbname=tuanmei_operation;host=192.168.20.71:9001:1,192.168.20.72:9001:1".
     *
     * @param array $cfg
     *            数据库的原始配置.
     *
     * @return array $parsed 一个或多个包含标准dsn配置;除以上信息外还会保留原来的额外配置.
     *
     * @throws \Exception Invalid dsn.
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
        if (count($mainPortions) != 2){
            throw new \Exception('Invalid dsn to parse:'. $dsn);
        }

        $driverType = $mainPortions[0];
        $dbPortions = explode(';', $mainPortions[1]);
        $dbname = "";
        $hostRaw = "";
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
            $port = isset($hostPortions[1]) && !empty($hostPortions[1]) ? $hostPortions[1] : (isset($defaultPort) ? $defaultPort : 3306);
            $parsedDsn .= 'port=' . $port;
            $weight = isset($hostPortions[2]) ? $hostPortions[2] : 1;
            $parsed[] = array_merge($cfg, array(
                'dsn' => $parsedDsn,
                'db' => $dbname,
                'weight' => $weight,
                'host' => $hostPortions[0],
                'port' => $port
            ));
        }
        return $parsed;
    }

    /**
     * 使用分区dsn替换原有dsn信息.
     *
     * @param array   $parseCfg       数据库的解析后配置.
     * @param integer $partitionIndex 用于替换的Dsn分区.
     *
     * @throws \Exception Invalid dsn.
     */
    public static function replaceGlobalDsnCfg($parseCfg, $partitionIndex)
    {

        $partitionDnsConfig = self::$partitionDnsParseInfos[$partitionIndex];

        // 修改所有数据库dns解析配置
        foreach (array('read', 'write') as $m) {
            foreach ($parseCfg[$m] as $dbSign => &$dbConfig) {
                // 取出第一个配置作为模板
                $tmpConfig = array_shift($dbConfig);

                $dbname = !empty($tmpConfig['db']) ? $tmpConfig['db'] : (!empty($tmpConfig['dbname']) ? $tmpConfig['dbname'] : '');

                // 先清空原有解析配置
                $dbConfig = array();

                // 重写解析配置
                foreach ($partitionDnsConfig[$m]['hosts'] as $host) {
                    $hostPortions = explode(':', $host);
                    $parsedDsn = $partitionDnsConfig[$m]['driverType'] . ':dbname=' . $dbname . ';host=' . $hostPortions[0] . ';';
                    $port = isset($hostPortions[1]) && !empty($hostPortions[1]) ? $hostPortions[1] : (isset($partitionDnsConfig[$m]['defaultPort']) ? $partitionDnsConfig[$m]['defaultPort'] : 3306);
                    $parsedDsn .= 'port=' . $port;
                    $weight = isset($hostPortions[2]) ? $hostPortions[2] : 1;

                    $dsnArr = array(
                                  'dsn' => $parsedDsn,
                                  'weight' => $weight,
                                  'host' => $hostPortions[0],
                                  'port' => $port
                    );

                    $dbConfig[] = array_merge($tmpConfig, $dsnArr);
                }
            }
        }

        return $parseCfg;
    }

    /**
     * 预解析所有分区dsn.
     *
     */
    private function parsePartitionDsns()
    {
        foreach ($this->partitionDsn['partition']['dsn'] as $partitionIndex => $dsnInfo) {
            foreach (array('read', 'write') as $m) {
                // 解析read和write的配置信息
                $dsn = isset($dsnInfo[$m]) ? $dsnInfo[$m] : '';

                $mainPortions = explode(':', $dsn, 2);

                if (count($mainPortions) != 2){
                    throw new \Exception('Invalid dsn to parse:'. $dsn);
                }

                // 获取存储服务
                $driverType = $mainPortions[0];
                // 获取db配置
                $dbPortions = explode(';', $mainPortions[1]);

                $dbname = "";
                $hostRaw = "";

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

                // 这个判断感觉没有必要
                if (!isset($dbname) || !isset($hostRaw)) {
                    throw new \Exception('Invalid dsn to parse!');
                }

                $hosts = explode(',', $hostRaw);

                self::$partitionDnsParseInfos[$partitionIndex][$m] = array(
                                                                         'driverType'  => $driverType,
                                                                         'hosts'       => $hosts,
                                                                         'defaultPort' => isset($defaultPort) ? $defaultPort : null,
                );
            }
        }
    }

}

