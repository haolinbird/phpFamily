<?php
require "Aes.php";

/**
 * 生成安全的随机字符串.
 *
 * @param integer $length 随机字符串长度
 * @return string
 */
function generateRandomString(int $length = 10)
{
    if (!is_int($length) || $length <= 0) {
        return '';
    }

    // 优选方案 use openssl
    if (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes($length, $cstrong);
        $hex = bin2hex($bytes);

        return $hex;
    }

    // 次选方案 随机生成
    $charPool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    return substr(str_shuffle(str_repeat($charPool, 5)), 0, $length);
}

// 明文
$data = "hello php aes_256_gcm!";

echo "明文:".$data."\r\n";

// 密钥
$key = $nonceStr = generateRandomString(16);;

echo "密钥:".$key."\r\n";

// 附加数据
$associatedData = 'fu jia shu ju';

echo "附加数据:".$associatedData."\r\n";

// 随机字符串
$nonceStr = generateRandomString(16);

echo "随机字符串:".$nonceStr."\r\n";

$aes = new Aes($key);
// 加密
$encrypt = $aes->encryptToString($data, $associatedData, $nonceStr);
$encrypt = base64_encode($encrypt);

echo "密文:".$encrypt."\r\n";

// 解密
$decrypt = $aes->decryptToString($associatedData, $nonceStr, $encrypt);

echo "解密:".$decrypt."\r\n";

