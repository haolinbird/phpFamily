日志记录组件
===========

V0.1.5-beta 版本:
----

增加判断是否为压测写入日志，以及压测日志路径的添加．

注意：

   改动的支持类型为：文本类型日志，json类型日志.
    
示例：

1.0 配置参考：/Doc/Example/Config/Log.php.压测路径在变量BENCH_LOG_ROOT中，　　
非压测路径在变量FILE_LOG_ROOT，注意路径是否具有php-fpm的读写权限，以及目录是否存在，　　
默认路径：压测:/var/log/x-jumei-bench/serviceName, 非压测：/var/log/serviceName.
 
* * *      
  使用示例：

    \Log\Hanlder::config((array) new \Config\Log()); // 初始化Log配置.
    $handler = \Log\Handler::instance("testFileLog"); // 实例（项目为:testFileLog）
    $handler->log("This is some test~"); // 写入日志
      
* * *
  配置实例:
    
    namespace Config;
       
    class Log{
        
        public  $FILE_LOG_ROOT = "/var/log/";
        
        public  $db = array('logger'=>'file',
        );
    
        public $testLogFile = array('logger' => 'file',　// 日志类型，目前压测日志写入只支持：file, jsonfile
            'path' => '/var/log/test_log.log', // 压测时此配置无效．非压测时若配置则写入此路径
            'rotateFormat' => 'Y-m-d'　// 日志文件名格式，　此时写入文件名为：test_log.2018-05-07.log
        );
    
        public  $admin = array(
            'logger' => 'jsonfile',
            'fields' => array('user', 'controller', 'action', 'params'),
        );
    }
    
   注意： 压测时都会日志写入路径为：/home/logs/x-jumei-bench/$cfgname下．
    
 2.0 依赖组件：JmArchiTracker >= 0.1.0.beta, 构建压测环境方法：调用Log组件之前，使用：\JmArchiTracker\Tracker::initBenchEnv()方法.
 
 
