<?php
namespace MNLogger;

class Base{
    const OFF = false;
    protected $_logFilePath = null;
    protected $_fileHandle = null;
    protected $_hostname = null;
    protected $_ip = null;
    protected $_app = null;
    protected $_on = false;

    protected $_mode = 1;
    protected $_server = null;
    const WRITE_TO_FILE = 1;
    const WRITE_TO_UDP = 2;
    const WRITE_TO_ALL = 3;

    const T_SUCCESS = 'success';
    const T_EXCEPTION = 'exception';

    // 无论是否抽样, 发生异常时必须记录下链路日志.
    static $isException = false;
    // 记录资源层耗时.
    static $resCsTimestamp = array();
    // 暂存链路日志
    static $log_buffer = array();
    // 抽样基数.
    protected $_samplePerRequest = 100;
    // binary_annotations value的字符限制.
    protected $binary_annotations_max_size = 120;

    public static function switchToExceptionStatus()
    {
        global $owl_context;

        // 从异常发生的点开始, 所有后续调用都要记录.
        static::$isException = true;
        $owl_context['is_sample'] = 1;
    }

    // 刷新全链路日志.
    public static function flush()
    {
        global $owl_context;
        // 写入日志到磁盘.
        // static::$log_buffer
        // global_span: 当前请求生命周期的全局span.
        // spans: 当前请求生命周期内，为每一次cs/cr新生成的span.
        if (isset(static::$log_buffer['global_span']) && (static::$log_buffer['global_span']['sample'] == 1 || static::$isException == true)) {
            TraceLogger::instance()->log(json_encode(static::$log_buffer['global_span'], 256));
            if (isset(static::$log_buffer['spans'])) {
                foreach (static::$log_buffer['spans'] as $span) {
                    TraceLogger::instance()->log(json_encode($span, 256));
                }
            }
        }

        static::reset();
    }

    // 重置全链路相关的配置标识和数据.
    public static function reset()
    {
        global $owl_context;

        static::$isException = false;
        static::$resCsTimestamp = array();
        static::$log_buffer = array();
    }

    // 设置抽样基数.
    public function setSamplePerRequest($samplePerRequest)
    {
        $this->_samplePerRequest = $samplePerRequest;
    }

    // 判断是否抽中.
    protected function isSample()
    {
        if ($this->_samplePerRequest < 1) {
            return false;
        }

        return mt_rand(1, $this->_samplePerRequest) === 1;
    }

    protected static $defaultConfig = array(
        'on' => true,
        'app' => 'DefaultSetting',
        'logdir' => '/home/logs/monitor/'

    );

    public static function getRecommendConfig($config)
    {
        if(empty(static::$configs) && class_exists('\Config\MNLogger')) {
            static::$configs = (array) new \Config\MNLogger;
        }

        if (! isset(static::$configs[$config])) {
            static::$configs[$config] = static::$defaultConfig;
            if (defined('JM_APP_NAME')) {
                static::$configs[$config]['app'] = JM_APP_NAME;
            } else {
                static::$configs[$config]['app'] = 'undefined';
            }
        }

        return static::$configs[$config];
    }

    /**
     * Initialize parameters/configs of logger. Then Logger::instance($configname) can retrieve an instance by config name.
     *
     * @param array $config
     */
    public static function setUp(array $config)
    {
        static::$configs = $config;
    }

    /**
     * @param string|array $config  config name or content.
     * @return static
     * @throws \Exception
     */
    public static function instance($config)
    {
        if(is_string($config))
        {
            if(empty(static::$configs))
            {
                // Load config by common rules {@link http://wiki.int.jumei.com/index.php?title=PHP%E9%A1%B9%E7%9B%AE/%E7%B1%BB%E5%BA%93%E5%BC%80%E5%8F%91%E4%B8%8E%E9%9B%86%E6%88%90%E8%A7%84%E8%8C%83#.E9.85.8D.E7.BD.AE.E8.87.AA.E5.8A.A8.E5.8A.A0.E8.BD.BD.E6.94.AF.E6.8C.81}
                if(class_exists('\Config\MNLogger'))
                {
                    static::$configs = (array) new \Config\MNLogger;
                }
                else
                {
                    static::$configs[$config] = static::$defaultConfig;
                }
            }
            if(!isset(static::$configs[$config]))
            {
                throw new Exception("$config not exists, is it configured with ".__CLASS__.'::setUp() ?');
            }
            else
            {
                $config = static::$configs[$config];
            }
        }
        if(!$config['app'] || !$config['logdir']) {
            throw new Exception("Please check the config params.\n");
        }
        $config_key = $config['app']. '_'. $config['logdir'];
        if (isset(static::$instance[$config_key])) {
            return static::$instance[$config_key];
        }
        static::$instance[$config_key] = new static($config);
        return static::$instance[$config_key];
    }

