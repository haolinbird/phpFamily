<?php
/**
* Rehash script
* 最多5个进程，不同进程只负责迁移自己的节点，加快速度
*/
define('ROOT_PATH', __DIR__.'/../../');
require ROOT_PATH.'Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->addRoot(ROOT_PATH.'/Vendor')->init();


if ($argc != 3) {
	throw new Exception("Params not correct.For example using `php Rehash.php RedisCache default` to migrate default config as Cache.");
}
$class = 'Redis\\'.$argv[1];
$name = $argv[2];

$redis_previous = $class::getInstance($name . '_previous');
$redis_previous->_rehash(getAllRealTargets($redis_previous));

function getAllRealTargets($redisObject)
{
	$targets = array();
	$config = $redisObject->config;
	$config = isset($config['nodes']) ? $config['nodes'] : array();
	foreach ($config as $target) {
		$targets[] = isset($target['master-alia']) ? $target['master-alia'] : $target['master'];
	}

	return $targets;
}