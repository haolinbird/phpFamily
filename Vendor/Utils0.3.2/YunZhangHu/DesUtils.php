<?php
/**
 * 云账户提供的des加解密工具.
 *
 * @author qiangd <qiangd@jumei.com>
 */

namespace Utils\YunZhangHu;

use Utils\Singleton;

/**
 * Create at 2019年12月5日 by qiangd <qiangd@jumei.com>.
 */
class DesUtils extends Singleton
{
    /**
     * Des key.
     *
     * @var string
     */
    private $des3key;

    /**
     * 密钥向量.
     *
     * @var string
     */
    private $iv;

    /**
     * 混淆向量.
     *
     * @var string
     */
    private $mode = MCRYPT_MODE_CBC;

    /**
     * 构造，传递⼆个已经进⾏base64_encode的KEY与IV.
     */
    function __construct()
    {
        // 赋值初始化deskey.
        $this->des3key = \Config\YunZhangHu::DES3_KEY;
        // 赋值初始化向量.
        $this->iv = substr($this->des3key, 0, 8);
    }

    /**
     * Get instance of the derived class.
     *
     * @return \Utils\YunZhangHu\DesUtils
     */
    public static function instance()
    {
        return parent::instance();
    }

    /**
     * 加密数据.
     *
     * @param array $value 要加密的数据.
     *
     * @return string
     */
    public function encrypt($value)
    {
        $iv = $this->iv;
        // 大于php7的逻辑.
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $ret = openssl_encrypt($value, 'DES-EDE3-CBC', $this->des3key, 0, $iv);
            if (false === $ret) {
                return openssl_error_string();
            }
            return $ret;
        }
        // php5的逻辑.
        $td = mcrypt_module_open(MCRYPT_3DES, '', $this->mode, '');
        $value = $this->paddingPKCS7($value);
        @mcrypt_generic_init($td, $this->des3key, $iv);
        $dec = mcrypt_generic($td, $value);
        $ret = base64_encode($dec);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    /**
     * 解密数据.
     *
     * @param string $value 待解密的数据.
     *
     * @return string
     */
    public function decrypt($value)
    {

        $iv = $this->iv;
        // 大于php7的逻辑.
        if (version_compare(PHP_VERSION, '7.0', '>=')) {
            $ret = openssl_decrypt($value, 'DES-EDE3-CBC', $this->des3key, 0, $iv);
            if (false === $ret) {
                return openssl_error_string();
            }
            return $ret;
        }
        // php5的逻辑.
        $td = mcrypt_module_open(MCRYPT_3DES, '', $this->mode, '');
        @mcrypt_generic_init($td, $this->des3key, $iv);
        $ret = trim(mdecrypt_generic($td, base64_decode($value)));
        $ret = $this->unPaddingPKCS7($ret);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }

    /**
     * 处理AES填充模式.
     *
     * @param array $data 要处理的数据.
     *
     * @return string
     */
    private function paddingPKCS7($data)
    {
        $block_size = mcrypt_get_block_size('tripledes', $this->mode);
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char), $padding_char);
        return $data;
    }

    /**
     * 反解填充模式.
     *
     * @param string $text 要反解的数据.
     *
     * @return boolean|string
     */
    private function unPaddingPKCS7($text)
    {
        $pad = ord($text {strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
    }

}
