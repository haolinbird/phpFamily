<?php
//init event client
require __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'init.php';
use \Event\Client\Lib\RpcClient as EC;

//prepare messages
$messageClassKey = 'jm_event_test_message';
$urls = array('http://www.jumei.com/', 'http://www.sina.com.cn/', 'http://www.baidu.com', 'http://www.usa.com');
shuffle($urls);
$info = file_get_contents($urls[0]);
// var_dump($info);
for($i=10; $i > 0; $i--)
{
    $message = array('order_id' => 123, 'create_time' => microtime(true), 'info'=>$info);
    $priority = 100;
    $timeToDelay = 0;;// 3600 * 9;
    $st = microtime(true);
    //send
    try{
        $ec = EC::instance();
        $return = $ec->setClass('Broadcast')->Send($messageClassKey, $message, $priority, $timeToDelay);
        var_dump($return);
    }
    catch(\Exception $e)
    {
        var_dump($ec->debugInfo());
        echo $e;
    }
    $et = microtime(true);
    printf('time consumed in sending message:%.6f ms  ', 1000*($et-$st));
}