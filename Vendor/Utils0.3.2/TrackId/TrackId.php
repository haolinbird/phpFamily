<?php
/**
 * 用户的身份标识ID.
 *
 * @author dengjing<jingd3@jumei.com>
 */
namespace Utils\TrackId;

class TrackId
{

    const COOKIE_TRACK_ID = '__trackid';

    /**
     * 获取盐值.
     *
     * @param type $trackId
     * @return type
     */
    public static function salt($trackId)
    {
        return substr(md5($trackId . substr($trackId, substr($trackId, -1, 1))), 2, 5);
    }

    /**
     * 获取cookie中的用户身份标识.
     *
     * @return integer
     */
    public static function get()
    {
        if (!isset($_COOKIE[self::COOKIE_TRACK_ID]) || strlen($_COOKIE[self::COOKIE_TRACK_ID]) != 22) {
            return false;
        }
        list($trackId, $salt) = explode('.', $_COOKIE[self::COOKIE_TRACK_ID]);
        if (!is_numeric($trackId)) {
            return false;
        }
        return self::salt($trackId) == $salt ? $trackId : false;
    }

    /**
     * 生成trackid.
     *
     * @return string
     */
    public static function genId()
    {
        usleep(1);
        $microTime = (int)(microtime(true) * 1000);
        $rand = rand(0, 999);
        $trackId = $microTime * 1000 + $rand;
        return $trackId;
    }

    /**
     * 获取cookie中的用户身份标识.
     *
     * @return string 用户的trakcId.
     */
    public static function set()
    {
        $trackId = self::genId();
        $string = $trackId . '.' . self::salt($trackId);
        // 5 years.
        \Utils\Cookie\Cookie::setCookiesWholeDomain([self::COOKIE_TRACK_ID => $string], time() + 157680000);
        return $trackId;
    }

    /**
     * 初始创建一个trackid(若cookie中已经存在有效的trackid时不再重新创建).
     *
     * @return string 用户的trackId.
     */
    public static function create()
    {
        $result = self::get();
        if (!$result) {
            $result = self::set();
        }
        return $result;
    }

    /**
     * 恢复cookie中的唯一标示(一般是登录之后从用户表中记录的trackid进行恢复).
     *
     * @param integer $trackId 用户的唯一标识.
     *
     * @return string
     */
    public static function restore($trackId)
    {
        $string = $trackId . '.'  . self::salt($trackId);
        \Utils\Cookie\Cookie::setCookiesWholeDomain([self::COOKIE_TRACK_ID => $string], time() + 157680000);
        return $trackId;
    }

}