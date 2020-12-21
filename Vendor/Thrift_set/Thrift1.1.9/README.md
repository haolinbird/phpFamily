 * 版本1.1.7
 * 发布时间 2015-02-10
 * 2015-02-10 public static $smsAlarmPrarm
 * 2015-01-20 使用文件锁代替信号量
 * 2015-01-13 短信告警治理
 * 2014-12-01 所有操作共享内存的地方加锁
 * 2014-08-24 thrift客户端text协议支持故障ip踢出，支持按照项目配置客户端，修复types.php中的类调用前没加载问题
 * 2014-07-15 去掉老的mnlogger埋点 加入tracelogger
 * 2014-06-06 Thrift客户端支持文本协议 

RPC通用客户端,  
 * 支持异步调用 注意：使用text协议时无法使用客户端异步 
 * 支持故障ip自动踢出  
 * 支持故障ip自动探测及恢复  

具体使用实例
=======
  
见[PHP Service 客户端使用实例](http://wiki.int.jumei.com/index.php?title=PHP_Service_%E5%AE%A2%E6%88%B7%E7%AB%AF%E4%BD%BF%E7%94%A8%E5%AE%9E%E4%BE%8B)



