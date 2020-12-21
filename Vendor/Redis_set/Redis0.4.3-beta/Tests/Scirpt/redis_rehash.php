<?php
define('ROOT_PATH', __DIR__.'/../../../../');
require ROOT_PATH.'Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->addRoot(ROOT_PATH.'/Vendor')->init();

//Cache
$config = array(
	'127.0.0.1:6379',
	'127.0.0.1:6380'
	);
$redis = Redis\RedisCache::getInstance('default_previous');
$ret = $redis->_rehash($config);
var_dump($ret);

