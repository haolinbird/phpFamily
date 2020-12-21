<?php
use Redis;

include_once __DIR__ . '/RedisBase.php';
include_once __DIR__ . '/RedisCache.php';
include_once __DIR__ . '/RedisStorage.php';
include_once __DIR__ . '/RedisMultiCache.php';
include_once __DIR__ . '/RedisMultiStorage.php';

#require_once __DIR__ . '/Vendor/Bootstrap/Autoloader.php';

#\Bootstrap\Autoloader::instance()->init();

/*
$config = array(//存储的配置，要求的配置格式。
    'nodes' => array(
        array('master' => "192.168.8.230:27000", 'slave' => "192.168.8.231:27000"),
        array('master' => "192.168.8.230:27001", 'slave' => "192.168.8.231:27001"),
        array('master' => "192.168.8.230:27002", 'slave' => "192.168.8.231:27002"),
        array('master' => "192.168.8.230:27003", 'slave' => "192.168.8.231:27003"),
        array('master' => "192.168.8.230:27004", 'slave' => "192.168.8.231:27004"),
        array('master' => "192.168.8.232:27005", 'slave' => "192.168.8.231:27005"),
        array('master' => "192.168.8.232:27006", 'slave' => "192.168.8.231:27006"),
        array('master' => "192.168.8.232:27007", 'slave' => "192.168.8.231:27007"),
        array('master' => "192.168.8.232:27008", 'slave' => "192.168.8.231:27008"),
        array('master' => "192.168.8.232:27009", 'slave' => "192.168.8.231:27009"),
    ),
    'db' => 0
);
RedisStorage::config($config);

$config = array(//缓存的配置，要求的配置格式。
    'nodes' => array(
        array('master' => "192.168.8.230:27000", 'slave' => "192.168.8.231:27000"),
        array('master' => "192.168.8.230:27001", 'slave' => "192.168.8.231:27001"),
        array('master' => "192.168.8.230:27002", 'slave' => "192.168.8.231:27002"),
        array('master' => "192.168.8.230:27003", 'slave' => "192.168.8.231:27003"),
        array('master' => "192.168.8.230:27004", 'slave' => "192.168.8.231:27004"),
        array('master' => "192.168.8.232:27005", 'slave' => "192.168.8.231:27005"),
        array('master' => "192.168.8.232:27006", 'slave' => "192.168.8.231:27006"),
        array('master' => "192.168.8.232:27007", 'slave' => "192.168.8.231:27007"),
        array('master' => "192.168.8.232:27008", 'slave' => "192.168.8.231:27008"),
        array('master' => "192.168.8.232:27009", 'slave' => "192.168.8.231:27009"),
    ),
    'db' => 2
);

RedisCache::config($config);
//***************************以上入口文件配置一次,向王伟同学要ip端口***************************************
//*******************************cache的使用方法*************************************

$cache = RedisCache::getInstance();
$cache->set("key", 1111);
var_dump($cache->get("key"));
$cache->close(); //如果是PHPSERVER，需要手动调用close来关闭连接。FPM的话就不close也行
//*******************************存储的使用方法*************************************
$stor = RedisStorage::getInstance();
$stor->set("key", 1111);
var_dump($stor->get("key"));
$stor->close(); //如果是PHPSERVER，需要手动调用close来关闭连接。FPM的话就不close也行
//*******************************事務的使用方法（只支持單個key）*************************************
$stor = RedisStorage::getInstance();
$stor->MULTI();
$stor->incr("key");
$stor->incr("key");
$stor->incr("key");
$stor->EXEC();
echo $stor->get("key");
*/

