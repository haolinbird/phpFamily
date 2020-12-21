<?php
namespace Utils\Url;

/**
 * Url签名.
 */
class Sign
{
    /**
     * 根据appid和参数生成签名.
     *
     * @param string $appid  应用的id.
     * @param array  $params url的参数.
     *
     * @return string 签名.
     */
    public static function genSign($appid, array $params)
    {
        unset($params['sig']);
        $cfg = \Config\UrlSign::$apps[$appid];
        $secretKey = $cfg['secret_key'];
        ksort($params);
        $paramsArr = array();
        foreach ($params as $key => $val) {
            $paramsArr[] = $key . '=' . rawurlencode($val);
        }
        $paramsStr = $secretKey . '&' . implode('&', $paramsArr);
        return md5($paramsStr);
    }

    /**
     * 验证签名是否通过.
     *
     * @param string $appid  应用id.
     * @param array  $params 请求的参数.
     *
     * @return boolean 验签成功返回true.
     *
     * @throws \Exception 失败返回异常.
     */
    public static function validate($appid, $params)
    {
        $cfg = \Config\UrlSign::$apps[$appid];
        $result = false;
        if ($cfg['enable']) {
            // 开启了验签.
            $sign = isset($params['sig']) ? $params['sig'] : ''; // 参数中的签名.
            if (empty($sign)) {
                throw new \Exception('sig undefined', 1000);
            }
            $newSign = self::genSign($appid, $params);
            if ($newSign != $sign) {
                throw new \Exception('check sign failed', 1003);
            }
            $timerange = $cfg['time_range'];
            if ($timerange) {
                // 开启了请求有效期验证.
                if (empty($params['sig_ts']) || !ctype_digit((string)$params['sig_ts'])) {
                    throw new \Exception('sig_ts undefined', 1001);
                }
                if (abs(time() - $params['sig_ts']) > $timerange) {
                    throw new \Exception('request expire', 1002);
                }
            }
        } else {
            $result = true;
        }
        return $result;
    }

}
