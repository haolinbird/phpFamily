<?php
namespace Thrift;
/**
 * 
 * thrift异步客户端实例
 * @author liangl
 *
 */
class ThriftInstance
{

    // lark保存的服务快照目录
    const LARK_SNAPSHOT_DIR = '/var/lib/lark/service_snapshot/';


    /**
     * 异步发送前缀
     * @var string
     */
    const ASYNC_SEND_PREFIX = 'asend_';
    
    /**
     * 异步接收后缀
     * @var string
     */
    const ASYNC_RECV_PREFIX = 'arecv_';


    protected $larkAddress = 'tcp://127.0.0.1:12311';

    protected $useLark = true;

    protected $remoteAddress;

    protected $remoteType;

    /**
     * 服务名（被调用的类名）
     * @var string
     */
    public $serviceName = '';
    
    /**
     * 项目名
     * @var string
     */
    public $projectName = '';
    
    /**
     * 是否使用原生thrift协议
     * @var bool
     */
    public $rawThrift = false;
    
    /**
     * thrift实例
     * @var array
     */
    protected $thriftInstance = null;
    
    /**
     * thrift异步实例['asend_method1'=>thriftInstance1, 'asend_method2'=>thriftInstance2, ..]
     * @var array
     */
    protected $thriftAsyncInstances = array();


    /**
     * traceLogger
     * @var traceLogger
     */
    protected static $traceLogger = null;

    /**
     * 当前实例使用的配置
     * @var array
     */
    protected $config = array();

    public function config($config) {
        $this->useLark = defined('USE_THRIFT_LARK') ? USE_THRIFT_LARK : true;

        if (isset($config['use_lark'])) {
            $this->useLark = $config['use_lark'];
        }

        if ($this->useLark && empty($config['service'])) {
            throw new \Exception('使用lark必须配置service字段');
        }
        $this->config = $config;
    }
    
    /**
     * 初始化工作
     * @return void
     */
    public function __construct($serviceName, $projectName = '', $rawThrift = false) {
        if(empty($serviceName)) {
            throw new \Exception('serviceName can not be empty', 500);
        }
        
        try {
            !self::$traceLogger && self::$traceLogger = @\MNLogger\TraceLogger::instance();
        } catch(\Exception $e){}
        
        $this->serviceName = $serviceName;
        $this->projectName = $projectName ? $projectName : $serviceName;
        $this->rawThrift = $rawThrift;
        $classNamePortions = explode('\\', $serviceName);
        $classname = '\Provider\\' . $this->serviceName . "\\" . array_pop($classNamePortions) . "Client";
        if(!class_exists($classname, false)) {
            $this->includeFile($classname);
        }
        
    }
    
