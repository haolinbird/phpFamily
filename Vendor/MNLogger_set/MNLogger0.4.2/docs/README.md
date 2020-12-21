监控系统日志打印 PHP 类库
========================

请使用版本 tag: v0.1

V0.2 版本:
-------------

添加异常日志集中打印、数据通道功能。

注意: 

1. 初始化 ExLogger 后会自动收集 Fault Error 异常和其他未被捕获异常，try catch 异常需要显式打印

使用示例, 单例模式：

	$config1 = array(
	     'on' => true,
	     'app' => 'mq',
	     'logdir' => './data/log/mq'
	);
	$config2 = array(
	     'on' => true,
	     'app' => 'rpc',
	     'logdir' => './data/log/rpc'
	);

	$logger1 = MNLogger::instance($config1);
	$logger2 = MNLogger::instance($config2);
	// 参数为用逗号分隔的 keys 和 vals
	$logger1->log('mobile,send', '1');
	$logger2->log('mobile,send', '2');
	$logger1->log('mobile,send', '3');
	$logger2->log('mobile,send', '4');

使用示例, 创建对象：

	$config = array(
	    'on' => true,
	    'app' => 'mq',
	    'logdir' => './data/log/monitor'
	);
	$logger = new MNLogger($config);
	$logger->log('mobile,send', '1');

	$config = array(
	    'on' => true,
	    'app' => 'mq',
	    'logdir' => './data/log/monitor1'
	);
	$logger = new MNLogger($config);
	$logger->log('mobile,send', '1');

其他使用示例：

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
