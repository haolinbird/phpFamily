<?php
namespace Notification;

class Client
{

    public $error;
    protected $config;
    protected $debug;
    protected $_curlResource;
    protected $_curlResCurrentTimes = 0;

    const CURLResMaxUseTimes = 10000;

    public function __construct($config = array())
    {
        /*
         * array(
         *     'host' => 'http://127.0.0.1:8080',
         *     'key' => '1',
         *     'secret' => 's123',
         * );
         */
        if (!empty($config)) {
            $this->config = $config;
        } elseif (class_exists('\Config\Notification')) {
            $this->config = (array)new \Config\Notification();
        } else {
            throw new \Exception('config file not found');
        }

        if (!empty($this->config['debug'])) {
            $this->debug = true;
        } else {
            $this->debug = false;
        }

    }

    /**
     * 推送消息至用户
     * 当指定$appid后，$group参数自动失效
     *
     * @param int $uid 用户所在系统的uid
     * @param Payload $payload
     * @param null $appid 只推送至指定app
     * @param string $group 推送的app组，默认为 聚美主APP的iPhone、Android、iPad版本
     * @return bool
     * @throws PayloadException
     */
    public function push($uid, Payload $payload, $appid = null, $product = 'jumei')
    {
        //如果payload大于apple规定的256byte，则抛出异常
        $payload->checkLong();

        $payload->set('uid', $uid);
        if (!empty($appid)) {
            $payload->set('appid', $appid);
        } else {
            $payload->set('product', $product);
        }

        $payload->sign($this->config['key'], $this->config['secret']);
        $path = '/send/uid';
        if (!empty($this->config['jpush'])) {
            $path = '/jpush/uid';
        }
        $res = $this->_call($path, $payload);

        if ($res) {
            if (!empty($res['result'])) {
                return true;
            } else {
                if (isset($res['message'])) {
                    if ($this->debug)
                        echo "gateway return error :{$res['message']}\n";

                    $this->error = $res['message'];
                }
            }
        }

        return false;
    }

    /**
     * 根据appid和token推送至指定设备
     *
     * @param string $appid
     * @param string $token 设备
     * @param Payload $payload
     */
    public function pushWithToken($appid, $token, Payload $payload)
    {

    }

    protected function _call($uri, Payload $payload, $retry = 1)
    {
        $ch = $this->_getCurlResource();

        // 设置curl允许执行的最长秒数
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        // 获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // 强制使用ipv4解析
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        if ($this->debug)
            echo $this->config['host'] . $uri . '?' . $payload->__toString() . "\n";

        // 设置请求url
        curl_setopt($ch, CURLOPT_URL, $this->config['host'] . $uri . '?' . $payload->__toString());

        $ret = curl_exec($ch);
        $error = curl_error($ch);
        if (!empty($error) || $ret === false) {
            if (empty($error)) {
                $this->error = "curl errno is " . curl_errno($ch);
                $this->_resetCurlResource();
            } else {
                $this->error = $error;
            }
            return false;
        }

        $ret = json_decode($ret, true);

        if (json_last_error() == JSON_ERROR_NONE) {
            return $ret;
        } else {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code == 502) {
                $this->error = "502 Bad Gateway";
            } elseif ($code == 504) {
                $this->error = "504 Gateway Timeout";
            }
            //有可能是服务在重启，重试
            if ($retry) {
                $retry--;
                usleep(10000);
                return $this->_call($uri, $payload, $retry);
            }
            return false;
        }

    }

    protected function _getCurlResource()
    {

        $this->_curlResCurrentTimes++;
        if ($this->_curlResCurrentTimes > self::CURLResMaxUseTimes) {
            curl_close($this->_curlResource);
            $this->_curlResource = null;
            $this->_curlResCurrentTimes = 1;
        }

        if (empty($ch)) {
            $this->_curlResource = curl_init();
        }

        return $this->_curlResource;
    }

    protected function _resetCurlResource()
    {
        curl_close($this->_curlResource);
        $this->_curlResource = null;
        $this->_curlResCurrentTimes = 0;
    }

}