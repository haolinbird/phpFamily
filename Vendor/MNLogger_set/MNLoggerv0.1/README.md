监控系统日志打印 PHP 类库
========================

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