<?php
namespace PHPClient;

use \Exception;

require_once __DIR__ . '/../MNLogger/Base.php';
require_once __DIR__ . '/../MNLogger/TraceLogger.php';
require_once __DIR__ . '/protocol/Lark.php';
require_once __DIR__ . '/protocol/JMText.php';


/**
 * 客户端协议实现.
 */
class JMTextRpcClient {
    /**
     * 本地代理地址
     */
    const LARK_ADDRESS = 'tcp://127.0.0.1:12311';

    // lark保存的服务快照目录
    const LARK_SNAPSHOT_DIR = '/var/lib/lark/service_snapshot/';

    const EXCEP_CODE_CONN_ERR = 2002;

    // 废弃，但为了兼容暂时保留该静态成员
    public static $connectionTimeOut = 4;
    public static $recvTimeOut = 18;

    /**
     * Text协议处理类
     *
     * @var \protocol\JMText
     */
    protected $protocolText;

    /**
     * 非全局的链接超时
     *
     * @var int
     */
    protected $connectTimeout = 1;

    /**
     * 当前实例是否走lark
     *
     * @var int
     */
    public $useLark = true;

    /**
     * 当前实例是否走mcpd
     *
     * @var int
     */
    public $useMcpd = true;

    /**
     * 非全局的read超时
     *
     * @var int
     */
    protected $recvTimeout = 5;

    /**
     * 与lark交互多等待的超时偏移量,单位毫秒
     */
    protected $recvLarkTimeoutOffset = 10;

    /**
     * 调用的类名
     *
     * @var string
     */
    protected $rpcClass;

    /**
     * 当前正在使用的配置信息
     *
     * @var array
     */
    protected $config;

    /**
     * 当前要发送的数据
     *
     * @var string
     */
    protected $sendBuffer;

    /**
     * 所有的服务器信息
     *
     * @var array
     */
    protected static $_config = array();


    protected static $events = array();

    private static $configName = "";
    /**
     * 设置或读取配置信息.
     *
     * @param array $config 配置信息.
     *
     * @return array|void
     */
    public static function config(array $config = array())
    {
        if (empty($config)) {
            return self::$_config;
        }
        foreach($config as $key=>$item)
        {
            self::$_config[$key] = $item;
        }
        return self::$_config;
    }


    /**
     * 获取RPC对象实例.
     *
     * @param array $config 配置信息, 或配置节点.
     *
     * @return JMTextRpcClient
     */
    public static function instance($config = array())
    {
        $className = get_called_class();
        
        static $instances = array();
        $key = $className . '-';
        if (empty($config)) {
            $key .= 'whatever';
        } else {
            $key .= md5(serialize($config));
        }
        if (empty($instances[$key]) || PHP_SAPI == 'cli') {
            if (!is_array($config)) {
                self::$configName = $config;
                $config = self::config();
            } else {
                self::$configName = "";
            }
            // 兼容老的 Rpc_XXX 形式的服务调用
            if (strpos($className, 'RpcClient_') === 0) {
                $instances[$key] = new $className($config);
            } else {
                $instances[$key] = new $className(self::$configName);
            }

            $instances[$key]->rpcClass = $className;
        }
        
        return $instances[$key];
    }

    /**
     * 检查返回结果是否包含错误信息.
     *
     * @param mixed $ctx 调用RPC接口时返回的数据.
     *
     * @return boolean
     */
    public static function hasErrors(&$ctx)
    {
        if (is_array($ctx)) {
            if (isset($ctx['error'])) {
                $ctx = $ctx['error'];
                return true;
            }
            if (isset($ctx['errors'])) {
                $ctx = $ctx['errors'];
                return true;
            }
        }
        return false;
    }

    /**
     * 注册各种事件回调函数.
     *
     * @param string   $eventName     事件名称, 如: read, recv.
     * @param function $eventCallback 回调函数.
     *
     * @return void
     */
    public static function on($eventName, $eventCallback)
    {
        if (empty(self::$events[$eventName])) {
            self::$events[$eventName] = array();
        }
        array_push(self::$events[$eventName], $eventCallback);
    }