    public function __construct($config)
    {
        $this->_on = $config['on'];

        if (isset($config['mode'])) {
            $this->_mode = @intval($config['mode']);
        }

        if (isset($config['server'])) {
            $this->_server = $config['server'];
        }

        if(!$config['app'] || !$config['logdir']) {
            throw new \Exception("Please check the config params.\n");
        }

        // 设置当前节点的抽样基数.
        if (isset($config['sample_per_request'])) {
            $this->setSamplePerRequest((int)$config['sample_per_request']);
        }

        // binary_annotations value的字符限制.
        if (isset($config['binary_annotations_max_size'])) {
            $this->binary_annotations_max_size = (int)$config['binary_annotations_max_size'];
        }

        if ($this->_on === static::OFF) {
            return;
        }
        $this->_app = $config['app'];
        $this->_ip = $this->getIp();
        $this->_logdir = $config['logdir']. DIRECTORY_SEPARATOR. $this->_app. DIRECTORY_SEPARATOR. $this->_logdirBaseName;

        date_default_timezone_set('PRC');
        if (!file_exists($this->_logdir)) {
            umask(0);
            if (!mkdir($this->_logdir, static::$filePermission, true)) {
                throw new \Exception('Can not mkdir: ' . $this->_logdir);
            }
        }

        $this->_logFilePath = $this->getLogFilePath();
        if (file_exists($this->_logFilePath) && !is_writable($this->_logFilePath)) {
            throw new \Exception('Can not write monitor log file: ' . $this->_logFilePath . "\n");
        }
    }


    protected function getIp()
    {
        static $ip;
        if($ip !== null)
        {
            return $ip;
        }

        // exec('ifconfig eth0', $output, $ret);
        // if ($ret == 0) {
        //     $o = implode('', $output);
        //     if (preg_match('!addr:([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})!', $o, $m)) {
        //         $ip = $m[1];
        //         return $ip;
        //     }
        // }
            
        // $ip = gethostbyname(trim(`hostname`));
        $ip = gethostbyname(gethostname());
        return $ip;
    }

    public function __destruct()
    {
        if ($this->_fileHandle) {
            fclose($this->_fileHandle);
        }
    }

    /**
     * Unified serialization method.(using json_encode without unicode escape).
     * @param mixed $data
     * @return string
     */
    public function serializeData($data)
    {
        return json_encode($data, 256);
    }

    public function getLogFilePath(){
       return $this->_logdir
       . DIRECTORY_SEPARATOR
       . $this->_app
       . '.'
       . date('Ymd')
       . '.log';
    }

    /**
     * Append log.
     *
     * @param string $file File path.
     * @param string $line Content.
     *
     * @return boolean
     */
    protected function send($file, $line)
    {
        if ($this->_mode & self::WRITE_TO_FILE) {
            if (file_put_contents($file, $line, FILE_APPEND|LOCK_EX) == false) {
                throw new \Exception("Can not append to file: $file");
            }
        }

        if ($this->_mode & self::WRITE_TO_UDP) {
            $pkgs = $this->package($line);

            foreach ($pkgs as $pkg) {
                $this->sendUdp($pkg);
            }
        }

        return true;
    }

    /**
     * Package.
     *
     * @param string $line Content.
     *
     * @return array
     */
    private function package($line)
    {
        $u = json_decode('{"data":["\u0010","\u0011"]}', true);

        $time = crc32(sprintf("%.9f", microtime(true)));

        $bin = pack('c3', ord('U'), ord('M'), ord('S')) . $u['data'][0] . pack('I', $time);
        $bin .= pack('A*', $line);
        $bin .=  pack('c3', ord('U'), ord('M'), ord('S')) . $u['data'][1] . pack('I', $time);

        $data = array();
        $offset = 0;
        $total = strlen($bin);
        while (($row = substr($bin, $offset, 65535))) {
            $data[] = $row;

            $offset += 65535;
            if ($offset >= $total) {
                break;
            }
        }

        return $data;
    }

    /**
     * Send Message.
     *
     * @param string $content Content.
     *
     * @return void
     */
    private function sendUdp($content)
    {
        if (empty($this->_server)) {
            throw new \Exception("Server is empty");
        }

        $tmp = explode(':', $this->_server);
        $ip = trim($tmp[0]);
        $port = trim($tmp[1]);
        $port = empty($port) ? 9001 : $port;

        $fp = fsockopen("udp://$ip", $port, $errno, $errstr, 10);

        if (! is_resource($fp)) {
            throw new \Exception($errstr, $errno);
        }

        fwrite($fp, $content);
        fclose($fp);
    }
}