    /**
     * 方法调用
     * @param string $name
     * @param array $arguments
     * @return mix
     */
    public function __call($method_name, $arguments)
    {
        $time_start = microtime(true);
        // 异步发送
        if(0 === strpos($method_name ,self::ASYNC_SEND_PREFIX)) {
            $real_method_name = substr($method_name, strlen(self::ASYNC_SEND_PREFIX));
            \Thrift\Context::put('methodName', $real_method_name);
            $arguments_key = var_export($arguments,true);
            $method_name_key = $method_name . $arguments_key;
            // 判断是否已经有这个方法的异步发送请求
            if(isset($this->thriftAsyncInstances[$method_name_key])) {
                // 如果有这个方法发的异步请求，则删除
                $this->thriftAsyncInstances[$method_name_key] = null;
                unset($this->thriftAsyncInstances[$method_name_key]);
            }

            try{
                self::$traceLogger && self::$traceLogger->RPC_CS($this->remoteAddress, $this->serviceName, $method_name, $arguments);
                
                $instance = $this->__instance();
                $callback = array($instance, 'send_'.$real_method_name);
                if(!is_callable($callback)) {
                    throw new \Exception($this->serviceName.'->'.$method_name. ' not callable', 1400);
                }
                $ret = call_user_func_array($callback, $arguments);
            } catch (\Exception $e) {
                self::$traceLogger && self::$traceLogger->RPC_CR('EXCEPTION', strlen($e), $e->__toString());
                throw $e;
            }


            // 保存实例
            $this->thriftAsyncInstances[$method_name_key] = $instance;
            return $ret;
        }

        // 异步接收
        if(0 === strpos($method_name, self::ASYNC_RECV_PREFIX)) {
            $real_method_name = substr($method_name, strlen(self::ASYNC_RECV_PREFIX));
            $send_method_name = self::ASYNC_SEND_PREFIX.$real_method_name;
            $arguments_key = var_export($arguments,true);
            $method_name_key = $send_method_name . $arguments_key;
            // 判断是否有发送过这个方法的异步请求
            if(!isset($this->thriftAsyncInstances[$method_name_key])) {
                $e = new \Exception($this->serviceName."->$send_method_name(".implode(',',$arguments).") have not previously been called, call " . $this->serviceName."->".self::ASYNC_SEND_PREFIX.$real_method_name."(".implode(',',$arguments).") first", 1500);
                throw $e;
            }
            
            // 创建个副本
            $instance = $this->thriftAsyncInstances[$method_name_key];
            // 删除原实例，避免异常时没清除
            $this->thriftAsyncInstances[$method_name_key] = null;
            unset($this->thriftAsyncInstances[$method_name_key]);
            
            try{
                $callback = array($instance, 'recv_'.$real_method_name);
                if(!is_callable($callback)) {
                    throw new \Exception($this->serviceName.'->'.$method_name. ' not callable', 1400);
                }
                // 接收请求
                $ret = call_user_func_array($callback, array());
            }catch (\Exception $e)
            {
                self::$traceLogger && self::$traceLogger->RPC_CR('EXCEPTION', strlen($e), $e->__toString());
                throw $e;
            }
            self::$traceLogger && self::$traceLogger->RPC_CR('SUCCESS', strlen(json_encode($ret)));

            return $ret;
        }
        
        \Thrift\Context::put('methodName', $method_name);

        try {
            self::$traceLogger && self::$traceLogger->RPC_CS($this->remoteAddress, $this->serviceName, $method_name, $arguments);

            $this->thriftInstance = $this->__instance();
            $callback = array($this->thriftInstance, $method_name);
            if(!is_callable($callback)) {
                throw new \Exception($this->serviceName.'->'.$method_name. ' not callable', 1400);
            }
            $ret = call_user_func_array($callback, $arguments);
            $this->thriftInstance = null;
        } catch(\Exception $e) {
            $this->thriftInstance = null;
            self::$traceLogger && self::$traceLogger->RPC_CR('EXCEPTION', strlen($e), $e->__toString());
            throw $e;
        }

        self::$traceLogger && self::$traceLogger->RPC_CR('SUCCESS', strlen(json_encode($ret)));
        
        return $ret;
    }
    
    protected function _getIpPort($address) {
        if (strpos($address, 'unix://') === 0) {
            return array(
                'host'  => $address,
                'port'  => -1,
            );
        }

        if (strpos($address, 'tcp://') === 0) {
            $address = substr($address, 6);
        }

        $arr = explode(':', $address);
        return array(
            'host'  => $arr[0],
            'port'  => $arr[1],
        );
    }

    /**
     * 获取一个实例
     * @return instance
     */
    protected function __instance() {
        // 服务端会用serviceName来推断provider目录
        if (\Thrift\Context::get('serverName') != $this->serviceName) {
            \Thrift\Context::put('serverName', $this->serviceName);
        }

        $pname = isset($this->config['protocol']) ? $this->config['protocol'] : 'binary';

        $transport = $this->_getTransport();

        $protocolName = $this->getProtocol($pname);
        $protocol = new $protocolName($transport);
        $classname = '\Provider\\' . $this->serviceName . "\\" . $this->serviceName . "Client";

        if (!class_exists($classname, false)) {
            $classname = $this->includeFile($classname);
        }

        return new $classname($protocol);
    }