    /**
     * 调用事件回调函数.
     *
     * @param $eventName 事件名称.
     *
     * @return void.
     */
    protected static function emit($eventName)
    {
        if (!empty(self::$events[$eventName])) {
            $args = array_slice(func_get_args(), 1);
            foreach (self::$events[$eventName] as $callback) {
                @call_user_func_array($callback, $args);
            }
        }
    }


    /**
     * 构造函数.
     *
     * @param array $config 配置信息, 或配置节点.
     *
     * @throws Exception 抛出开发错误信息.
     */
    protected function __construct(array $config = array()) {
        // 用户传入的配置
        $config = self::config($config);

        // Config目录的配置，多次调用config方法效果类似与merge
        if(class_exists('\Config\PHPClient')) {
            $config = self::config((array) new \Config\PHPClient);
        }

        if (empty($config)) {
            throw new Exception('找不到任何配置');
        }

        if(!empty($config['connection_time_out']) && (int)$config['connection_time_out'] > 0) {
            $this->connectTimeout = (int)$config['connection_time_out'];
        }

        if(!empty($config['recv_time_out']) && (int)$config['recv_time_out'] > 0) {
            $this->recvTimeout = (int)$config['recv_time_out'];
        }

        $this->useLark = defined('USE_PHPCLIENT_LARK') ? USE_PHPCLIENT_LARK : true;
        if(isset($config['use_lark'])) {
            $this->useLark = $config['use_lark'];
        }

        $this->useMcpd = defined('USE_CONNECT_POOL') ? USE_CONNECT_POOL : true;
        if(isset($config['use_mcpd'])) {
            $this->useMcpd = $config['use_mcpd'];
        }

        $className = get_called_class();

        if (!preg_match('/^[A-Za-z0-9]+_([A-Za-z0-9]+)/', $className, $matches)) {
            throw new Exception(sprintf('JMTextRpcClient: Invalid class name `%s`', $className));
        }

        $module = $matches[1];
        if (!empty(self::$configName)) {
            $module = self::$configName;
        }
        if (empty($config[$module])) {
            throw new Exception(sprintf('JMTextRpcClient: Missing configuration for `%s`', $module));
        }
        $this->init($config[$module]);
    }

    /**
     * 析构函数.
     */
    public function __destruct(){
    }

    /**
     * 读取初始化配置信息.
     *
     * @param array $config 配置.
     *
     * @return void
     */
    public function init(array $config) {
        $this->config = $config;
        if (!empty($this->config['recv_time_out'])) {
            $this->recvTimeout = $this->config['recv_time_out'];
        }

        if (!empty($this->config['recv_lark_timeout_offset'])) {
            $this->recvLarkTimeoutOffset = $this->config['recv_lark_timeout_offset'];
        }

        $this->useLark = defined('USE_PHPCLIENT_LARK') ? USE_PHPCLIENT_LARK : true;
        if(isset($config['use_lark'])) {
            $this->useLark = $config['use_lark'];
        }

        $this->useMcpd = defined('USE_CONNECT_POOL') ? USE_CONNECT_POOL : true;
        if(isset($config['use_mcpd'])) {
            $this->useMcpd = $config['use_mcpd'];
        }

        if ($this->useLark && empty($this->config['service'])) {
            throw new \Exception('使用lark必须配置service字段');
        }
        $this->protocolText = new \protocol\JMText(self::$_config['rpc_secret_key'], $this->config['user'], $this->config['secret']);
        $this->rpcCompressor = isset($config['compressor']) ? strtoupper($config['compressor']) : null;
    }

    /**
     * 获取当前服务的配置.
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 创建网络链接.
     *
     * @throws Exception 抛出链接错误信息.
     *
     * @return Socket connection resource.
     */
    public function openLarkConnection() {
        $proxyAddress = self::LARK_ADDRESS;
        if (defined(('LARK_ADDRESS'))) {
            $proxyAddress = LARK_ADDRESS;
        }

        $conn = @stream_socket_client($proxyAddress, $errno, $errStr, $this->connectTimeout);

        if (!$conn) {
            throw new Exception(sprintf('连接代理[%s]失败，错误详情:%s', $proxyAddress, $errStr), self::EXCEP_CODE_CONN_ERR);
        }

        stream_set_timeout($conn, $this->recvTimeout, $this->recvLarkTimeoutOffset * 1000);

        return $conn;
    }

