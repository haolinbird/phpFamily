<?php
namespace MNLogger;

class EXLogger {
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
        register_shutdown_function(array($this, "log_fatal_handler"));
        set_error_handler(array($this, "log_error"));
		//set_exception_handler(array($this, "log_exception"));
    }

    public function log_fatal_handler() {
    	$errors = error_get_last();
	    if ($errors["type"] == E_ERROR) {
	    	$error_msg = "\n". $errors['type'] . " {$errors['message']} in {$errors['file']} on line {$errors['line']}:\n";
	    	$error_msg .= $this->debug_backtrace_string();
            $this->_log('ERROR', $error_msg);
	    }

	}

	public function log_error($errno, $errstr, $errfile, $errline) {
		$error_msg = "\n". $errno . " {$errstr} in {$errfile} on line {$errline}:\n";
	    $error_msg .= $this->debug_backtrace_string();
	    $this->_log('ERROR', $error_msg);
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	private function debug_backtrace_string() {
	    $stack = '';
	    $i = 0;
	    $trace = debug_backtrace();
	    unset($trace[0]); //Remove call to this function from stack trace
	    unset($trace[1]); //Remove call to log_error function from stack trace
	    foreach($trace as $node) {
	        $stack .= "#$i ".$node['file'] ."(" .$node['line']."): "; 
	        if(isset($node['class'])) {
	            $stack .= $node['class'] . "->"; 
	        }
	        $stack .= $node['function'] . "()" . PHP_EOL;
	        $i++;
	    }
	    return $stack;
	} 

    // This exception will throw out and caught a fault error, or will log the error twice.
	// public function log_exception($e) {
	// 	$this->_log_exception($e, false);
    //  throw $e;
	// }

	private function _log_exception($e, $caught) {
		if($caught) {
			$type = 'WARN';
		} else {
			$type = 'ERROR';
		}
		//$class_name = get_class($e);
		$msg = $e->getMessage();
		$file = $e->getFile();
		$line = $e->getLine();
		$error_msg = "\n{$msg} in {$file} on line {$line}:\n". $e->getTraceAsString(). "\n";
		$this->_log($type, $error_msg);
	}

    public function __destruct()
    {
        if ($this->_fileHandle) {
            fclose($this->_fileHandle);
        }
    }

    public function log($e) {
    	$this->_log_exception($e, true);

    }

    private function _log($type, $exception)
    {
        if ($this->_on === self::OFF) {
            return;
        }

        global $owl_context;

        $time = date('Y-m-d H:i:s');
        $line = "OWL\001DATA\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001Exception\001{$owl_context['uuid']}\001{$owl_context['trace_id']}\001{$type}\001{$exception}\004\n";

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