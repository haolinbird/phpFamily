<?php
/**
 * Created by PhpStorm.
 * User: nassir
 * Date: 2017/12/11
 * Time: 15:25
 */

namespace Ryanc\RSA;


class AES
{
    private $_cipher;// 加密方式
    private $_key;// 密钥
    private $_options = 0;// options 是以下标记的按位或： OPENSSL_RAW_DATA 、 OPENSSL_ZERO_PADDING
    private $_iv = '';// 非null的初始化向量
    private $_tag = '';// 使用 AEAD 密码模式（GCM 或 CCM）时传引用的验证标签
    private $_aad = '';// 附加的验证数据
    private $_tagLength = 16;// 验证 tag 的长度。GCM 模式时，它的范围是 4 到 16

    public function __construct( string $cipher, string $key, int $options = 0, string $iv = '', string $tag = null, string $add = '', int $tagLength = 16)
    {
        $this->_cipher = $cipher;
        $this->_options = $options;
        $this->_tag = $tag;
        $this->_aad = $add;
        $this->_tagLength = $tagLength;
        $ivlen = openssl_cipher_iv_length($cipher);// 获得该加密方式的iv长度
        $this->_iv = openssl_random_pseudo_bytes($ivlen);// 生成相应长度的伪随机字节串作为初始化向量
        $this->_key = $key . 'nassir';
    }

    public function encrypt($plaintext)
    {
        $ciphertext = openssl_encrypt($plaintext, $this->_cipher, $this->_key, $this->_options, $this->_iv, $this->_tag);
        return $ciphertext;
    }

    public function decrypt($ciphertext)
    {
        $original_plaintext = openssl_decrypt($ciphertext, $this->_cipher, $this->_key, $this->_options, $this->_iv, $this->_tag);
        return $original_plaintext;
    }
}

$tmp = new AES("aes-256-gcm", "123456789WANGchao");
$plaintext = "message to be encrypted";
$ciphertext = $tmp->encrypt($plaintext);
echo $ciphertext . "\n";
$original_plaintext = $tmp->decrypt($ciphertext);
echo $original_plaintext . "\n";