    /**
     *发送数据
     * @param Stream socket resource $conn*
     * @param array $packet
     *
     * @throws Exception 抛出开发用异常
     */
    public function sendTLV($buffer, $conn) {
        // 记录下来，可能后面会用到
        $this->sendBuffer = $buffer;
        try {
            $context = array(
                'recv_timeout'      => $this->recvTimeout,
                'client_service'    => defined('JM_APP_NAME') ? JM_APP_NAME : 'php-rpc-client',
                'service_name'      => $this->config['service'],
            );
            if (isset($this->config['specified_address'])) {
                $context['target_address'] = $this->config['specified_address'];
            }
            $larkBuffer = \protocol\Lark::encode(\protocol\Lark::TAG_RPC_CLIENT_SEND, array($buffer, $this->config['service'], json_encode($context)));

            $len = fwrite($conn, $larkBuffer, strlen($larkBuffer));

            if ($len != strlen($larkBuffer)) {
                throw new Exception(sprintf('发送数据到代理失败'));
            }

            self::emit('send', $larkBuffer);
        } catch (\Exception $e) {
            throw $e;
        }
        return ;
    }

    /**
     * 接收数据
     * @param Stream socket resource $conn
     * @return array
     * @throws \Exception
     */
    public function recvTLV($conn) {
        $buffer = '';
        while (($leftLen = \protocol\Lark::input($buffer)) > 0) {
            $tmp = fread($conn,  $leftLen);

            if (!$tmp && $tmp !== '0') { // 字符串0是一个神奇的存在，bool值为false
                $err = $this->getConnErr($conn);
                throw new \Exception(sprintf('[Lark协议]读取失败,原因:%s。服务名[%s]',$err, $this->config['service']));
            }
            $buffer .= $tmp;
        }

        $larkData = \protocol\Lark::decode($buffer, $err);

        if ($larkData === false) {
            throw new \Exception(sprintf('请求异常，错误:%s', $err));
        }

        $tag = $larkData['tag'];

        $context = array();
        if (count($larkData['data']) >= 2) {
            $context = json_decode($larkData['data'][1], true);
        }

        if ($tag !== \protocol\Lark::TAG_RPC_CLIENT_RECV) {
            throw new \Exception(sprintf('请求异常，错误的Tag[%d],期望Tag[%d]:%s, 服务地址: %s', $tag, \protocol\Lark::TAG_RPC_CLIENT_RECV, $larkData['data'][0], @$context['server_ip']));
        }

        $data = $larkData['data'][0];


        self::emit('recv', $data);

        fclose($conn);

        $data = ($this->protocolText->decode($data));

        $ctx = json_decode($data, true);

        return $this->dealCtx($ctx);
    }

    /**
    *初始化发送数据
    *
    *@param array  $arguments service方法所需传的参数。
    *@return array   待发送数据
    */
    public function initRpcData($class, $method, $arguments) {
        $packet = $this->protocolText->getPacket($class, $method, $arguments);
        return $this->protocolText->encode($packet);
    }

    /**
    * 检查返回结果
    *
    * @param array $ctx RPC调用结果
    * @throws Exception
     * @return mixed
    */
    protected function dealCtx($ctx) {
        $trace_logger = $this->getTraceLogger();
        
        if (is_array($ctx) && isset($ctx['exception']) && is_array($ctx['exception'])) {
            throw new Exception(var_export($ctx['exception'], true));
        }

        if(is_array($ctx) && isset($ctx['rpc_business_exception'])) {
            $trace_logger && $trace_logger->RPC_CR('EXCEPTION', strlen(json_encode($ctx)), $ctx);
        } else {
            $trace_logger && $trace_logger->RPC_CR('SUCCESS', strlen(json_encode($ctx)));
        }

        return $ctx;

    }

