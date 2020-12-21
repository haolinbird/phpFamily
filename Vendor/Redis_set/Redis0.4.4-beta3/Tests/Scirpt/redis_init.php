<?php
define('ROOT_PATH', __DIR__.'/../../../../');
require ROOT_PATH.'Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->addRoot(ROOT_PATH.'/Vendor')->init();

$redis = Redis\RedisCache::getInstance('default');


$data = array();

for($i = 0; $i < 1000000; $i++)
{
	// $key = str_repeat(chr(65+$i),5).$i.'string';
	// $val = str_repeat(chr(97+$i), 10);
	$t = 8;
	$rand = '';
	while($t-- >=0) {
		$rand .= chr(rand(64,126));
	}
	$key = 'KEY.'.$rand.$i;
	$val = $key;

	//String
	echo "$i:$key=>$val\n";
	if (!$redis->set($key, $val)) {
		echo "Redis string key: $key, val: $val\n";
	}
	continue;
	//hash
	if (FALSE === $redis->hSet($key.'hash', $key, $val)) {
		echo "Redis hash key: $key, val: $val\n";
	}
	//list
	if (FALSE === $redis->rPush($key.'list', $val)) {
		echo "Redis list key: $key, val: $val\n";
	}
	//set
	if (FALSE === $redis->sAdd($key.'set', $val)) {
		echo "Redis set key: $key, val: $val\n";
	}
	$data[$key] = $val;
}