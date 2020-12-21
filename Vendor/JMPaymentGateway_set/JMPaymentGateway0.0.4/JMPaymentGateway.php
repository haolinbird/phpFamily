<?php
/**
 * 聚美支付网关协议实现.
 * 
 * @author xiw <xiw4@jumei.com>
 */

namespace JMPaymentGateway;

use \Exception;

/**
 * 聚美支付网关协议实现.
 */
class JMPaymentGateway
{
    protected $gatewayUrl = 'https://pay.jumei.com/gateway?';
    protected $gatewayUrls = array(); // 兼容多个网关地址
    protected $hostname;
    protected $user;
    protected $secret;

    protected $requestMethod = 'POST';
    protected $timeOut = 5;
    protected $showOutput = false;

    private static $instances = array();
    private static $configs;

    /**
     * 设置或读取配置信息.
     *
     * @param array $config 配置信息.
     *
     * @return array|void
     */
    public static function config(array $config = array())
    {
        if (!empty($config)) {
            self::$configs = $config;
        } else {
            return self::$configs;
        }
    }

    /**
     * 获取网关对象实例.
     * 
     * @param string $endpoint 配置信息.
     * 
     * @return JMPaymentGateway
     */
    public static function instance($endpoint = 'default')
    {
        if (!isset(self::$instances[$endpoint])) {
            self::$instances[$endpoint] = new self($endpoint);
        }
        return self::$instances[$endpoint];
    }

    /**
     * 构造函数.
     *
     * @param string $endpoint 配置信息.
     *
     * @throws Exception 抛出开发错误信息.
     */
    private function __construct($endpoint = 'default')
    {
        if (empty(self::$configs)) {
            self::$configs = (array) new \Config\JMPaymentGateway();
        }

        $config = self::config();
        if (empty($config)) {
            throw new Exception('JMPaymentGateway: Missing configurations');
        }
        
        if (isset($config[$endpoint])) {
            $this->init($config[$endpoint]);
        } else {
            throw new Exception('JMPaymentGateway: Missing configuration for ' . $endpoint);
        }
    }

    /**
     * 设定网关请求方式.
     * 
     * @param string $method 请求方式.
     * 
     * @return void
     */
    public function setRequestMethod($method)
    {
        $this->requestMethod = strtoupper($method);
    }

    /**
     * 设定请求超时时间.
     * 
     * @param integer $timeout 超时时间.
     * 
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->timeOut = $timeout;
    }

    /**
     * 是否显示服务端输出的信息
     * 
     * @param boolean $v Boolean.
     * 
     * @return \JMPaymentGateway\JMPaymentGateway
     */
    public function setShowOutput($v)
    {
        $this->showOutput = $v;
        return $this;
    }

    /**
     * 调用远程接口.
     *
     * @param array  $params 请求参数.
     * @param string $fn     回调方式.
     *
     * @throws Exception 抛出开发用的错误提示信息.
     *
     * @return mixed
     */
    public function request(array $params, $fn = null)
    {
        try {
            $requestParams = $this->buildRequestPara(array('params' => json_encode($params)));
            $ctx = $this->remoteRequest($requestParams);
        } catch (Exception $e) {
            throw $e;
        }

        if (is_array($ctx) && isset($ctx['exception']) && is_array($ctx['exception'])) {
            throw new Exception('JMPaymentGateway Server Exception: ' . var_export($ctx['exception'], true));
        }

        if (isset($ctx['output']) && $ctx['output'] && $this->showOutput) {
            echo $ctx['output'];
        }
        if ($fn) {
            if (self::hasErrors($ctx)) {
                $fn(null, $ctx);
            } else {
                $ctx = isset($ctx['response']) ? $ctx['response'] : $ctx;
                $fn($ctx, null);
            }
        } else {
            if (isset($ctx['is_success']) && $ctx['is_success'] == 'T') {
                return $ctx['response'];
            } else {
                return $ctx;
            }
        }
    }

    /**
     * 生成网关签名数组.
     * 
     * @param mixed $params 请求前的参数数组.
     * 
     * @return array
     */
    public function buildRequestPara($params)
    {
        if (!$params) {
            return array();
        }
        
        if (isset($params['user'])) {
            throw new \Exception('ILLEGAL_REQUEST_USER');
        }

        $params['user'] = $this->user;
        $params['timestamp'] = time();

        $paraSort = $this->processParam($params);
        $paraSort['sign'] = $this->buildRequestMysign($paraSort);

        return $paraSort;
    }