    /**
    * 获取日志对象
    *
    * @return \MNLogger\TraceLogger 当无法获取时返回null.
    */
    public function getTraceLogger()
    {
        static $logger;
        if(!is_null($logger)){
            return $logger;
        }
        $config = self::config();
        
        $logdir = isset($config['trace_log_path']) ? $config['trace_log_path'] : '/home/logs/monitor';
        
        $trace_config = array(
           'on' => true,
           'app' => defined('JM_APP_NAME') ? JM_APP_NAME : 'php-rpc-client',
           'logdir' => $logdir,
        );
        
        try{
            return \MNLogger\TraceLogger::instance($trace_config);
        }
        catch(\Exception $e){}
    }

    protected function getConnErr($conn) {
        if(feof($conn)) {
           return "链接断开";
        }
        $meta = stream_get_meta_data($conn);
        if ($meta['timed_out']) {
            return sprintf('读取服务端数据超时(%d秒)', $this->recvTimeout);
        }

        $errInfo = error_get_last();

        return sprintf('未知错误:%s', $errInfo['message']);
    }

    public function __call($method, $arguments)
    {
        $this->rpcMethod = $method;
        if($this->rpcClass){
            $class = $this->rpcClass;
        } else {
            $class = get_called_class();
        }

        try {
            $trace_logger = $this->getTraceLogger();
            $trace_logger && $trace_logger->RPC_CS($this->config['service'], $class, $method, $arguments);

            if ($this->useLark) {
                do {
                    try {
                        $conn = $this->openLarkConnection();
                    } catch (\Exception $e) {
			error_log($e->getMessage()."\n".$e->getTraceAsString());
                        break;
                    }
                    $packet = $this->initRpcData($class, $method, $arguments);
                    $this->sendTLV($packet, $conn);

                    return $this->recvTLV($conn);
                } while (false);
            }

            try {
                $conn = $this->openServerConnection();
            }catch (\Exception $e) { // 尝试mcpd
                $conn = $this->openMcpdConnection();
            }

            $packet = $this->initRpcData($class, $method, $arguments);
            $this->sendJMText($packet, $conn);

            return $this->recvJMText($conn);
        } catch (\Exception $e) {
            $trace_logger = $this->getTraceLogger();
            $trace_logger && $trace_logger->RPC_CR('EXCEPTION', strlen($e), $e);
            throw $e;
        }
    }

    /******************************************
     * 以下方法可以在lark稳定后全部删除
     ******************************************/
    public function openMcpdConnection() {
        $mcpdAddress = 'unix:///var/run/mcpd.sock';
        $conn = @stream_socket_client($mcpdAddress, $errno, $errStr, $this->connectTimeout);

        if (!$conn) {
            throw new Exception(sprintf('连接mcpd失败:%s', $errStr));
        }

        stream_set_timeout($conn, $this->recvTimeout);

        $buffer = sprintf("jmtext://servicename=%s;dovekey=%s", $this->config['service'], @$this->config['doveKey']);
        $buffer = sprintf("%d\n%s\n", strlen($buffer), $buffer);

        if (fwrite($conn, $buffer, strlen($buffer)) != strlen($buffer)) {
            throw new \Exception('与mcpd握手失败');
        }
        $data = fgets($conn);
        if (trim($data) != '+OK') {
            throw new \Exception('与mcpd握手失败');
        }

        return $conn;
    }

    /**
     * 获取直连链接.
     *
     * @return resource
     * @throws Exception
     */
    public function openServerConnection() {
        $address = $this->getOneAddress();
        if (!$address) {
            throw new \Exception('无法连接lark、mcpd，且无法找到可用的远端服务');
        }
        $conn = @stream_socket_client($address, $errno, $errStr, $this->connectTimeout);

        if (!$conn) {
            throw new Exception(sprintf('连接server[%s]失败:%s', $address, $errStr));
        }

        stream_set_timeout($conn, $this->recvTimeout);

        return $conn;
    }

