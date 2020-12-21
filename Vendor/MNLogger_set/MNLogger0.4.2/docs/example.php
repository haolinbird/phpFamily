<?php

require_once __DIR__ . '/vendor/autoload.php';
use MNLogger\MNLogger;
use MNLogger\EXLogger;
use MNLogger\DATALogger;

$config1 = array(
     'on' => true,
     'app' => 'mq',
     'logdir' => './data/stats'
);
$config2 = array(
     'on' => true,
     'app' => 'rpc',
     'logdir' => './data/stats'
);

$config3 = array(
     'on' => true,
     'app' => 'rpc',
     'logdir' => './data/exception'
);

$config4 = array(
     'on' => true,
     'app' => 'rpc',
     'logdir' => './data/data'
);
echo 'hello';
// 统计埋点示例
$logger1 = MNLogger::instance($config1);
$logger2 = MNLogger::instance($config2);

$logger1->log('mobile,send', '1');
$logger2->log('mobile,send', '2');
$logger1->log('mobile,send', '3');
$logger2->log('mobile,send', '4');

// 数据通道示例
$data_channel = DATALogger::instance($config4);

$data_channel->log("c1", "Whatever but should be string.");

// 异常示例, 只有最后一个起作用，即每个应用只应该使用一个 EXLogger 实例
$ex_logger = EXLogger::instance($config3);

// try catch 示例
try {
	throw new Exception("Exception in try catch.");
} catch(Exception $e) {
	$ex_logger->log($e);
}

// Error 示例
$a = 1/0;

// 应用未捕获异常示例
throw new Exception("Some exception.");

