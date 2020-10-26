<?php

/**
 * Class AES加密类
 */
class AES
{
    const KEY_LENGTH_BYTE = 32;
    const AUTH_TAG_LENGTH_BYTE = 16;

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
    private $_aesKey;

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
     * @param string  $method  加密方式
     * @param string  $aesKey  加密Key
     * @param integer $options 加密选项
     * @throws Exception
     */
    public function __construct(string $method, string $aesKey, int $options = \OPENSSL_RAW_DATA)
    {
        // 检查是否支持加密方式
        if (!in_array($method, openssl_get_cipher_methods()))
        {
            throw new \Exception('不支持的加密方式:'.$method);

        }
        $this->_method = $method;

        // 设置加密密钥
        $this->_aesKey = $aesKey;

        // 设置加密选项
        $this->_options = $options;
    }

    /**
     * Encrypt AEAD_AES_256_GCM plaintext
     *
     * @param string $plaintext      AES GCM plain text
     * @param string $nonceStr       AES GCM nonce
     * @param string $associatedData AES GCM additional authentication data
     * @return string
     */
    public function encrypt($plaintext, $nonceStr, $associatedData)
    {
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ciphertext = openssl_encrypt($plaintext, $this->_method, $this->_aesKey, $this->_options, $nonceStr, $this->_tag, $associatedData, $this->_tagLength) . $this->_tag;

            return \base64_encode($ciphertext);
        }

        throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }

    /**
     * Decrypt AEAD_AES_256_GCM ciphertext
     *
     * @param string $ciphertext     AES GCM cipher text
     * @param string $nonceStr       AES GCM nonce
     * @param string $associatedData AES GCM additional authentication data
     *
     * @return string|bool      Decrypted string on success or FALSE on failure
     */
    public function decrypt($ciphertext, $nonceStr, $associatedData)
    {
        $ciphertext = \base64_decode($ciphertext);
        if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE) {
            return false;
        }

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);

            return \openssl_decrypt($ctext, $this->_method, $this->_aesKey, $this->_options, $nonceStr, $authTag, $associatedData);
        }

        throw new \RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }
}


/*
// 获得该加密方式的iv长度
$ivlen = openssl_cipher_iv_length('aes-256-gcm');
// 生成相应长度的伪随机字节串作为初始化向量
$iv = openssl_random_pseudo_bytes($ivlen);
// 把二进制字符串转换为十六进制值
$nonceStr = bin2hex($iv);
echo "nonceStr:".$nonceStr."\n";

$nonceStr = '123';
// 附加数据
$associatedData = '';
echo "associatedData:".$associatedData."\n";
*/

$tmp = new AES("aes-256-gcm", "MQz5xOMDQaE85yMrG8K022veMQCRLVwY");
$response = '{"notify_id":2320601940,"notify_time":"2020-08-14 17:46:24","notify_type":"order_report_completed","resource":"{\"algorithm\":\"aes-256-gcm\",\"ciphertext\":\"LxGOxvGPHtjWDPqPR3gIgK4+RzOnrY6wmx1RxoaBVws2hCaoixr699gpRJnayQ4IEP+xlwnqMu66oafLOnACLN2u2gDT\",\"associated_data\":\"\",\"nonce\":\"d9517dd18b8e08fbe546a8b4\"}","test_flag":1}';
$response = json_decode($response, true);
$resource = json_decode($response['resource'], true);

$nonceStr = $resource['nonce'];
echo "nonceStr:".$nonceStr."\n";
$associatedData = $resource['associated_data'];
echo "associatedData:".$associatedData."\n";

//$ciphertext = $tmp->encrypt($plaintext, $nonceStr, $associatedData);
$ciphertext = $resource['ciphertext'];
echo "密文:".$ciphertext . "\n";
$original_plaintext = $tmp->decrypt($ciphertext, $nonceStr, $associatedData);
echo $original_plaintext . "\n";
