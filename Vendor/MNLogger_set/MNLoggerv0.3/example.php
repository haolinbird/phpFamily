<?php

require_once __DIR__ . '/vendor/autoload.php';
use MNLogger\MNLogger;
use MNLogger\EXLogger;
use MNLogger\DATALogger;

// logdir 此处在开发调试环境使用 /tmp/logs/monitor/, 方便避免权限问题
//        线上一般为 /home/logs/monitor, 需要运维设置读写权限。

$config1 = array(
     'on' => true,
     'app' => 'mq',
     'logdir' => '/tmp/logs/monitor/'
);
$config2 = array(
     'on' => true,
     'app' => 'payment-rpc',
     'logdir' => '/tmp/logs/monitor'
);

$config3 = array(
     'on' => true,
     'app' => 'refund-rpc',
     'logdir' => '/tmp/logs/monitor'
);

$config4 = array(
     'on' => true,
     'app' => 'withdraw',
     'logdir' => '/tmp/logs/monitor'
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

// 异常示例, 只有第一个起作用，即每个应用只应该使用一个 EXLogger 实例
$ex_logger = EXLogger::instance($config3);

// 另外一种方法：先注册/初始化配置
EXLogger::setUp(array('exception'=>$config3));
// 业务再在写日志时处调用log方法
EXLogger::instance('exception')->log(new \Exception('业务流程有异常'));

// 其它Logger也类似。
// 注册配置:
DATALogger::setUp(array('payment'=>$config2, 'refund'=>$config3, 'withdraw'=>$config4));
DATALogger::instance('payment')->log('payment_data', json_encode(array('id'=>321,'nickname'=>'tom','birth'=>null)));
DATALogger::instance('refund')->log('refund_data', 'refund approved');

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

