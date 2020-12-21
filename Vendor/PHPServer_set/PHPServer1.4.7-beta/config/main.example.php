<?php

return array(
    'workers' => array(
        'QuotaAgent' => array(
            'protocol'              => 'udp', 
            'port'                  => 1984,
            'child_count'           => 1,   // 固定为1
            'send_timeout'          => 10,                             
        ),

        // 统计接口调用结果 只开一个进程 已经配置好，不用设置
        'StatisticWorker' => array(
            'protocol'              => 'udp',
            'port'                  => 20205,
            'child_count'           => 1,
        ),
        
        // 查询接口调用结果 只开一个进程 已经配置好，不用再配置
        'StatisticGlobal' => array(
            'protocol'              => 'tcp',
            'port'                  => 20203,
            'child_count'           => 1,
        ),

        // 查询接口调用结果 只开一个进程 已经配置好，不用再配置
        'StatisticProvider' => array(
            'protocol'              => 'tcp',
            'port'                  => 20204,
            'child_count'           => 1,
        ),
            
        // 监控server框架的worker 只开一个进程 framework里面需要配置成线上参数
        'Monitor' => array(
            'protocol'              => 'tcp',
            'port'                  => 20305,
            'child_count'           => 1,
            'framework'             => array(
                 'phone'   => '15551251335',      // 告警电话
                 'url'     => 'http://xxx.xxx',   // 发送短信调用的url 上线时使用下面线上的配置
                 //'url'     => 'http://sms.int.jumei.com/send',  // 发送短信调用的url
                 'param'   => array(                            // 发送短信用到的参数
                     'channel' => 'monternet',                    
                     'key'     => 'notice_rt902pnkl10udnq',                
                     'task'    => 'int_notice',      
                 ),
                 'min_success_rate' => 98,                    // 框架层面成功率小于这个值时触发告警
                 'max_worker_normal_exit_count' => 1000,      // worker进程退出（退出码为0）次数大于这个值时触发告警
                 'max_worker_unexpect_exit_count' => 10,      // worker进程异常退出（退出码不为0）次数大于这个值时触发告警
             )
        ), 
        
        // [开发环境用，生产环境可以去掉该项]耗时任务处理，发送告警短信 邮件，监控master进程是否退出,开发环境监控文件更改等
        'FileMonitor' => array(
            'protocol'              => 'udp',
            'port'                  => 10203,
            'child_count'           => 1,
        ),
            
        // [开发环境用，生产环境可以去掉该项]rpc web测试工具
        'TestClientWorker' => array(
            'protocol'              => 'tcp',
            'port'                  => 30303,
            'child_count'           => 1,
        ),
        
        // [开发环境用，生产环境可以去掉该项]thrift rpc web测试工具
        'TestThriftClientWorker' => array(
            'protocol'              => 'tcp',
            'port'                  => 30304,
            'child_count'           => 1,
        ),
        'ServiceRegister' => array(
            'protocol'              => 'tcp',
            'port'                  => 23333,
            'child_count'           => 1,
            'worker_class'          => 'ServiceRegister',                  
        ),
    ),
    
    'ENV'          => 'dev', // dev or production
    'worker_user'  => '', //运行worker的用户,正式环境应该用低权限用户运行worker进程

    // 数据签名用私匙
    'rpc_secret_key'    => '769af463a39f077a0340a189e9c1ec28',
    
    // 项目名称，和配置系统项目名一致，例如Koubei
    'project_name' => 'xxx',
    
    // 日志追踪 trace_log 日志目录
    'trace_log_path'    => '/home/logs/monitor',
    // 异常监控 exception_log 日志目录
    'exception_log_path'=> '/home/logs/monitor',
    // 是否开启日志追踪监控
    'trace_log_on'      => true,
    // 是否开启异常监控
    'exception_log_on'  => true,
    // 日志追踪采样，10代表 采样率1/10, 100代表采样率1/100
    'trace_log_sample'  => 10,
    // 配额文件目录，用于配额限制
    'quota_file_dir'    => '/dev/shm/phpserver-quota',
);
