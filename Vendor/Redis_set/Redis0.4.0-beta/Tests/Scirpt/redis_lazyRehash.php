<?php
define('ROOT_PATH', __DIR__.'/../../../../');
require ROOT_PATH.'Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->addRoot(ROOT_PATH.'/Vendor')->init();

$redis = Redis\RedisCache::getInstance('default');

for($i = 0; $i < 26; $i++)
{
	$key = str_repeat(chr(65+$i),5).$i.'string';
	$val = str_repeat(chr(97+$i), 10);

	$ret = $redis->get($key);
	if (strcmp($val, $ret) !== 0) {
		echo "Error $key:$ret\n";
	}
}