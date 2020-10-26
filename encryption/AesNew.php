<?php

function Encrypt(?string $Content, string $Key): string {
    return openssl_encrypt($Content, 'aes-256-gcm', $Key, OPENSSL_RAW_DATA, $IV = random_bytes(16), $Tag, '', 16) . $IV . $Tag;
}

function Decrypt(?string $Ciphertext, string $Key): ?string {
    if (strlen($Ciphertext) < 32)
        return null;

    $Content = substr($Ciphertext, 0, -32);
    $IV = substr($Ciphertext, -32, -16);
    $Tag = substr($Ciphertext, -16);

    try {
        return openssl_decrypt($Content, 'aes-256-gcm', $Key, OPENSSL_RAW_DATA, $IV, $Tag);
    } catch (Exception $e) {
        return null;
    }
}

$data = 'hello php aes_256_gcm!';

$key = '0d9f4c4aac10275a2d7255296783324c';

$encrypt = Encrypt($data, $key);

echo "加密:".$encrypt."\r\n";

$decrypt = Decrypt($encrypt, $key);

echo $decrypt."\r\n";
