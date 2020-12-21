<?php
namespace MNLogger;

class DATALogger extends Base{
    protected static $filePermission = 0777;
    protected $_logdirBaseName = 'data';
    protected static $configs=array();
    protected static $instance = array();


    /**
     * @param string $config
     * @return static
     */
    public static function instance($config='exception')
    {
        return parent::instance($config);
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
        $this->_logFilePath = $this->getLogFilePath();
        $line = "OWL\001DATA\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001DATA\001{$key}\001{$data}\004\n";
        if(false === file_put_contents($this->_logFilePath, $line, FILE_APPEND|LOCK_EX)){
            throw new \Exception('Can not append to file: ' . $this->_logFilePath);
        }
    }
}