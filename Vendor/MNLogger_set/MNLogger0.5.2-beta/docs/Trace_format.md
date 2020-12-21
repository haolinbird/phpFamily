OWL Trace 日志格式
=====================

实现追踪的方式是在请求端、被请求端打印日志；OWL 进行日志收集汇总处理并展示。
通过 nginx 生成的 uuid 实现 Nginx 日志到追踪日志的关联、Nginx 日志到异常日志的关联。
每个 HTTP 请求到来时由 Nginx 传入 uuid，jumei-monitor-logger 获取 uuid 并生成当前 trace_id，然后赋值给 global $owl_context 数组的对应变量中。每个远程调用都会附加这个变量数组，并传递给远程服务。这样客户端、远程服务打印日志都会打印这个*上下文*变量，以分布式调用过程的日志关联。

依赖：

1. Nginx 版本升级、Nginx 日志格式修改

proxy_set_header x-owl-rid $request_id;

2. 尽可能统一的 HTTP 客户端类库
3. RPC 框架传递上下文
4. MySQL 类库埋入 Trace 日志 (MYSQL_CS [客户端发送]， MYSQL_CR [客户端接收])
5. RPC 客户端 (RPC_CS [客户端发送]， RPC_CR [客户端接收])、服务端埋 (RPC_SR [服务端接收]、RPC_SS [服务端发送]) 入 Trace 日志
6. 其他类库埋入 Trace 日志

日志格式：
-------------

日志位用 ^A 分割
内部 Array 用 ^B 分割
内部 KEY VAL 用 ^C 分割
单条日志结束换行用 ^D\n 分割

Trace 的基本格式：
---------------------

$line = "OWL\001TRACE\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001{$type}\001{$uuid}\001{$parent_id}\001{$trace_id}\001{$call_type}\001{$call_name}\001{$end_point}\001{$timestamp}\001{$attachment}\004\n";

OWL^ATRACE^A版本号^A应用名^A时间^IP^A发送接收类型^A上下文UUID^A上下文父ID^A上下文追踪ID^ATRACE TYPE^ACALL NAME^AAEND POINT^ATIMESTAMP^AATTACHMENT

发送接收类型：SR\SS\CS\SR
上下文: uuid,parentId,Id
TRACE TYPE: HTTP\RPC\MYSQL\REDIS\MQ\MC\...
CALL NAME: ServiceName.methodName\HTTP.get\Memcache.get\...
END POINT: 本地、远程IP和端口
ATTACHMENT: RESULT TYPE:SUCCESS、EX:exceptionMessage 参数\结果\传递的信息大小、Appname、IP:PORT、hit\miss、CPU、内存占用 等等 K-V 附加信息
RESULT TYPE: SUCCESS\TIMEOUT\EXCEPTION

一段 Trace 日志例子：
-----------------

OWL^ATRACE^A0002^Arpc^A2014-06-20 17:21:20.000^A192.168.10.74^ASR^Aaaaaa-bbbbb-cccc-dddd^A0^A53a3fd1090d30^AHTTP^Ahttp.^A:^A1403256080593^A^D$
OWL^ATRACE^A0002^Arpc^A2014-06-20 17:21:20.000^A192.168.10.74^ACS^Aaaaaa-bbbbb-cccc-dddd^A53a3fd1090d30^A53a3fd1093906^AHTTP_CLIENT^AhttpClient.GET^A:^A1403256080604^AURL^Chttp://rpc.int.jumei.com/ServcieA/MethodB?c=323333^BMETHOD^CGET^BDATA^C^D$
OWL^ATRACE^A0002^Arpc^A2014-06-20 17:21:20.000^A192.168.10.74^ACR^Aaaaaa-bbbbb-cccc-dddd^A53a3fd1090d30^A53a3fd1093906^A^A^A^A1403256080655^ARES_TYPE^CSUCCESS^BDATA^C{}^D$
OWL^ATRACE^A0002^Arpc^A2014-06-20 17:21:20.000^A192.168.10.74^ASS^Aaaaaa-bbbbb-cccc-dddd^A0^A53a3fd1090d30^A^A^A^A1403256080676^A^D$

上下文的传递、和其他日志的关联：
----------------------------

远程调用附加上下文信息：HTTP 调用通过 Header 附加，RPC 通过上下文位传递，RPC 客户端获取当前 $owl_context 全局变量并且序列化到请求中，RPC 服务器端反序列化 $owl_context 并赋值给当前的 $owl_context 全局变量。

`
<?php
global $owl_context;
echo $owl_context['uuid'];
echo $owl_context['parent_id'];
echo $owl_context['trace_id'];
?>
`

异常日志的打印附着 TarceId 和 SpanId，以关联调用链和异常信息

ID 的生成和采样：
-----------------

UUID 由 Nginx 插件生成并传入 PHP 应用，PHP应用框架将变量赋值到 global 变量中。
traceId 生成函数：uniqid() 。

潜在效率问题：
----------

IP 获取
时间戳生成
日志刷盘
ID 生成
集群服务器时间校准

`
<?php
$config = array(
	    'on' => true,
	    'app' => 'mq',
	    'logdir' => './data/log/trace'
	);

$trace_logger = TraceLogger::instance($config1);
// HTTP Server
global $owl_context;
$owl_context['uuid'] = ???;
$owl_context['parent_id'] = ???;
$owl_context['trace_id'] = ???;
$trace_logger->HTTP_SR();
$trace_logger->HTTP_SS();
// HTTP Client
$trace_logger->HTTP_CS($url, $method, $data);
$trace_logger->HTTP_CR($response_type, $response_data);
// MySQL Client
$trace_logger->MYSQL_CS($end_point, $method, $sql);
$trace_logger->MYSQL_CR($response_type, $response_data);
// RPC Server
global $owl_context;
$owl_context['uuid'] = ???;
$owl_context['parent_id'] = ???;
$owl_context['trace_id'] = ???;
$trace_logger->RPC_SR($service, $method, $params);
$trace_logger->RPC_SS();
// RPC Client
$trace_logger->RPC_CS($end_point, $service, $method, $params);
$trace_logger->RPC_CR($response_type, $response_data);
// MC Client
$trace_logger->MC_CS($end_point, $method, $query);
$trace_logger->MC_CR($response_type, $response_data);
// Redis Client
$trace_logger->REDIS_CS($end_point, $method, $query);
$trace_logger->REDIS_CR($response_type, $response_data);
?>
`

