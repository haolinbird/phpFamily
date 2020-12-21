<?php

namespace Config;

class MNLogger
{
    // 全链路依赖三个日志配置项: trace2,slow2,exception2;这些配置不存在的时候则使用该默认值(推荐使用).
    /*array(
        'on' => true,
        // 未定义JM_APP_NAME则使用"DefaultSetting".
        'app' => JM_APP_NAME,
        'logdir' => '/home/logs/monitor/',
        // 1 日志写入到文件; 2 日志写入到UDP agent; 3 日志同时写入到文件和udp
        'mode' => 1,
        // udp agent地址以及端口(mode为2/3时必须配置), 例如:127.0.0.1:9001.
        'server' => "",
    )*/
    // 全链路错误日志.
    public $exception2 = array(
        'on' => true,
        'app' => 'example',
        'logdir' => '/home/logs/monitor/'
    );
    // 全链路日志.
    public $trace2 = array(
        'on' => true,
        'app' => 'example',
        'logdir' => '/home/logs/monitor/'
    );
    // 慢查询日志.
    public $slow2 = array(
        'on' => true,
        'app' => 'example',
        'logdir' => '/home/logs/monitor/'
    );
}