    /**
     *
     */
    protected function _getTransport() {
        if (defined('LARK_ADDRESS')) {
            $this->larkAddress = LARK_ADDRESS;
        }

        $arr = $this->_getIpPort($this->larkAddress);
        $socket = new \Thrift\Transport\TSocket($arr['host'], $arr['port']);
        $transport = new \Thrift\Transport\TFramedTransport($socket);
        $transport->setService(@$this->config['service']);
        $transport->setServerType(\Thrift\Transport\TTransport::CONN_TYPE_LARK);
        $transport->setSpecifiedAddress(@$this->config['specified_address']);
        $transport->setTag(\Thrift\Transport\Lark::TAG_RPC_CLIENT_SEND);
        if($this->rawThrift) {
            $transport->rawThrift = true;
        }

        // 是否成功建立连接
        $connected = false;
        if ($this->useLark) {
            try { // lark
                $transport->open();
                $connected = true;
                $this->remoteType = \Thrift\Transport\TTransport::CONN_TYPE_LARK;
                $this->remoteAddress = $this->larkAddress;
            } catch (\Exception $e) {
		error_log($e->getMessage()."\n".$e->getTraceAsString());
	    }
        }

        if (!$connected) { // 直连
            $this->remoteAddress = $this->getOneAddress();
            $arr = $this->_getIpPort($this->remoteAddress);
            $socket = new \Thrift\Transport\TSocket($arr['host'], $arr['port']);
            $transport = new \Thrift\Transport\TFramedTransport($socket);
            $transport->setService(@$this->config['service']);
            $transport->setServerType(\Thrift\Transport\TTransport::CONN_TYPE_SERVER);
            if($this->rawThrift) {
                $transport->rawThrift = true;
            }
            $transport->open();
            $this->remoteType = \Thrift\Transport\TTransport::CONN_TYPE_SERVER;
        }


        $timeout = @$this->config['timeout'];
        \Thrift\Context::put('thrift_recv_timeout', $timeout);
        if($timeout >= 1) {
            $socket->setRecvTimeout($timeout*1000 + 10);
        } else {
            $socket->setRecvTimeout(30000);
        }

        $socket->setSendTimeout(5000);

        return $transport;
    }

    protected function getOneAddress() {
        $nodes = @$this->config['nodes'];

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

    /**
     * 载入provider文件
     * @return string the real classname
     */
    protected function includeFile($classname)
    {
        $config = Client::config();
        $provider_dir = $config[$this->projectName]['provider'];
        $provider_service_dir = $provider_dir.'/'.str_replace('\\', DIRECTORY_SEPARATOR, $this->serviceName);
        $include_file_array = glob($provider_service_dir.'/*.php');
        foreach($include_file_array as $file)
        {
            include_once $file;
        }
        if(!class_exists($classname))
        {// 尝试使用标准的命名方式, 如: Lv1\ClassName\ClassName 其实应该是Lv1\ClassName,即就是serviceName.
            $std_provider_service_dir = dirname($provider_service_dir);
            $include_file_array = glob($std_provider_service_dir.'/*.php');
            foreach($include_file_array as $file)
            {
                include_once $file;
            }
            $std_classname = '\Provider\\' . $this->serviceName . 'Client';
            if(!class_exists($std_classname)){
                throw new \Exception("Can not find class $classname or $std_classname in directory $provider_service_dir or $std_provider_service_dir");
            }
            return $std_classname;
        }
        return $classname;
    }
    
    /**
     * getProtocol
     * @param string $key
     * @return string
     */
    protected function getProtocol($key=null) {
        $protocolArr = array(
            'binary' =>'Thrift\Protocol\TLarkBinaryProtocol',
            'compact'=>'Thrift\Protocol\TLarkCompactProtocol',
            'json'   =>'Thrift\Protocol\TLarkJSONProtocol',
        );
        if ($this->remoteType == \Thrift\Transport\TTransport::CONN_TYPE_SERVER) {
            $protocolArr = array(
                'binary' =>'Thrift\Protocol\TBinaryProtocol',
                'compact'=>'Thrift\Protocol\TCompactProtocol',
                'json'   =>'Thrift\Protocol\TJSONProtocol',
            );
        }
        return isset($protocolArr[$key]) ? $protocolArr[$key] : $protocolArr['binary'];
    }
}
