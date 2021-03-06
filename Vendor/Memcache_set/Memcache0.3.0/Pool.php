<?php
namespace Memcache;

class Pool{
    protected static $instances = array();
    protected static $configs;
    protected static $defaultConfig =  array(
        array('host'=>'127.0.0.1', 'port' => 11211)
    );
    protected function __construct()
    {

    }

    /**
     * Set or get configs for the lib.
     *
     * @param string $config
     * @return boolean
     */
    public static function config($config=null)
    {
        if(is_null($config))
        {
            return static::$configs;
        }
        static::$configs = $config;
        return true;
    }

    /**
     * Destroy instances in pool.
     */
    public static function destroy()
    {
        foreach(self::$instances as $inst)
        {//force free resources, in case any other references.
            $inst->close();
            $inst = null;
        }
        self::$instances = array();
        self::$configs = null;
    }

    /**
     *
     * @param string $endpoint
     * @throws Exception
     * @return \Memcache\Connection
     */
    public static function instance($endpoint='default')
    {
        if (!isset(self::$instances[$endpoint]))
        {
            if(!self::$configs)
            {
                if(class_exists('\Config\Memcache'))
                {
                    self::$configs = (array) new \Config\Memcache;
                }
                else
                {
                    self::$configs[$endpoint] = self::$defaultConfig;
                }

            }
            $configs = self::$configs;
            if(!isset(self::$configs[$endpoint]))
            {
                throw new Exception('Config of "' . $endpoint . '" does not exist!', 100);
            }
            self::sanitizeConfig($configs[$endpoint]);
            self::$instances[$endpoint] = self::connect($configs[$endpoint]);
        }
        return self::$instances [$endpoint];
    }
    public static function instanceList()
    {
        return self::$instances;
    }
    protected static function connect($config)
    {
        $instance = new Connection();
        $backupServer = array();
        if(isset($config['backup_hosts'])){
            $backupServer = $config['backup_hosts'];
            unset($config['backup_hosts']);
        }
        $configTemp = array('host'=>array());
        if(is_array(current($config)))
        {
            foreach ($config as $host)
            {
                if(isset($host['type']) && $host['type'] == 'backup')
                {//作为集群二级缓存使用
                    $backupServer[] = $host;
                    continue;
                }
                $persistent = isset($host['persistent']) ? $host['persistent'] : false;
                $instance->addServer($host['host'], $host['port'], $persistent);
                $configTemp['host'][] = $host['host'].':'.$host['port'];
            }
        }
        else if(isset($config['host']))
        {
            if(!isset($config['port']))
            {
                $config['port'] = 11211;
            }
            $persistent = isset($host['persistent']) ? $host['persistent'] : false;
            $instance->addServer($config['host'], $config['port'], $persistent);
            $configTemp['host'][] = $config['host'].':'.$config['port'];
        }
        if(!empty($backupServer))
        {
            foreach($backupServer as $k => $v) {
                unset($backupServer[$k]['type']);
            }
            $instance->setBackupInstance(self::connect($backupServer));
        }
        $instance->setDsn(implode(',', $configTemp['host']));
        return $instance;
    }

    public static function sanitizeConfig(&$config)
    {
        if(!is_array(current($config)))
        {
            $config = array($config);
        }

        foreach ($config as $k=>$c)
        {
            if ($k == 'backup_hosts') {
                continue;
            }
            if(!isset($c['port']))
            {
                $config[$k]['port'] = 11211;
            }
            if(!isset($c['host']))
            {
                throw new Exception('Invalid config! please provide "host" !', 100);
            }
        }
        return $config;
    }
}
