<?php
require_once __DIR__ . '/../../Vendor/Bootstrap/Autoloader.php';
\Bootstrap\Autoloader::instance()->init();
require_once __DIR__ . '/../../Singleton.php';
require_once __DIR__ . '/../Redis.php';


$key = 100;
$prefix = 'biz_name';
$a = \Utils\Locker\Redis::instance()->lock($prefix, $key);
// 加锁成功.
var_dump($a);
$b = \Utils\Locker\Redis::instance()->lock($prefix, $key);
// 加锁失败.
var_dump($b);
$c = \Utils\Locker\Redis::instance()->unlock($prefix, $key);
// 解锁成功.
var_dump($c);
$d = \Utils\Locker\Redis::instance()->lock($prefix, $key);
// 重新加锁成功.
var_dump($d);
