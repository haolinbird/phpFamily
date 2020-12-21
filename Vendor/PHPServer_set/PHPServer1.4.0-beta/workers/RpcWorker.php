<?php
/**
 * 标准 RpcWorker.
 *
 * @author Xiangheng Li <xianghengl@jumei.com>
 */

/**
 * RpcWorker 抽象类实现.
 */
abstract class RpcWorker extends PHPServerWorker implements IWorker
{

    /**
     * 压缩方法.
     */
    private $rpcCompressor;
    
    protected $quotaAgentConnection = null;
    
    protected $quotaConfig = array();
    
    protected $quotaFileDir = '/dev/shm/phpserver-quota';
    
    protected $quotaFilePrefix = '__quota__';

    protected $lastClientDataEncoder = self::DATA_ENCODER_JSON;
    
    protected $quotaReportAddress = '';
    const DATA_ENCODER_JSON = 'json';
    const DATA_ENCODER_MSGPACK = 'msgpack';
    
    /**
     * 验证数据是否接收完整.
     *
     * @param string $recv_str 接收到的数据流.
     *
     * @return integer|boolean
     */
    public function dealInput($recv_str)
    {
        return Text::input($recv_str);
    }
    
    public function serve($is_daemon = true)
    {
    	 $port = PHPServerConfig::get('workers.QuotaAgent.port');
    	 if($port)
    	 {
    	 	$this->quotaReportAddress = 'udp://127.0.0.1:'.$port;
    	 }
         $quota_file_dir = PHPServerConfig::get('quota_file_dir');
    	 if($quota_file_dir)
    	 {
    		 $this->quotaFileDir = $quota_file_dir;
    	 }
    	 $this->loadQuotaConfig();
    	 parent::serve($is_daemon);
    }
    
    /**
     * 载入配额配置
     */
    protected function loadQuotaConfig()
    {
        if(is_file(__DIR__. '/../config/other.php'))
    	{
    		include __DIR__. '/../config/other.php';
    		if(isset($quota_config) && is_array($quota_config))
    		{
    			$this->quotaConfig = $quota_config;
    		}
    	}
    }

    /**
     * 处理数据流.
     *
     * @param string $recv_str 接收到的数据流.
     *
     * @throws Exception 抛出开发时错误.
     *
     * @return void
     */
    public function dealProcess($recv_str)
    {
        try {
            if (($data = Text::decode($recv_str)) === false) {
                throw new Exception('RpcWorker: You want to check the RPC protocol.');
            }

            if ($data['command'] === 'TEST' && $data['data'] === 'PING') {
                $this->send('PONG');
                return;
            }

            $this->rpcCompressor = null;
            if ($data['command'] !== 'RPC') {
                throw new Exception('RpcWorker: Oops! I am going to do nothing but RPC.');
            }
            
            $data = $data['data'];
            // 根据数据组装结构，如果使用json编码，第一个字符必然为"{"
            if($data{0} === '{'){
                $this->lastClientDataEncoder = self::DATA_ENCODER_JSON;
            } else {
                $this->lastClientDataEncoder = self::DATA_ENCODER_MSGPACK;
            }
            $packet = $this->decodeData($data);
            
            global $context, $owl_context;
            $context = array();
            if(isset($packet['CONTEXT']))
            {
                $context = $packet['CONTEXT'];
            }
            $owl_context = null;
            if(isset($context['owl_context']))
            {
                $owl_context = $this->decodeData($context['owl_context']);
            }

            if ($this->encrypt($packet['data'], PHPServerConfig::get('rpc_secret_key')) !== $packet['signature']) {
                throw new Exception('RpcWorker: You want to check the RPC secret key, or the packet has broken.');
            }

            $data = $this->decodeData($packet['data']);
            if (empty($data['version']) || $data['version'] !== '2.0') {
                throw new Exception('RpcWorker: Hmm! We are now expect version 2.0.');
            }

            $prefix = 'RpcClient_';
            if (strpos($data['class'], $prefix) !== 0) {
                throw new Exception(sprintf('RpcWorker: Mmm! RPC class name should be prefix with %s.', $prefix));
            }
            $data['class'] = substr($data['class'], strlen($prefix));
            
            // 检查权限
            $_SERVER['REMOTE_ADDR'] = $this->getRemoteIp();
            if($_SERVER['REMOTE_ADDR'] !== '127.0.0.1')
            {
	            if(!PHPServerWorker::hasAuth($this->serviceName, $data['class'], $data['method'], $_SERVER['REMOTE_ADDR']))
	            {
	            	throw new \Exception("{$_SERVER['REMOTE_ADDR']} has no permissions to access {$this->serviceName} {$data['class']}->{$data['method']}. Permission denied.");
	            }
            }

            $this->process($data);
        } catch (Exception $ex) {
            $this->send(
                array(
                    'exception' => array(
                        'class' => get_class($ex),
                        'message' => $ex->getMessage(),
                        'code' => $ex->getCode(),
                        'file' => $ex->getFile(),
                        'line' => $ex->getLine(),
                        'traceAsString' => $ex->getTraceAsString(),
                    )
                )
            );
        }
    }
    
