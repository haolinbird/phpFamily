<?php
namespace EventClient;

class RpcBase {
    const PROTOCOL_JSON = 'json';
    const PROTOCOL_MSGPACK = 'msgpack';
    const PROTOCOL_PHP = 'php';
    protected $protocol = 'json';
    protected function deserialize($data) {
        switch ($this->protocol) {
            case self::PROTOCOL_JSON :
                return json_decode ( $data, true );
                continue;
            case self::PROTOCOL_MSGPACK :
                return msgpack_unpack ( $data );
                continue;
            case self::PROTOCOL_PHP :
                return unserialize ( $data );
                continue;
            default :
                return false;
        }
    }
    protected function serialize($data) {
        switch ($this->protocol) {
            case self::PROTOCOL_JSON :
                return json_encode ( $data );
                continue;
            case self::PROTOCOL_MSGPACK :
                return msgpack_pack ( $data );
                continue;
            case self::PROTOCOL_PHP :
                return serialize ( $data );
                continue;
            default :
                return false;
        }
    }
    /**
     * <h4>Data</h4>
     * <pre>
     * user => .
     * ..
     * secret_key => ...
     * class => ...
     * method => ...
     * </pre>
     * 
     * @param array $data            
     * @return string
     */
    protected function generateSign($data) {
        return md5 ( $data ['user'] . $data ['secret_key'] . $data ['class'] . $data ['method'] );
    }
}

/**
 * Exception code as below:<br/>
 * <b>Error code start with "51" are redis exceptions.</b><br/>
 * <pre>
 * <code>
 * 51000 => Emepty class name.
 * 51500 => Server error.
 * 51501 => Bad service rpc data.
 * 51502 => Transaction not completed
 * </code>
 * </pre>
 * 
 * @author Su Chao<suchaoabc@163.com>
 */
class RpcClientException extends \Exception {
}
/**
 * transaction logical exceptions.<br />
 * Exception code as below:<br/>
 * <b>Error code start with "53" are redis exceptions.</b><br/>
 * 
 * @author Su Chao<suchaoabc@163.com>
 */
class RpcLogicalException extends \Exception {
    protected $rpcFile, $rpcLine, $rpcTrace, $rpcTraceString, $rpcExceptionClass;
    public function __construct($e) {
        parent::__construct ( $e->getMessage (), $e->getCode () );
        $this->rpcExceptionClass = get_class ( $e );
        $this->rpcFile = $e->getFile ();
        $this->rpcLine = $e->getLine ();
        $this->rpcTrace = $e->getTrace ();
        $this->rpcTraceString = $e->getTraceAsString ();
    }
    public function rpcTrace() {
        return $this->rpcTrace;
    }
    public function rpcTraceString() {
        return $this->rpcTraceString;
    }
    public function rpcExceptionClass() {
        return $this->rpcExceptionClass;
    }
}

/**
 * Client base class for communicating with the server via HTTP.<br />
 * <h4>Example</h4>
 * <pre>
 * <code>
 * $rpcCfg = array('protocol'=>'json',//optional.default is json.if you have
 * pecl of msgpack installed then msgpack will be prefered
 * 'user'=>'koubei',
 * 'secret_key'=>'sl-fwfl12',
 * 'url'=>'http://event.koubei.jumei.com/rpc.php'
 * );
 * $rpcClient = new RpcClient($rpcCfg);
 * //or if you have a default config in Cfg/RpcClient.php then
 * $defaultClient = new RpcClient();
 * $result = $rpcClient->setClass('Broadcast')->Subscribe('UserUpgradeNotice');
 * </code>
 * </pre>
 *
 * @author Su Chao<suchaoabc@163.com>
 */
class RpcClient extends RpcBase {
    protected static $instances = array();
    protected $className;
    protected $methodName;
    protected $args;
    protected $lastResponseText;
    protected $returnData;
    protected static $configs = array();
    
    /**
     * client configrations.
     *
     * @var array
     */
    protected $cfg = array (
            'protocol' => 'json'
    );
    
    protected $debugInfo = array ();

    /**
     * 设置客户端所需的配置。
     *
     * @param array $configs
     */
    public static function config(array $configs)
    {
        static::$configs = $configs;
    }

