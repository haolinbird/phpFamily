<?php
/**
 * 监控日志组件配置文件
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2020-12-24 10:28:30
 */

namespace Config;

/**
 * Class MNLogger .
 */
class MNLogger
{
    public $exception = array (
        'logdir' => "#{xz-php-cron.MNLogger.Exception.Logdir}",
        'app' => 'xz-php-cron',
        'on' => true,
    );

    public $trace = array (
        'logdir' => "#{xz-php-cron.MNLogger.Trace.Logdir}",
        'app' => 'xz-php-cron',
        'on' => false,
    );
}
