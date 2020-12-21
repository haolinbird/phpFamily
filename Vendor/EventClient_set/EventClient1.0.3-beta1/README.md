#消息/事件中心客户端

##历史版本
### 1.0.1    
网络连接失败时重试3此，提高消息发送成功的几率。

### 1.0.2    
支持PHPServer下的JmText协议。（服务端已经搭建了运行在PHPServer上的rpc服务.）    
配置可还是参考Exmaple文件或者如下代码：    
<pre>
    array(
        // rpc server secret key.
        'rpc_secret_key' => '769af463a39f077a0340a189e9c1ec28',
        'user'=>'test',
        // message/event center subscriber secret key.
        'secret_key'=>'test_password',
        'hosts' => "#{mec.rpc.servers}"
    );
</pre>

### 1.0.3-beta1
支持mcpd连接池, 配置如下:
<pre>
    array(
        // rpc server secret key.
        'rpc_secret_key' => '769af463a39f077a0340a189e9c1ec28',
        'user'=>'test',
        // message/event center subscriber secret key.
        'secret_key'=>'test_password',
        'hosts' => "#{mec.rpc.servers}"

        // mcpd连接池需要的配置
        'service' => '',
        'dove_key' => '',
    );  
</pre>
