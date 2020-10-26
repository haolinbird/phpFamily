<?php

/**
 * Class AES加密类
 */
class AES
{
    /*
      加密方式
      openssl_get_cipher_methods() 可以获取可用的加密算法
    */
    private $_method;

    /*
      密钥
      AES 加密的分组长度是 128 位, 即每个分组为 16 个字节 ( 每个字节 8 位 ).
      密钥的长度根据加密方式的不同可以是 128 位, 192 位, 256 位. 与 DES 加密一样.
      密钥长度超过指定长度时, 超出部分无效. 密钥长度不足时, 会自动以`\0`补充到指定长度
      加密方式  密钥长度 ( 位 )  分组长度 ( 位 )
      AES-128	   128	          128
      AES-192	   192	          128
      AES-256	   256            128
    */
    private $_key;

    /*
       options 是以下标记的按位或： OPENSSL_RAW_DATA 、 OPENSSL_ZERO_PADDING
       0 : 自动对明文进行 padding, 返回的数据经过 base64 编码.
       1 : OPENSSL_RAW_DATA, 自动对明文进行 padding, 但返回的结果未经过 base64 编码.
       2 : OPENSSL_ZERO_PADDING, 自动对明文进行 0 填充, 返回的结果经过 base64 编码.但是, openssl 不推荐 0 填充的方式, 即使选择此项也不会自动进行 padding, 仍需手动 padding
    */
    private $_options = 0;

    /*
       iv 非null的初始化向量
       不使用此项会抛出一个警告. 如果未进行手动填充, 则返回加密失败
       openssl_decrypt($data, $method, $key, $options = 0, $iv = '') : 解密数据.
       openssl_cipher_iv_length($method) : 获取 method 要求的初始化向量的长度.
       openssl_random_pseudo_bytes($length) : 生成指定长度的伪随机字符串.
       hash_mac($method, $data, $key, $raw_out) : 生成带有密钥的哈希值.
    */
    public $_iv = '';

    // 使用 AEAD 密码模式（GCM 或 CCM）时传引用的验证标签
    private $_tag = '';

    // 附加的验证数据，可以为空
    private $_aad = '';

    // 验证 tag 的长度。GCM 模式时，它的范围是 4 到 16
    private $_tagLength = 16;

    /**
     * AES constructor.
     * @param string $method
     * @param string $key
     * @param int $options 不会
     * @param string $iv
     * @param string|null $tag
     * @param string $add
     * @param int $tagLength
     * @throws Exception
     */
    public function __construct(string $method, string $key, int $options = 0, string $iv = '', string $tag = null, string $add = '', int $tagLength = 16)
    {
        // 检查是否支持加密方式
        if (!in_array($method, openssl_get_cipher_methods()))
        {
            throw new \Exception('不支持的加密方式:'.$method);

        }
        $this->_method = $method;

        // 获得该加密方式的iv长度
        $ivlen = openssl_cipher_iv_length($method);
        // 生成相应长度的伪随机字节串作为初始化向量
        $this->_iv = openssl_random_pseudo_bytes($ivlen);

        // 设置加密选项
        $this->_options = $options;

        // 设置附加数据
        $this->_aad = $add;

        // 设置加密密钥
        $this->_key = $key;

        // 设置 AEAD 密码模式（GCM 或 CCM）时传引用的验证标签
        $this->_tag = $tag;

        // 验证tag的长度
        $this->_tagLength = $tagLength;
    }

    /**
     * AES 加密
     * @param string $plaintext 明文数据
     * @return string
     */
    public function encrypt($plaintext)
    {
        $ciphertext = openssl_encrypt($plaintext, $this->_method, $this->_key, $this->_options, $this->_iv, $this->_tag, $this->_aad, $this->_tagLength);
        return $ciphertext;
    }

    /**
     * AES 解密
     * @param string $ciphertext base64编码后的密文数据
     * @return string
     */
    public function decrypt($ciphertext)
    {
        $original_plaintext = openssl_decrypt($ciphertext, $this->_method, $this->_key, $this->_options, $this->_iv, $this->_tag, $this->_aad);
        return $original_plaintext;
    }
}

$tmp = new AES("aes-256-gcm", "123456789WANGchao");
$plaintext = "message to be encrypted";
$ciphertext = $tmp->encrypt($plaintext);
echo $ciphertext . "\n";
$original_plaintext = $tmp->decrypt($ciphertext);
echo $original_plaintext . "\n";
