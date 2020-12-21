<?php
namespace MNLogger;

class MNLogger
{

    const OFF = false;

    private static $filePermission = 0777;
    private $_logFilePath = null;
    private $_fileHandle = null;
    private $_hostname = null;
    private $_ip = null;
    private $_app = null;
    private $_on = false;

    private static $instance = array();

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
    public static function instance($config)
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

    public function __construct($config)
    {
        $this->_on = $config['on'];
        if(!$config['app'] || !$config['logdir']) {
            throw new \Exception("Please check the config params.\n");
        }
        if ($this->_on === self::OFF) {
            return;
        }
        $this->_app = $config['app'];
        $this->_ip = $this->getIp();
        $this->_logdir = $config['logdir']. DIRECTORY_SEPARATOR. $this->_app;

        date_default_timezone_set('PRC');
        $this->_logFilePath = $this->_logdir
            . DIRECTORY_SEPARATOR
            . $this->_app
            . '.'
            . date('Ymd')
            . '.log';
        if (!file_exists($this->_logdir)) {
            umask(0);
            if (!mkdir($this->_logdir, self::$filePermission, true)) {
                throw new \Exception('Can not mkdir: ' . $this->_logdir);
            }
        }

        if (file_exists($this->_logFilePath) && !is_writable($this->_logFilePath)) {
            throw new \Exception('Can not write monitor log file: ' . $this->_logFilePath . "\n");
        }
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
        $keys_len = count(explode(',', $keys));
        $vals_len = count(explode(',', $vals));

        if($keys_len > 6) {
            throw new \Exception('Keys count should be <= 6.');
        }

        if($vals_len > 4) {
            throw new \Exception('Values count should be <= 4.');
        }

        $keys = str_replace(",", "\003", $keys);
        $vals = str_replace(",", "\003", $vals);

        $time = date('Y-m-d H:i:s');
        $line = "OWL\001STATS\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001{$keys}\001{$vals}\004\n";

        if (!$this->_fileHandle) {
            $this->_fileHandle = fopen($this->_logFilePath, 'a');
            if (!$this->_fileHandle) {
                throw new \Exception('Can not open file: ' . $this->_logFilePath);
            }
        }
        if (!fwrite($this->_fileHandle, $line)) {
            throw new \Exception('Can not append to file: ' . $this->_logFilePath);
        }
    }

    private function getIp()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        } else {
            $ip = gethostbyname(trim(`hostname`));
        }
        return $ip;
    }
}
