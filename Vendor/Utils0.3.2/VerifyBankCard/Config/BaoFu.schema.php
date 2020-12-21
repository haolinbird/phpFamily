<?php
namespace Config;

class BaoFu
{

    /**
     * API的host.
     *
     * @var string
     */
    const API_HOST = 'https://vgw.baofoo.com/biztransfer';

    /**
     * 宝付提供给商户的唯一编号.
     *
     * @var string
     */
    const MEMBER_ID = '100026286';

    /**
     * 宝付提供给商户的唯一终端编号.
     *
     * @var string
     */
    const TERMINAL_ID = '200001418';

    /**
     * 商户私钥密码.
     *
     * @var string
     */
    const PASSWORD = '100026286_715265';

    /**
     * 商户私钥文件名.
     *
     * @var string
     */
    const PFX_FILE = 'bfkey_100026286@@200001418.pfx';

    /**
     * 公钥文件名.
     *
     * @var string
     */
    const CER_FILE = 'bfkey_100026286@@200001418.cer';

    /**
     * 证书路径.
     *
     * @var string
     */
    const RSA_PATH = __DIR__ . '/../Sample/';

    /**
     * 行业类型.
     *
     * @var string
     */
    const INDUSTRY_TYPE = 'A1';

}