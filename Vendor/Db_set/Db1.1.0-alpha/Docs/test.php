<?php
require __DIR__.'/../Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../../')->addRoot(__DIR__.'/Example/')->init();

$db = Db\Connection::instance()->read('tuanmei');
$db->beginTransaction();
$re = $db->select('*')->from('tuanmei_user')->where()->limit(10)->queryAll();
var_dump($re);
var_dump($db->inTrans());
$db->commit();
$db->closeAll();
var_dump($db->inTrans());