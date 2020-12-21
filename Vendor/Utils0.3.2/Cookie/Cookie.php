<?php
namespace Utils\Cookie;

/**
 * Cookie.
 */
class Cookie
{

    /**
     * 保存Cookie.
     *
     * @param array   $valuePairArray Cookie要存入的k-v对.
     * @param integer $expiration     Cookie过期时间.
     * @param string  $domain         Cookie的作用域名.
     * @param boolean $secure         是否只允许https传输.
     * @param boolean $httponly       是否只允许通过http访问.
     *
     * @return void
     */
    public static function setCookies(array $valuePairArray, $expiration, $domain = null, $secure = false, $httponly = false)
    {
        foreach ($valuePairArray as $key => $value) {
            setcookie($key, $value, $expiration, '/', $domain, $secure, $httponly);
        }
    }

    /**
     * 将cookie设置为全站范围.
     *
     * @param array   $valuePairArray Coockie要存入的k-v对.
     * @param integer $expiration     Coockie过期时间.
     * @param string  $domain         作用域(可选，不填写代表全站).
     * @param boolean $secure         是否只允许https传输.
     * @param boolean $httponly       是否只允许通过http访问.
     *
     * @return void.
     */
    public static function setCookiesWholeDomain(array $valuePairArray, $expiration, $domain = null, $secure = false, $httponly = false)
    {
        if (empty($domain)) {
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            if (!empty($host) && !self::isIP($host) && !self::isLocal($host)) {
                $segments = explode('.', $host);
                if (!empty($segments)) {
                    if (count($segments) > 2) {
                        array_shift($segments);
                    }
                    $domain = '.' . implode('.', $segments);
                }
            }
        }
        return self::setCookies($valuePairArray, $expiration, $domain, $secure, $httponly);
    }

    /**
     * 获取Cookie.
     *
     * @param string $key Key.
     *
     * @return mixed
     */
    public static function getCookie($key)
    {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
    }

    /**
     * 移除cookie值.
     *
     * @param array $names 要删除的Cookie名称.
     *
     * @return void
     */
    public static function removeCookies($names)
    {
        foreach ($names as $name) {
            if (isset($_COOKIE[$name])) {
                unset($_COOKIE[$name]);
            };
        }
    }

    /**
     * 是否是本机名称.
     *
     * @param string $value 主机名字.
     *
     * @return boolean
     */
    public static function isLocal($value)
    {
        return (strpos($value, '.') === false);
    }

    /**
     * 是否为IP地址.
     *
     * @param string $value 主机名字.
     *
     * @return boolean
     */
    public static function isIP($value)
    {
        $value = preg_replace('/^(http|https|file|ftp):\/\//', '', $value);
        return (bool) filter_var($value, FILTER_VALIDATE_IP);
    }

}
