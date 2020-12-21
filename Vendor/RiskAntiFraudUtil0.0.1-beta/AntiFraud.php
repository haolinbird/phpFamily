<?php
/**
 * RiskAntifraudUtil
 *
 * @author chaol1 <chaol1@jumei.com>
 */
namespace RiskAntiFraudUtil;

/**
 * Class AntiFraud
 * @package RiskAntiFraudUtil
 *
 * @version 0.0.1-beta
 */
class AntiFraud
{
    /**
     * @var instance
     */
    private static $instance;

    /**
     * 获取实例.
     *
     * @return $this
     */
    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 获取验签Token.
     *
     * @param string  $appname    AppName.
     * @param string  $deviceId   设备ID.
     * @param string  $ip         IP like "127.0.0.1", 传客户端的外网IP
     * @param string  string      Version, Token的版本号 like 'v1'.
     * @param string  $configName Service配置名:\Config\PHPClient.
     * @return mixed
     */
    public function getToken($appname, $deviceId, $ip, $version = 'v1', $configName = "AntiFraud") {
        $response = \PHPClient\Text::inst($configName)->setClass("AntiFraud")->getTokenByAppName($appname, $deviceId, $ip, $version);
        return $response;
    }

    /**
     * 验签执行方法.
     *
     * @param string  $appname    AppName.
     * @param array   $params     验签参数.
     * @param string  $sign       签名串.
     * @param integer $ts         当前时间戳.
     * @param integer $tokenId    TokenId.
     * @param string  $configName Service配置名:\Config\PHPClient.
     * @return mixed
     */
    public function checkSign($appname, array $params, $sign, $ts, $tokenId, $configName = "AntiFraud") {
        $response = \PHPClient\Text::inst($configName)->setClass("AntiFraud")->checkSignByAppName($appname, $params, $sign, $ts, $tokenId);
        return $response;
    }

}