    /**
     * 发起远程调用协议.
     *
     * @param array $data RPC 数据.
     *
     * @throws \Exception 抛出开发用的错误提示信息.
     *
     * @return mixed
     */
    protected function remoteRequest(array $data)
    {
        $this->executionTimeStart = microtime(true);

        $url = $this->getRequestUrl();
        $error = null;
        if ($this->requestMethod == 'GET') {
            $response = $this->getHttpResponseGET($url, $data, $this->timeOut, $error);
        } else {
            $response = $this->getHttpResponsePOST($url, $data, $this->timeOut, $error);
        }
        if ($error) {
            throw new \Exception(sprintf('JMPaymentGateway: Got wrong protocal: %s', $error));
        }

        $ctx = @json_decode($response, true);
        if ($response === false || !$ctx) {
            throw new \Exception(
                sprintf(
                    'JMPaymentGateway: Network %s may time out (%.3fs), or there ara fatal errors on the server, the response is: %s',
                    $this->gatewayUrl,
                    $this->executionTime(),
                    $response
                )
            );
        }
        return $ctx;
    }

    /**
     * 生成签名结果.
     *
     * @param mixed $paraSort 已排序要签名的数组.
     *
     * @return string
     */
    protected function buildRequestMysign($paraSort)
    {
        $prestr = $this->createLinkstring($paraSort);
        return md5($prestr . '&' . $this->secret);
    }

    /**
     * 把数组所有元素,按照“参数=参数值”的模式用“&”字符拼接成字符串.
     *
     * @param array   $para      需要处理的数组元素.
     * @param boolean $urlencode 是否urlencode编码.
     *
     * @return string
     */
    protected function createLinkstring(array $para, $urlencode = false)
    {
        $arg = "";
        while (list($key, $val) = each($para)) {
            if ($urlencode) {
                $val = urlencode($val);
            }
            $arg .= $key. "=" . $val . "&";
        }
        // 去掉最后一个&字符.
        $arg = substr($arg, 0, count($arg) - 2);
        return $arg;
    }

    /**
     * 处理参数.
     *
     * @param mixed $parameter 参数.
     *
     * @return array
     */
    protected function processParam($parameter)
    {
        $para = array();
        while (list($key, $val) = each($parameter)) {
            if ($key == "sign" || $val == "") {
                continue;
            } else {
                $para[$key] = $parameter[$key];
            }
        }
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 远程获取数据，POST模式.
     *
     * @param string  $url     请求地址.
     * @param mixed   $data    Post的数据.
     * @param integer $timeout 超时时间.
     * @param string  &$error  引用错误.
     *
     * @return string
     */
    protected function getHttpResponsePOST($url, $data, $timeout = 5, &$error = '')
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if ($this->hostname) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $this->hostname));
        }
        $responseText = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        return $responseText;
    }

    /**
     * 远程获取数据，GET模式.
     *
     * @param string  $url        请求地址.
     * @param mixed   $parameters 附加参数.
     * @param integer $timeout    请求超时.
     * @param string  &$error     引用错误.
     *
     * @return string
     */
    protected function getHttpResponseGET($url, $parameters = array(), $timeout = 5, &$error = '')
    {
        if ($parameters) {
            if (is_array($parameters)) {
                $url .= http_build_query($parameters, '', '&');
            } else {
                $url .= $parameters;
            }
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        if ($this->hostname) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: ' . $this->hostname));
        }
        $responsText = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        return $responsText;
    }

    /**
     * 检查返回结果是否包含错误信息.
     *
     * @param mixed &$ctx 调用RPC接口时返回的数据.
     *
     * @return boolean
     */
    public static function hasErrors(&$ctx)
    {
        if (is_array($ctx) && isset($ctx['is_success']) && $ctx['is_success'] === 'F') {
            if (isset($ctx['errors'])) {
                $ctx = $ctx['errors'];
            } elseif (isset($ctx['error_code']) && $ctx['error_code']) {
                $ctx = array('message' => $ctx['error_code'], 'code' => 0);
            } else {
                $ctx = array('message' => 'ERROR_CODE_EMPTY', 'code' => 0);
            }
            return true;
        }
        return false;
    }

    /**
     * 读取初始化配置信息.
     *
     * @param array $config 配置.
     *
     * @return void
     */
    protected function init(array $config)
    {
        if (isset($config['gateway_url'])) {
            $this->gatewayUrl = $config['gateway_url'];
        }
        if (isset($config['gateway_urls']) && is_array($config['gateway_urls'])) {
            $this->gatewayUrls = $config['gateway_urls'];
        }
        $this->user = $config['user'];
        $this->secret = $config['secret'];
        if (isset($config['time_out'])) {
            $this->timeOut = $config['time_out'];
        }
        if (isset($config['hostname']) && !empty($config['hostname'])) {
            $this->hostname = $config['hostname'];
        }
    }

    /**
    * 获取网关地址，兼容新老版本.
    *
    * @return string url
    */
    protected function getRequestUrl()
    {
        if (!empty($this->gatewayUrls) && is_array($this->gatewayUrls)) {
            $key = array_rand($this->gatewayUrls);
            return $this->gatewayUrls[$key];
        } else {
            return $this->gatewayUrl;
        }
    }

    /**
     * 计算 RPC 请求时间.
     *
     * @return float
     */
    private function executionTime()
    {
        return microtime(true) - $this->executionTimeStart;
    }

}
