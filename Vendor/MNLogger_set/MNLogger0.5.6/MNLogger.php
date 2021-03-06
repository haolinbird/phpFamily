<?php
namespace MNLogger;

class MNLogger extends Base
{
    protected static $filePermission = 0777;
    protected $_logdirBaseName = 'stats';
    protected static $instance = array();

    protected static $configs;

    /**
     * 初始化所有配置
     */
    public static function config(array $configs)
    {
        static::$configs = $configs;
    }

    /**
     * @param string|array $config  string时为配置名称, array为配置内容
     * @return mixed
     * @throws \Exception
     */
    public static function instance($config='stats')
    {
        if(is_string($config))
        {
            if(!static::$configs)
            {// 自动加载配置
                static::$configs = (array) new \Config\MNLogger;
            }
            if(!isset(static::$configs[$config]))
            {
                throw new \Exception("MNLogger config \"{$config}\" not exists!");
            }
            $config = static::$configs[$config];
        }
        if(!$config['app'] || !$config['logdir']) {
            throw new \Exception("Please check the config params.\n");
        }
        $config_key = $config['app']. '_'. $config['logdir'];
        if (isset(self::$instance[$config_key])) {
            return self::$instance[$config_key];
        }
        self::$instance[$config_key] = new self($config);
        return self::$instance[$config_key];
    }

    public function __destruct()
    {
        if ($this->_fileHandle) {
            fclose($this->_fileHandle);
        }
    }

    // log('mobile,send', '1');
    public function log($keys, $vals)
    {
        if ($this->_on === self::OFF) {
            return;
        }
        // $keys_len = count(explode(',', $keys));
        // $vals_len = count(explode(',', $vals));

        // if($keys_len > 6) {
        //     throw new \Exception('Keys count should be <= 6.');
        // }

        // if($vals_len > 4) {
        //     throw new \Exception('Values count should be <= 4.');
        // }

        $keys = str_replace(",", "\003", $keys);
        $vals = str_replace(",", "\003", $vals);

        $time = date('Y-m-d H:i:s');
        $line = "OWL\001STATS\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001{$keys}\001{$vals}\004\n";

        $this->_logFilePath = $this->getLogFilePath();
        // if (false === file_put_contents($this->_logFilePath, $line, FILE_APPEND | LOCK_EX)) {
        //     throw new \Exception('Can not append to file: ' . $this->_logFilePath);
        // }
        $this->send($this->_logFilePath, $line);
    }
}
