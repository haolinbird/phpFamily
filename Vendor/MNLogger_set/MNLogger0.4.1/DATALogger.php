<?php
namespace MNLogger;

class DATALogger {
	const OFF = false;

    private static $filePermission = 0777;
    private $_logFilePath = null;
    private $_fileHandle = null;
    private $_hostname = null;
    private $_ip = null;
    private $_app = null;
    private $_on = false;

    private static $instance = array();

    public static function instance($config)
    {
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

    public function log($key, $data)
    {
        if ($this->_on === self::OFF) {
            return;
        }

        $time = date('Y-m-d H:i:s');
        $line = "OWL\001DATA\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001DATA\001{$key}\001{$data}\004\n";

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