    /**
     * get an instance with the specified config
     * 
     * @param string $endpoint cfg name
     * @return \EventClient\Lib\RpcClient
     */
    public static function instance($endpoint = 'default')
    {
        if(!isset(self::$instances[$endpoint]))
        {
            self::$instances[$endpoint] = new self($endpoint);
        }
        return self::$instances[$endpoint];
    }
    
    /**
     * return the current configurations 
     */
    public function getCfg()
    {
        return $this->cfg;
    }

    /**
     *
     * @param string|object $endpoint configuration name of RPC.
     *
     * @throws  \EventClient\RpcClientException
     */
    public function __construct($endpoint = 'default') {
        if (is_array ( $endpoint ) && isset ( $endpoint ['user'] ) && isset ( $endpoint ['secret_key'] ) && isset ( $endpoint ['url'] )) {
            if (! isset ( $endpoint ['debug'] ))
                $endpoint ['debug'] = false;
            $this->cfg = array_merge ( $this->cfg, $endpoint);
        }
        else
        {
            if(!static::$configs)
            {
                static::$configs = (array) new \Config\EventClient;
            }

            if(!isset(static::$configs[$endpoint]))
            {
                throw new RpcClientException('EventClient "'.$endpoint.'" not configured!');
            }
            else
            {
                if(!isset(static::$configs[$endpoint]['debug']))
                {
                    static::$configs[$endpoint]['debug'] = false;
                }
                $this->cfg = static::$configs[$endpoint];
            }
        }
        $this->protocol = $this->cfg['protocol'];
    }

    /**
     * set the remote class name
     *
     * @param string $className
     * @return \Event\Client\Lib\RpcCient
     */
    public function setClass($className) {
        $this->className = $className;
        return $this;
    }
    
    public function __get($name)
    {
        switch($name)
        {
            case 'cfg':
                return $this->getCfg();
                continue;
            default:
                trigger_error('Try to get undefined property: '.$name.' of '.__CLASS__, E_USER_WARNING);
                continue;
        }
    }
    public function __call($name, $args) {
        return $this->callService ( $name, $args );
    }
    public function callService($name, $args) {
        if (empty ( $this->className )) {
            throw new RpcClientException ( 'Class name not set.', 51000 );
        }
        $this->methodName = $name;
        $this->args = $args;
        $rpcData = array ();
        $rpcData ['data'] = array (
                'protocol' => $this->cfg ['protocol'],
                'class' => $this->className,
                'method' => $this->methodName,
                'params' => $this->args,
                'user' => $this->cfg ['user'],
                'sign' => $this->generateSign ( array (
                        'user' => $this->cfg ['user'],
                        'secret_key' => $this->cfg ['secret_key'],
                        'class' => $this->className,
                        'method' => $this->methodName
                ) )
        );
        $rpcData ['data'] = $this->serialize ( $rpcData ['data'] );
        $requestGetString = http_build_query ( array (
                'protocol' => $this->cfg ['protocol']
        ), '', '&' );

        $url = $this->cfg ['url'] . '?' . $requestGetString;
        $this->lastResult = array ();
        $ch = curl_init ( $url );

        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $rpcData );
        curl_setopt ( $ch, CURLOPT_HEADER, false );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt ( $ch, CURLOPT_FORBID_REUSE, true );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
        $this->lastResponseText = curl_exec ( $ch );
        if ($this->cfg ['debug'])
            $this->debugInfo ['reponse_text'] = &$this->lastResponseText;
        if (curl_errno ( $ch )) {
            throw new RpcClientException ( 'Service timeout or connection failed!' . curl_error ( $ch ), 51500 );
        }
        $this->returnData = $returnData = $this->deserialize ( $this->lastResponseText );
        if (! $returnData) {
            $this->throwException ( new RpcClientException ( 'Bad service rpc data!', 51501 ) );
        }
        if (isset ( $returnData ['Exception'] )) 
        {
            $ex = new RpcClientException('Caught exception from server: '.$returnData['Exception'], 51502);
            $this->throwException ( $ex );
        }
        if (! array_key_exists ( 'return', $returnData ))
        {
            $this->throwException ( new RpcClientException ( 'Bad service rpc return!', 51501 ) );
        }
        
        return $returnData ['return'];
    }
    protected function throwException($e) {
        throw $e;
    }
    /**
     * return verbose debug information.
     */
    public function debugInfo() {
        return $this->debugInfo;
    }
    public function getReturnData() {
        return $this->returnData;
    }
}