<?php
/**
 * 聚美支付系统相关配置文件.
 * 
 * @author XiW<xiw4@jumei.com>
 */
namespace Config;

class JMPaymentGateway
{
    public $GlobalAlipayMobile = array(
        'gateway_url' => 'http://pay.jumeicd.com/gateway?',
        'user' => 'GlobalAlipayMobile',
        'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
    );

}
