<?php
require __DIR__.'/../Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../../')->addRoot(__DIR__.'/Example/')->init();

$db = Db\Connection::instance()->read('jumei');
$re = $db->select('*')->from('user')->where()->limit(10)->queryAll();
var_dump($re);