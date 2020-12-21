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

V0.5.6 版本:
-------------

添加udp支持，允许将日志数据通过udp写入到特定host上的特定端口。


使用示例, 单例模式：

	$config1 = array( 
	     'on' => true,
	     'app' => 'mq',
	     'logdir' => './data/log/mq',
	     'mode' => 1, // 1 写入到文件; 2 写入到UDP; 3 同时写入到文件和udp
	     'server' => "127.0.0.1:9001" // udp server地址以及端口
	);

	$logger1 = MNLogger::instance($config1);

V0.5.7-beta 版本(基于V0.5.6):
-------------

全链路日志支持, 配置请参考docs/Config/MNLogger.php.

```
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
```
V0.5.8-beta 版本（基于V0.5.7-beta）:
-------------

trace cr+ss不在response_type为EXCEPTION时不在自动记录exception日志。
调整EXLogger::log接口的内部逻辑适配全链路日志。

V0.5.9-beta 版本（基于V0.5.8-beta）:
-------------

修复mnlogger生成的span id/trace id在高并发时可能重复的问题.
资源访问层elapsed增加单位描述ms.
限制res资源访问层记录value数据的大小(限制为120个字符).
发生异常的时候, 无论是否有抽样都要记录下链路日志(从异常发生时到本次请求的剩余链路,能记录的都要记录).
首节点检测到前端没有传递抽样标识的时候(注意是 没有 而不是 不抽样),根据的配置(sample_per_request)决定是否抽中,后续的节点跟随首节点的配置.
```
<?php

namespace Config;

class MNLogger
{
    // 全链路日志.
    public $trace2 = array(
        'on' => true,
        'app' => 'example',
        'logdir' => '/home/logs/monitor/',
        // 仅首节点在抽样标识缺失的情况下使用该配置.
        // 抽样基数: 表示每次请求被抽中的概率为 1/sample_per_request, 默认为100.
        'sample_per_request' => 100,
    );
}
```