    /**
     * 依次尝试从配置、快照中获取可用的uri
     *
     * @return bool|string
     *
     */
    protected function getOneAddress() {
        $nodes = @$this->config['uri'];
        
        if (is_string($nodes)) {//可能是'tcp://127.0.0.1:2202'
            return $this->formatUri($nodes);
        }


        if (is_array($nodes) && count($nodes) >= 1) { //优先随机使用dove中的uri
            $randKey = array_rand($nodes);
            return $this->formatUri($nodes[$randKey]);
        }
        // 最后一次努力，尝试使用lark的遗言
        return $this->getSnapshotAddress();
    }

    protected function formatUri($uri) {
        $tmp = str_replace('tcp://', '', $uri);
        $tmp = explode(':', $tmp);
        $address = "$tmp[0]:$tmp[1]";
        return  'tcp://' . $address;
    }

    /**
     * 发送原生的JMText协议.
     *
     * @param $buffer
     * @param $conn
     *
     * @throws Exception
     */
    public function sendJMText($buffer, $conn) {
        if (fwrite($conn, $buffer, strlen($buffer)) != strlen($buffer)) {
            throw new Exception(sprintf('[JMText协议]发送失败'));
        }
        self::emit('send', $buffer);
        return ;
    }

    public function recvJMText($conn) {
        // 读取 RPC 返回数据的长度信息
        if (!$length = fgets($conn)) {
            $err = $this->getConnErr($conn);
            throw new \Exception(sprintf('[JMText协议]读取失败:%s。服务名[%s]',$err, $this->config['service']));
        }
        $length = trim($length);
        if (substr($length, 0, 4) == '!ERR') {
            throw new Exception($length);
        }

        if (!preg_match('/^\d+$/', $length)) {
            throw new \Exception(sprintf('[JMText协议]错误的长度标识:%s。服务名[%s]',bin2hex($length), $this->config['service']));

        }
        $length = 1 + $length; // 1 means \n

        error_clear_last();
        // 读取 RPC 返回的具体数据
        $ctx = '';
        while (strlen($ctx) < $length) {
            $buf = fgets($conn);

            if(false !== $buf){
                $ctx .= $buf;
            } else{
                $err = $this->getConnErr($conn);
                throw new \Exception(sprintf('[JMText协议]读取失败:%s。服务名[%s]',$err, $this->config['service']));
            }

        }

        self::emit('recv', $ctx);
        $ctx = trim($ctx);

        fclose($conn);
        // 反序列化 JSON 数据并返回
        if ($ctx !== '') {
            if ($this->rpcCompressor === 'GZ') {
                $ctx = @gzuncompress($ctx);
            }
            $ctx = json_decode($ctx, true);
        }
        return $this->dealCtx($ctx);
    }

    public function getSnapshotAddress() {
        $snapShotFile = self::LARK_SNAPSHOT_DIR . $this->config['service'];

        if (!is_file($snapShotFile)) {
            throw new \Exception(sprintf('快照文件[%s]丢失', $snapShotFile));
        }

        if (!is_readable($snapShotFile)) {
            throw new \Exception(sprintf('快照文件[%s]无读权限', $snapShotFile));
        }

        $buffer = trim(file_get_contents($snapShotFile));

        if (empty($buffer)) {
            return false;
        }

        $uris = explode(',', $buffer);

        $randKey = array_rand($uris);
        return $uris[$randKey];
    }
}

class AsyncRequestWrapper{
    protected $conn;

    /**
     * @var JMTextRpcClient
     */
    protected $rpcClient;

    /**
     * @var class name of the service.
     */
    protected $class;

    /**
     * @var method name of the service in this request.
     */
    protected $method;

    /**
     * 是否需要接受JMText协议，默认为TLV协议
     *
     * @var bool
     */
    protected $isJMText;
    /**
     * @param JMTextRpcClient $client
     * @param string $class class name of the service.
     * @param string $method method name of the class.
     * @param stream connection resource $conn
     */
    public function __construct(JMTextRpcClient $client, $class, $method, $conn, $isJMText = false){
        $this->rpcClient = $client;
        $this->class = $class;
        $this->method = $method;
        $this->conn = $conn;
        $this->isJMText = $isJMText;
    }