//***************************如果你在项目中需要访问2个集群的数据，需要下面的方法*****************
$config = array(//存储的配置，要求的配置格式。
    'WEB' => array('nodes' => array(
            array('master' => "192.168.8.230:27000", 'slave' => "192.168.8.231:27000"),
            array('master' => "192.168.8.230:27001", 'slave' => "192.168.8.231:27001"),
            array('master' => "192.168.8.230:27002", 'slave' => "192.168.8.231:27002"),
            array('master' => "192.168.8.230:27003", 'slave' => "192.168.8.231:27003"),
            array('master' => "192.168.8.230:27004", 'slave' => "192.168.8.231:27004"),
            array('master' => "192.168.8.232:27005", 'slave' => "192.168.8.231:27005"),
            array('master' => "192.168.8.232:27006", 'slave' => "192.168.8.231:27006"),
            array('master' => "192.168.8.232:27007", 'slave' => "192.168.8.231:27007"),
            array('master' => "192.168.8.232:27008", 'slave' => "192.168.8.231:27008"),
            array('master' => "192.168.8.232:27009", 'slave' => "192.168.8.231:27009"),
        ),
        'db' => 15
    ),
    'APP' => array('nodes' => array(
            array('master' => "192.168.8.230:27000", 'slave' => "192.168.8.231:27000"),
            array('master' => "192.168.8.230:27001", 'slave' => "192.168.8.231:27001"),
            array('master' => "192.168.8.230:27002", 'slave' => "192.168.8.231:27002"),
            array('master' => "192.168.8.230:27003", 'slave' => "192.168.8.231:27003"),
            array('master' => "192.168.8.230:27004", 'slave' => "192.168.8.231:27004"),
            array('master' => "192.168.8.232:27005", 'slave' => "192.168.8.231:27005"),
            array('master' => "192.168.8.232:27006", 'slave' => "192.168.8.231:27006"),
            array('master' => "192.168.8.232:27007", 'slave' => "192.168.8.231:27007"),
            array('master' => "192.168.8.232:27008", 'slave' => "192.168.8.231:27008"),
            array('master' => "192.168.8.232:27009", 'slave' => "192.168.8.231:27009"),
        ),
        'db' => 14
    ),
    'TEST' => array(
        'nodes' => array(
            array('master' => "10.16.52.412:4400", 'slave' => "10.16.52.412:4411"),
            array('master' => '10.16.52.412:3412', 'slave' => '10.16.52.412:2411'),
            array('master' => "253.11.255.0:4400", 'slave' => '253.11.255.0:4411'),
            array('master' => "19.20.113.98:2200",'slave' => '19.20.113.98:2233'),
            array('master' => "180.95.66.72:1300", 'slave' => '180.95.66.72:1311'),
            array('master' => '175.59.36.27:9999', 'slave' => '175.59.36.27.9988'),
            array('master' => '221.85.94.23:5764', 'slave' => '211.85.94.23:5766'),
            array('master' => '10.16.36.555:6540', 'slave' => '10.16.36.255.6540'),
            array('master' => '19.20.113.95:5522', 'slave' => '19.20.113.955.5533'),
            array('master' => '180.95.66.72:6644', 'slave' => '180.95.66.72.6655'),
            array('master' => '10.16.36.88:7460', 'slave' => '10.16.36.88:7465'),
            array('master' => '123.13.14.20:5201', 'slave' => '123.13.14.20:5202'),
            array('master' => '13.14.20.20:1314', 'slave' => '13.14.20.20:2020')
        ),
        'db' => 1,
        'sort_before_hash' => true,
    ),
);
Redis\RedisMultiStorage::config($config); //入口文件配置一次
#$WEB = RedisMultiStorage::getInstance("WEB");//获取WEB前端redis集群实例（存储）
#$APP = RedisMultiStorage::getInstance("APP");//获取APP的redis集群实例（存储）
#$WEB->set("key5", "web");
#var_dump($WEB->get("key5"));
#$APP->set("key5", "app");
#var_dump($APP->get("key5"));
$test = Redis\RedisMultiStorage::getInstance("TEST");
$test->set("hardstyle", "111");



#RedisMultiCache::config($config); //入口文件配置一次
#$WEB = RedisMultiCache::getInstance("WEB");//获取WEB前端redis集群实例（缓存）
#$APP = RedisMultiCache::getInstance("APP");//获取APP的redis集群实例（缓存）
#$WEB->set("key6", "web");
#var_dump($WEB->get("key6"));
#$APP->set("key6", "app");
#var_dump($APP->get("key6"));

?>