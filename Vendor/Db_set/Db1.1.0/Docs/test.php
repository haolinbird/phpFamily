<?php
require __DIR__.'/../Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../../')->addRoot(__DIR__.'/Example/')->init();
$cn = 100;
while($cn--)
{
 $npid = pcntl_fork();
 if($npid == 0)
 {
    break;
 }
}
if($npid) exit;
//define('JM_PHP_CONN_POOL_ON', false);
$n=2000;
while($n--){
$db = Db\Connection::instance()->read('tuanmei');
$db->beginTransaction();
$re = $db->select('*')->from('tuanmei_user')->where()->limit(10)->queryAll();
//var_dump($re);
//var_dump($db->inTrans());
$db->commit();
$db->closeAll();
//var_dump($db->inTrans());
usleep(20000);
}