    protected function checkQuota($class, $method, $user)
    {
    	// 这个该用户调用这个接口没严格限定配额
    	if(empty($this->quotaConfig[$this->projectName][$class][$method][$user]['strict']))
    	{
    		return;
    	}
    	
    	if(isset($this->quotaConfig[$this->projectName][$class][$method][$user]['quota']) && !$this->quotaConfig[$this->projectName][$class][$method][$user]['quota'])
    	{
    		throw new \Exception("$class::$method::$user Quota is zero");
    	}
    	
    	$this->reportQuotaData($class, $method, $user);
    	
    	// 清理缓存
    	clearstatcache();
    	$quota_file = $this->quotaFileDir."/{$this->quotaFilePrefix}$class-$method-$user";
    	// 有这个文件代表配额已经消耗光
    	if(is_file($quota_file))
    	{
    		throw new \Exception("$class::$method::$user Quota exceeded");
    	}
    	return;
    }
    
    protected function reportQuotaData($class, $method, $user)
    {
    	if(!$this->quotaReportAddress)
    	{
    		return;
    	}
        $client = stream_socket_client($this->quotaReportAddress, $errno, $errmsg);
        @stream_socket_sendto($client, json_encode(array('class'=>$class, 'method'=>$method, 'user'=>$user)));
    }

    /**
     * 业务处理方法.
     *
     * @param mixed $data RPC 请求数据.
     *
     * @return void
     */
    abstract protected function process($data);

    /**
     * 发送数据回客户端.
     *
     * @param mixed $data 业务数据.
     *
     * @return void
     */
    protected function send($data)
    {
        $data = $this->encodeData($data);
        if ($this->rpcCompressor === 'GZ') {
            $data = @gzcompress($data);
        }
        $this->sendToClient(Text::encode($data));
    }

    /**
     * 数据签名.
     *
     * @param string $data   待签名的数据.
     * @param string $secret 私钥.
     *
     * @return string
     */
    private function encrypt($data, $secret)
    {
        return md5($data . '&' . $secret);
    }

    protected function encodeData($data){
        if($this->lastClientDataEncoder === self::DATA_ENCODER_JSON){
            $s = json_encode($data);
            if($s === false){
                throw new \Exception("Failed to encode data: ".json_last_error_msg());
            } else {
                return $s;
            }
        } else if($this->lastClientDataEncoder === self::DATA_ENCODER_MSGPACK) {
            $s = msgpack_pack($data);
            if($s === false){
                $err = error_get_last();
                if($err){
                    $err = $err['message'];
                } else {
                    $err = 'unknown encode error.';
                }
                throw new \Exception("Failed to encode data:".$err);
            } else{
                return $s;
            }
        } else {
            throw new \Exception("Invalid data encoder:{$this->lastClientDataEncoder}");
        }
    }

    protected function decodeData($data){
        if($data{0} === '{'){
            $re = json_decode($data, true);
            if($re === null){
                if(json_last_error()){
                    throw new \Exception("Failed to decode client data: ".json_last_error_msg());
                } else {
                    return $re;
                }
            } else{
                return $re;
            }
        } else {
            $re = msgpack_unpack($data);
            if(!is_array($re)){
                $err = error_get_last();
                if($err){
                    $err = $err['message'];
                } else {
                    $err = 'unknown decode error.';
                }
                throw new \Exception("Failed to decode client data:".$err);
            }
            return $re;
        }
    }
}

// 兼容php5.5以下版本.
if(!function_exists('json_last_error_msg')){
    function json_last_error_msg(){
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return 'No error';
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }
}