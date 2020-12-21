# 消息-事件客户端中心说明

###### _目前只开放了 Broadcast::Send 方法. 通过此方法可以向自己或者所有已订阅此消息类型系统发送(广播)消息。_

## RPC客户端    
* ### 配置
  参考Docs/Examples/Cfg/RpcClient.php, 第一次配置时，可将此文件拷贝至Cfg目录。    
  其中的`user`及`secret_key`即消息事件管理中心-订阅者管理中相应地 _订阅者键_ 和 _订阅者私钥_
  
* ### 集成

    从 [https://hg.jumeicd.com/EventClient](https://chaos@hg.jumeicd.com/EventClient) 获取消息事件中心客户端代码，集成至自己的项目代码中，建议使用子仓库模式以便客户端代码和项目代码的管理解耦。    

* ### 获取实例

    参考以下代码：    
    
        require 'Path/To/EventClient/init.php';
        use \Event\Client\Lib\RpcClient as EC;
        $ec = EC::instance();


* ### 方法调用    
    
    参考以下代码：     
    
        //prepare messages
        $messageClassKey = 'user_report';
        $message = array('order_id' => 123, 'create_time' => 13289811);
        $priority = 100;
        $timeToDelay = 3600 * 9;
        
        //send
        try{
            $ec = EC::instance();
            $return = $ec->setClass('Broadcast')->Send($messageClassKey, $message, $priority,$timeToDelay);
           var_dump($return);
          }
        catch(\Exception $e)
        {
            var_dump($ec->debugInfo());
            echo $e;
        }        


* ### 注意事项

  * message中的数据必须使用utf字符集，使用其它字符集时请先进行转换。

