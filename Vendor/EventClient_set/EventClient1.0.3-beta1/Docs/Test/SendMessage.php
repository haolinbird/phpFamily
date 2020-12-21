<?php
//init event client
require __DIR__.'/../../Vendor/Bootstrap/Autoloader.php';
use \EventClient\RpcClient as  EC;

\Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../Examples/')->addRoot(__DIR__.'/../../../')->init();
//prepare messages
$messageClassKey = 'test';
//$urls = array('http://www.jumei.com/', 'http://www.sina.com.cn/', 'http://www.baidu.com', 'http://www.usa.com');
//shuffle($urls);
//$info = file_get_contents($urls[0]);
$info = array("abc"=>123123, 12=>123123);
// var_dump($info);

$cC = 10;
$pid = 0;
while($cC--){
    $pid = pcntl_fork();
    if($pid == 0){
        break;
    }
}
if($pid > 0){
    return;
}

for($i=100; $i > 0; $i--)
{
    $message = array('order_id' => 899999, 'create_time' => microtime(true), 'info'=>$info);
    $priority = 1024;
    $timeToDelay = 0;;// 3600 * 9;
    $st = microtime(true);
    //send
    try{
        $ec = EC::instance();
        $return = $ec->setClass('Broadcast')->Send($messageClassKey, $message, $priority, $timeToDelay);
        var_dump($return);
//        $ec = EC::instance('psProto');
//        $return = $ec->setClass('Broadcast')->Send($messageClassKey, $message, $priority, $timeToDelay);
//        var_dump($return);

    }
    catch(\Exception $e)
    {
        var_dump($ec->debugInfo());
        echo $e;
    }
    $et = microtime(true);
    printf('time consumed in sending message:%.6f ms  ', 1000*($et-$st));
}
