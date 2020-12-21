<?php
namespace Config;

class Log{
    // 文件日志的根目录.请确认php进程对此目录可写
    public $FILE_LOG_ROOT = '/var/log/cart_test/';

    // 数据库日志配置
    public $db = array('logger' => 'file',
    );

    // 库存日志配置
    public $inventoryLog = array(
            'logger' => 'jsonfile',
            'fields' => array('app', 'sku_no', 'at_time', 'service', 'params', 'return', 'changed', 'operationid')
    );

    // 支付日志
    public $payment = array(
        'logger' => 'jsonfile',
        'fields' => array('app', 'sku_no', 'at_time', 'service', 'params', 'return', 'changed', 'operationid'),
        'path' => '/tmp/payment.log'
    );
}