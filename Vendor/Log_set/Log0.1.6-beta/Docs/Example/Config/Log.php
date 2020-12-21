<?php
namespace Config;

/**
 *
 * @author suchao
 *
 */
#!defined("FILE_LOG_ROOT") && define("FILE_LOG_ROOT", "/var/log/jm-event-center/");
#!defined("BENCH_LOG_ROOT") && define("BENCH_LOG_ROOT", "/var/log/x-jumei-bench/");

class Log{
    /**
     * root of all logs of type "file".
     * @var please make sure this file is writeable to php-fpm worker.
     */
    #const FILE_LOG_ROOT = '/var/log/jm-event-center/';
    public $FILE_LOG_ROOT = '/var/log/jm-event-center';

    /**
     * for database connections
     * @var array
     */
    public  $db = array('logger'=>'file',
    );

    public $testLogFile = array('logger' => 'file',
        // 指定path后，所有日志将写到此文件中.
        'path' => '/var/log/test_log.log',
        // 按时间进行切分的文件名后缀格式. 如以下格式将生产日志文件/var/log/test_log.2015-09-12.log
        'rotateFormat' => 'Y-m-d'
    );

    public  $admin = array(
        'logger' => 'jsonfile',
        'fields' => array('user', 'controller', 'action', 'params'),
    );
}
