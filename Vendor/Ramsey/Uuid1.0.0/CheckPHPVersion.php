<?php
/**
 * CheckPHPVersion.php Handler.
 *
 * @author quans<quans@jumei.com>
 *
 * @since 2017/5/18
 */

namespace Ramsey\Uuid;

/**
 * Class CheckPHPVersion.
 */
class CheckPHPVersion
{
    /**
     * 判断是否支持random_bytes()函数.
     *
     * @return boolean
     */
    public static function isRandomBytesSuport()
    {
        $version = phpversion();
        $version = explode('.', $version);
        if ($version[0] >= 7) {
            return true;
        }

        return false;
    }

}
