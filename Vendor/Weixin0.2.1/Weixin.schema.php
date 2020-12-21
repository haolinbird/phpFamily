<?php
namespace Config;

/**
 * Class Weixin.
 */
class Weixin
{
    /**
     * 小程序配置.
     *
     * @var array
     */
    public $applet = [
        'shuabao' => [
            'app_id' => 'abc',
            'app_secret' => 'xyz',
            'token' => 'yourtokenhere',
            'timeout' => 5, // 请求微信API等待超时(单位:秒)
            'connection_timeout' => 1000, // 请求微信API等待超时(单位:毫秒)
        ]
    ];
    /**
     * 公众号配置.
     *
     * @var array
     */
    public $h5 = [
        'shuabao' => [
            'app_id' => 'foo',
            'app_secret' => 'bar',
            'token' => 'mytokenhere',
            'timeout' => 5, // 请求微信API等待超时(单位:秒)
            'connection_timeout' => 1000,// 请求微信API等待超时(单位:毫秒)
        ]
    ];
    /**
     * 使用的redis集群的endpoint.
     *
     * @var string
     */
    public $redis = 'default';
}