    /**
     * 异步请求情况下，通过result属性获取服务端返回结果。
     * @param $name
     * @return array
     * @throws Exception
     */
    public function __get($name){
        if($name == 'result'){
            try {
                if ($this->isJMText) {
                    return $this->rpcClient->recvJMText($this->conn);
                }
                return $this->rpcClient->recvTLV($this->conn);
            }
            catch(\Exception $ex){
                $ex = new Exception('RPC Exception: '.$this->class.'::'.$this->method.' '.$ex->getMessage());
                throw $ex;
            }
        }
        trigger_error('Try to access undefined property:ServiceClassWrapper::$'.$name, E_USER_WARNING);
    }

    public function __set($name, $val){
        // not allow to assign "result" from outside.
        if($name == 'result'){
            throw new Exception("Property 'result' is not settable for class '".get_called_class()."'");
        } else {
            $this->$name = $val;
        }
    }
}
/**
 * Class RequestWrapper 对请求对象重新封装，避免魔术方法(__call())的意外重载。
 * @package PHPClient
 */
class ServiceClassWrapper{
    /**
     * 是否异步发送请求.
     * @var bool
     */
    protected $asyncSend;

    /**
     * @var class name of the service.
     */
    protected $class;

    /**
     * @var JMTextRpcClient
     */
    public $rpcClient;

    /**
     * @param JMTextRpcClient $client
     * @param string $class class name of the service.
     */
    public function __construct(JMTextRpcClient $client, $class, $async=false){
        $this->rpcClient = $client;
        $this->class = $class;
        $this->asyncSend = $async;
    }

    /**
     * 调用 RPC 方法.
     *
     * @param string $method    PRC 方法名称.
     * @param mixed  $arguments 方法参数.
     *
     * @throws Exception 抛出开发用的错误提示信息.
     *
     * @return mixed 当为异步请求时返回一个request对象，否则直接返回服务端的结果.
     */
    public function __call($method, $arguments) {
        try{ // 这一层是为了捕获所有的异常，并且记录MNLogger日志后再抛出
            // 获取当前服务的配置.
            $config = $this->rpcClient->getConfig();

            $trace_logger = $this->rpcClient->getTraceLogger();
            $trace_logger && $trace_logger->RPC_CS($config['service'], $this->class, $method, $arguments);

            if ($this->rpcClient->useLark) {
                do {
                    try {
                        $conn = $this->rpcClient->openLarkConnection();
                    } catch (\Exception $e) { //连接异常跳出代码块往下走
                        break;
                    }
                    $buffer = $this->rpcClient->initRpcData($this->class, $method, $arguments);
                    $this->rpcClient->sendTLV($buffer, $conn);

                    //异步请求
                    if ($this->asyncSend) {
                        return new AsyncRequestWrapper($this->rpcClient, $this->class, $method, $conn);
                    }

                    return $this->rpcClient->recvTLV($conn);
                } while (0);
            }

            try {
                $conn = $this->rpcClient->openServerConnection();
            }catch (\Exception $e) { // 尝试mcpd
                $conn = $this->rpcClient->openMcpdConnection();
            }

            $buffer = $this->rpcClient->initRpcData($this->class, $method, $arguments);
            $this->rpcClient->sendJMText($buffer, $conn);
            try {
                if ($this->asyncSend) {
                    return new AsyncRequestWrapper($this->rpcClient, $this->class, $method, $conn, true);
                }
                return $this->rpcClient->recvJMText($conn);
            } catch (\Exception $e) {
                throw new Exception('JMText协议交互异常: '. $this->class.'::'.$method. ' '.$e->getMessage());
            }
        } catch (\Exception $e) {
            $trace_logger = $this->rpcClient->getTraceLogger();
            $trace_logger && $trace_logger->RPC_CR('EXCEPTION', strlen($e), $e);
            throw $e;
        }
    }
}

if(!function_exists('error_clear_last')){
    function error_clear_last(){
    }
}

spl_autoload_register(
    function ($className) {
        if (strpos($className, 'RpcClient_') !== 0)
            return false;

        eval(sprintf('class %s extends \PHPClient\JMTextRpcClient {}', $className));
    }, true, true
);
