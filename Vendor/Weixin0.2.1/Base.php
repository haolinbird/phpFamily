<?php
/**
 * @author: dengjing<jingd3@jumei.com>.
 *
 */
namespace Weixin;

abstract class Base {

    const ACCESS_TOKEN_PREFIX = 'weixin_access_token_cache_';
    /**
     * Singleton Instances.
     *
     * @var array
     */
    public static $instances = [];
    /**
     * 相关的配置信息
     *
     * @var array
     */
    protected $config = [];
    /**
     * 应用的名字.
     *
     * @var string
     */
    protected $endpoint;
    /**
     * 配置的key名称,如h5,applet.
     *
     * @var string
     */
    protected $platform;
    /**
     * redis的配置名.
     *
     * @var string
     */
    protected static $redisEndPoint;

    /**
     * not support new class.
     *
     * @param string $endpoint
     *
     * @throws \Exception
     */
    private function __construct($endpoint) {
        $cfg = (array) new \Config\Weixin;
        $namespace = __NAMESPACE__ ;
        $this->platform = strtolower(str_replace($namespace . '\\', '', get_called_class()));
        $this->endpoint = $endpoint;
        if (empty($cfg[$this->platform][$this->endpoint])) {
            throw new \Exception($this->platform . ' config not set');
        }
        if (empty($cfg['redis'])) {
            throw new \Exception('redis config not set');
        }
        static::$redisEndPoint = $cfg['redis'];
        $this->config = $cfg[$this->platform][$this->endpoint];
        if (!isset($this->config['app_id'])) {
            $this->config['app_id'] = $this->config['appid'];
        }
        if (!isset($this->config['app_secret'])) {
            $this->config['app_secret'] = $this->config['appsecrect'];
        }
    }


    /**
     * Get instance of the derived class.
     *
     * @param string $endpoint 配置的endpoint.
     *
     * @return \Weixin\Base
     */
    public static function instance($endpoint)
    {
        $className = get_called_class();
        if (!isset(self::$instances[$className][$endpoint])) {
            static::$instances[$className][$endpoint] = new $className($endpoint);
        }
        return static::$instances[$className][$endpoint];
    }

    /**
     * 获取配置.
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * http get请求.
     *
     * @param string $url 请求的url.
     * @param array  $get get参数.
     *
     * @return string
     */
    protected function get($url, $get = [])
    {
        return $this->request($url, $get);
    }

    /**
     * http post 请求.
     *
     * @param string  $url           请求的url.
     * @param string  $post          post参数.
     * @param boolean $ignoreJmProxy 是否忽略jmproxy, 默认不忽略.
     *
     * @return string
     */
    protected function post($url, $post, $ignoreJmProxy = false)
    {
        return $this->request($url, [], $post, $ignoreJmProxy);
    }

    /**
     * 该function用于JmProxy请求.
     *
     * @param string $url  地址.
     * @param array  $get  get参数.
     * @param array  $post post参数
     *
     * @return array
     * @throws \Exception 错误异常.
     */
    public function JmProxy($url, $get = array(), $post = array())
    {
        // 是否启用本地代理.
        if (!empty($get)) {
            $method = "GET";
            $getString = http_build_query($get);
            $url .= '?' . $getString;
            $params = "";
        } else {
            $method = "POST";
            $params = $post;
        }

        try {
            $res = $this->jmproxyRequest($method, $url, array(), $params);
            return $res;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }


    }

    /**
     * Request.
     *
     * @param string       $method 请求方式.
     * @param string       $url    请求地址.
     * @param array        $header 请求头.
     * @param string/array $params 请求参数.
     *
     * @return array
     * @throws \Exception 代理错误.
     */
    public function jmproxyRequest($method, $url, array $header, $params)
    {
        if (empty($params)) {
            $params = "";
        } else {
            $params = is_array($params) ? http_build_query($params) : $params;
        }
        // 解析原生curl请求头为key=>value格式.
        if (!empty($header) && key($header) === 0) {
            $tmpHeader = $header;
            $header = array();
            foreach ($tmpHeader as $item) {
                $flag = strpos($item, ':');
                $header[substr($item, 0, $flag)] = substr($item, $flag + 1);
            }
        }
        // 请求头不能为空.
        if (!isset($header['User-Agent'])) {
            $header['User-Agent'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.186 Safari/537.36';
        }
        $data = array(
            'method' => $method,
            'url' => $url,
            'header' => $header,
            'params' => $params
        );


        $res = \PHPClient\Text::inst('JmProxy')->setClass('jmproxy')->ProxyHttp($data);
        // 代理处理状态.
        if ($res['statu'] != 200) {
            throw new \Exception(json_encode($res));
        } else {
            return json_decode($res['data'], true);
        }
    }

    /**
     * 执行http/https请求.
     *
     * @param string  $url           请求的url.
     * @param array   $get           get参数.
     * @param array   $post          post参数.
     * @param boolean $ignoreJmProxy 是否忽略jmproxy.
     *
     * @return string
     * @throws \Exception 请求异常.
     */
    protected function request($url, $get = [], $post = [], $ignoreJmProxy = false)
    {
        if (class_exists("\Config\JmProxy") && \Config\JmProxy::$enableJmProxy && $ignoreJmProxy === false) {
            try {
                $response = $this->JmProxy($url,$get,$post);
                if ($response['status_code'] != 200) {
                    $result = false;
                } else {
                    $result = $response['body'];
                }
            } catch (\Exception $exception) {
                $info = $exception->getMessage();
            }
        } else {
            if (!empty($get)) {
                $getString = http_build_query($get);
                $url .= '?' . $getString;
            }

            $ch = curl_init($url);
            if (!empty($post)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            }
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
            $timeout = !empty($this->config['timeout']) ? $this->config['timeout'] : 5;
            $connectionTimeout = !empty($this->config['connection_timeout']) ? $this->config['connection_timeout'] : 1000;
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connectionTimeout);

            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            if ($info['http_code'] != 200 || curl_error($ch) != '') {
                $result = false;
            } else {
                $result = $response;
            }
            curl_close($ch);
        }
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $functions = ['get', 'getjson', 'post', 'postjson'];
        $action = '';
        for ($i = 1; $i <= 3; $i++) {
            if (!in_array($backtrace[$i]['function'], $functions)) {
                $action = $backtrace[$i]['function'];
                break;
            }
        }
        $logName = 'weixin_req_' . $action;
        $logCfg = \Log\Handler::config();
        if (empty($logCfg)) {
            $logCfg = (array) new \Config\Log;
            \Log\Handler::config($logCfg);
        }
        if (!isset($logCfg[$logName])) {
            $logCfg[$logName] = ['logger' => 'jsonfile', 'rotateFormat' => 'Y-m-d'];
            \Log\Handler::config($logCfg);
        }
        \Log\Handler::instance($logName)->log(['date' => date('Y-m-d H:i:s'), 'platform' => $this->platform, 'app' => $this->endpoint, 'action' => $action, 'url' => $url, 'response' => $result, 'get' => $get, 'post' => $post]);
        if ($result === false) {
            throw new \Exception(var_export($info, true));
        }
        return $result;
    }

