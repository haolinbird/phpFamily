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
     * @var \MNLogger\TraceLogger
     */
    public static $rpcTraceLogger = null;

    /**
     * @var \MNLogger\SlowLogger
     */
    public static $rpcSlowLogger = null;

    /**
     * @var \MNLogger\EXLogger
     */
    public static $exLogger       = null;

    /**
     * 压缩方法.
     */
    private $rpcCompressor;
    
    protected $quotaAgentConnection = null;
    
    protected $quotaConfig = array();
    
    protected $quotaFileDir = '/dev/shm/phpserver-quota';
    
    protected $quotaFilePrefix = '__quota__';
    
    protected $quotaReportAddress = '';

    const VERSION = 'v1.0';




    /**
     * 验证数据是否接收完整.
     *
     * @param string $recv_str 接收到的数据流.
     *
     * @return integer|boolean
     */
    public function dealInput($recv_str) {
        return Lark::input($recv_str);
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
    public function dealProcess($recv_str) {
        try {
            if (($larkData = Lark::decode($recv_str, $err)) === false) {
                throw new Exception('TLV协议：解析失败:' . $err);
            }

            if ($larkData['tag'] != Lark::TAG_RPC_SERVER_RECV) {
                throw new Exception('TLV协议：TAG必须为' . Lark::TAG_RPC_SERVER_RECV);
            }

            if (($data = Text::decode($larkData['data'][0])) === false) {
                throw new Exception('Rpc协议：解析失败');
            }

            if (count($larkData['data']) >= 2) {
                $tmpData = json_decode($larkData['data'][1], true);
                self::$currentClientIp = $tmpData['client_ip'];
            } else {
                self::$currentClientIp = '0.0.0.0';
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
            
            $packet = json_decode($data, true);
            
            global $context, $owl_context;
            $context = array();
            if(isset($packet['CONTEXT'])) {
                $context = $packet['CONTEXT'];
            }
            $owl_context = null;
            if(isset($context['owl_context']))
            {
                $owl_context = json_decode($context['owl_context'], true);
            }

            if ($this->encrypt($packet['data'], PHPServerConfig::get('rpc_secret_key')) !== $packet['signature']) {
                throw new Exception('RpcWorker: You want to check the RPC secret key, or the packet has broken.');
            }

            $data = json_decode($packet['data'], true);
            if (empty($data['version']) || $data['version'] !== '2.0') {
                throw new Exception('RpcWorker: Hmm! We are now expect version 2.0.');
            }

            $prefix = 'RpcClient_';
            if (strpos($data['class'], $prefix) !== 0) {
                throw new Exception(sprintf('RpcWorker: Mmm! RPC class name should be prefix with %s.', $prefix));
            }
            $data['class'] = substr($data['class'], strlen($prefix));
            
            // 检查权限
            $_SERVER['REMOTE_ADDR'] = self::$currentClientIp;
            if($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
	            if(!PHPServerWorker::hasAuth($this->serviceName, $data['class'], $data['method'], $_SERVER['REMOTE_ADDR']))
	            {
	            	throw new \Exception("{$_SERVER['REMOTE_ADDR']} has no permissions to access {$this->serviceName} {$data['class']}->{$data['method']}. Permission denied.");
	            }
            }

            $process_begin_ts   = microtime(true);

            /***开始处理业务***/
            $this->process($data);
            /***结束处理业务***/

            try {
                $process_end_ts = microtime(true);
                $process_used_time = ($process_end_ts - $process_begin_ts) * 1000;
                // 慢日志落盘
                if ($process_used_time >= $this->slowlogTime) {
                    $data['process_used_time_ms'] = $process_used_time;
                    $data['password'] = '******';
                    \MNLogger\SlowLogger::instance()->log(json_encode($data));
                }

                // monitor|exception日志落盘
                if (is_callable(array('MNLogger\TraceLogger', 'flush'))) {
                    MNLogger\TraceLogger::flush();
                }
            }catch (\Exception $e) {
            }

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
        $data = json_encode($data);
        if ($this->rpcCompressor === 'GZ') {
            $data = @gzcompress($data);
        }
        $encode_text = Text::encode($data);

        $context = json_encode(array());//上下文，先预留为空
        $encode_text = Lark::encode(Lark::TAG_RPC_SERVER_SEND, array($encode_text, $context));
        $this->sendToClient($encode_text);

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
}
