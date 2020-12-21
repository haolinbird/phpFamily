<?php
namespace Config;

class XinYan
{

    /**
     * API的host.
     */
    const API_HOST = 'https://test.xinyan.com';
    /**
     * 新颜提供给商户的唯一编号.
     */
    const MEMBER_ID = '8000013189';
    /**
     * 新颜提供给商户的唯一终端编号.
     */
    const TERMINAL_ID = '8000013189';
    /**
     * 商户私钥密码.
     */
    const PASSWORD = '217526';
    /**
     * 商户私钥文件名.
     */
    const PFX_FILE = '8000013189_pri.pfx';
    /**
     * 公钥文件名.
     */
    const CER_FILE = 'bfkey_8000013189.cer';
    /**
     * 证书路径.
     */
    const RSA_PATH = __DIR__ . '/../Sample/';

}