    /**
     * get请求获取json数据.
     *
     * @param string $url 请求地址.
     * @param array  $get 参数.
     *
     * @return array
     */
    protected function getjson($url, $get = [])
    {
        return json_decode($this->get($url, $get), true);
    }

    /**
     * post请求获取json数据.
     *
     * @param string  $url           请求地址.
     * @param array   $post          参数.
     * @param boolean $ignoreJmProxy 是否忽略jmproxy,默认不忽略.
     *
     * @return array
     */
    protected function postjson($url, $post = [], $ignoreJmProxy = false)
    {
        return json_decode($this->post($url, $post, $ignoreJmProxy), true);
    }

    /**
     * 获取redis实例.
     *
     * @return \Redis\RedisCache
     */
    protected function redis()
    {
        return \Redis\RedisMultiCache::getInstance(static::$redisEndPoint);
    }

    /**
     * 优先从缓存中获取公用token, 缓存中没有会去微信重新获取.
     *
     * @return string 成功返回access_token,失败返回空.
     */
    public function getAccesstoken()
    {
        $redisKey = self::ACCESS_TOKEN_PREFIX . $this->config['app_id'];
        $token = $this->redis()->get($redisKey);
        if (!$token) {
            $token = $this->refreshAccessToken();
        }
        return $token;
    }

    /**
     * 调用微信接口重新获取公用token.
     *
     * @return string 成功返回access_token,失败返回空.
     * @link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/access-token/getAccessToken.html
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183
     */
    public function refreshAccessToken()
    {
        $token = '';
        $appId = $this->config['app_id'];
        $appSecret = $this->config['app_secret'];
        $redisKey = self::ACCESS_TOKEN_PREFIX . $this->config['app_id'];
        $url = "https://api.weixin.qq.com/cgi-bin/token";
        $retry = 3;
        while ($retry--) {
            $result = $this->getjson($url, ['grant_type' => 'client_credential', 'appid' => $appId, 'secret' => $appSecret]);
            if (!empty($result['access_token'])) {
                $token = $result['access_token'];
                $expire = intval($result['expires_in']) - 600;
                $this->redis()->setex($redisKey, $expire, $token);
                break;
            }
        }
        return $token;
    }

    /**
     * 发送公众号/小程序的客服消息.
     *
     * @param string $touser  用户的openid.
     * @param string $msgtype 消息类型,text/link/miniprogrampage
     * @param array  $content 消息内容, 根据$msgtype来决定.
     *
     * @return array
     *
     * @link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/customer-message/sendCustomerMessage.html
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140547
     */
    public function sendCustomMessage($touser, $msgtype, $content)
    {
        $data = [
            'touser' => $touser,
            'msgtype' => $msgtype,
            $msgtype => $content,
        ];
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $token = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$token}";
        $result = $this->postjson($url, $json);
        return $result;
    }

    /**
     * 验证消息的确来自微信服务器.
     *
     * @param string $signature 签名.
     * @param string $timestamp 请求时间戳.
     * @param string $nonce     随机数.
     *
     * @return boolean 返回true表示校验通过,false表示不通过.
     */
    public function checkSignature($signature, $timestamp, $nonce)
    {
        $token = $this->config['token'];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if ($tmpStr == $signature ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 生成随机字符串.
     *
     * @param integer $length 长度.
     *
     * @return string
     */
    public function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 生成永久素材(只支持公众号).
     *
     * @param string $file
     * @param string $type
     * @return array
     */
    function addAaterial($file, $type = 'image') {
        $fileInfo = array('media' => class_exists('CURLFile', false) ? new \CURLFile($file) : '@' . $file);
        $accessToken = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$accessToken}&type={$type}";
        return $this->postjson($url, $fileInfo, true);
    }

    /**
     * 生成临时素材.
     *
     * @param string $file
     * @param string $type
     * @return array
     */
    function uploadMedia($file, $type = 'image') {
        $fileInfo = array('media' => class_exists('CURLFile', false) ? new \CURLFile($file) : '@' . $file);
        $accessToken = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$accessToken}&type={$type}";
        return $this->postjson($url, $fileInfo, true);
    }

}
