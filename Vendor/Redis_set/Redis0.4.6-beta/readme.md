# Redis

## 特性
* RedisCaChe (缓存Redis,一致性hash)
*  RedisStorage (存储Redis,取模)
*  支持multi和exec，但是事务只可以用一个key
*  支持mget和mset，但请尽量少用
*  数据迁移
*  节点映射(`master-alia`)
**客户端以下方法被禁用 :**

> "INFO", "KEYS", "BLPOP", "MSETNX", "BRPOP", "RPOPLPUSH", "BRPOPLPUSH", "SMOVE", "SINTER", "SINTERSTORE", "SUNION", "SUNIONSTORE", "SDIFF", "SDIFFSTORE", "ZINTER", "ZUNION",  "FLUSHDB", "FLUSHALL", "RANDOMKEY", "SELECT", "MOVE", "RENAMENX", "DBSIZE", "BGREWRITEAOF", "SLAVEOF", "SAVE", "BGSAVE", "LASTSAVE"
 
## 使用
### RedisCache
``` php
$cache = RedisCache::getInstance('default');
$cache->set("key", 1111);
$val = $cache->get("key");
$cache->close(); //如果是PHPSERVER，需要手动调用close来关闭连接。FPM的话就不close也行
```
### RedisStorage
``` php
$stor = RedisStorage::getInstance();
$stor->set("key", 1111);
$val = $stor->get("key");
$stor->close(); //如果是PHPSERVER，需要手动调用close来关闭连接。FPM的话就不close也行
```
### 事务
``` php
$stor = RedisStorage::getInstance();
$stor->MULTI();
$stor->incr("key");
$stor->incr("key");
$stor->incr("key");
$stor->EXEC();
echo $stor->get("key");
```
### 集群
``` php
$config = array(//存储的配置，要求的配置格式。
    'WEB' => array('nodes' => array(
            array('master' => "192.168.8.230:27000", 'slave' => "192.168.8.231:27000"),
            array('master' => "192.168.8.230:27001", 'slave' => "192.168.8.231:27001"),
        ),
        'db' => 15
    ),
    'APP' => array('nodes' => array(
            array('master' => "192.168.8.232:27008", 'slave' => "192.168.8.231:27008"),
            array('master' => "192.168.8.232:27009", 'slave' => "192.168.8.231:27009"),
        ),
        'db' => 14
    )
);
RedisMultiStorage::config($config); //入口文件配置一次
$WEB = RedisMultiStorage::getInstance("WEB");//获取WEB前端redis集群实例（存储）
$APP = RedisMultiStorage::getInstance("APP");//获取APP的redis集群实例（存储）
$WEB->set("key5", "web");
var_dump($WEB->get("key5"));
$APP->set("key5", "app");
var_dump($APP->get("key5"));

RedisMultiCache::config($config); //入口文件配置一次
$WEB = RedisMultiCache::getInstance("WEB");//获取WEB前端redis集群实例（缓存）
$APP = RedisMultiCache::getInstance("APP");//获取APP的redis集群实例（缓存）
$WEB->set("key6", "web");
var_dump($WEB->get("key6"));
$APP->set("key6", "app");
var_dump($APP->get("key6"));
```
### 节点映射
> **存在`master-alia`配置时，原`master`节点将忽略，节点将映射为`master-alia`，`master`仅做映射处理，即不改变原有一致性hash节点**

示例请看 `数据迁移`配置部分。
### 数据迁移
> **提示:   请统一接入配置系统,迁移将会删除元节点数据**
##### 手动Rehash
> 迁移`default`配置
执行`Rehash.php`脚本,比如：`php Rehash.php RedisCache default`
##### 自动Rehash
新配置项未读到数据时，尝试从老配置读取，并迁移至新配置节点
> 需要设置 `$auto_rehash = true`
``` php
/* lazy migrate */
$redis = RedisCache::getInstance('default');
$val = $redis->get('aaaaa'); // 当未找到Cache时,使用老配置,并迁移至新节点,返回结果
```
### 配置

> Redis.php

``` php
<?php
namespace Config;

class Redis {
	//自动rehash
	public $auto_rehash = true;
	
	//通配符，用于迁移匹配的key,仅手动hash时有效,不配置时默认为'*'
	public $match = '[^/dev_test/]*';  //不以'/dev_test/'开头的所有key

	// 手动迁移时，每次从源Redis实例读取多少Key，不配置时默认为3000
	public $count = '3000';

	/* 新节点 */
	public $default = array(
		'nodes' => array(
			// 将`master`节点映射为`master-alia`
			array('master'=>'127.0.0.1:63656', 'slave'=>'127.0.0.1:9999'，'master-alia'=>'127.0.0.1:9379'),
			array('master'=>'127.0.0.1:6380'),
			),
		'db'=>2,
		);

	/* 旧节点 用于数据迁移*/
	public $default_previous = array(
		'nodes' => array(
			array('master'=>'127.0.0.1:6379','master-alia'=>'127.0.0.1:8888'),
			),
		'db' => 0
		);
}
```

> MNLogger.php

``` php
<?php
namespace Config;

class MNLogger
{
    // 迁移成功时日志配置
	public $_SUCCESS = array(
		'on'=>true,
		'app'=>'Redis',
		'logdir'=>'/tmp/logs/monitor/data/success'
	);
	// 迁移失败时日志配置
	public $_FAILED = array(
		'on'=>true,
		'app'=>'Redis',
		'logdir'=>'/tmp/logs/monitor/data/failed'
	);
}
```
##变更记录
###0.4.2-beta 变更
```
class Redis
{
    public $default_slave = array(
        'db' => 12,
        'consistent' => false, // 未定义或为true使用一致性hash算法，为false使用随机选择算法
        'nodes' => array(
            array('master' => '127.0.0.1:6379'),
            array('master' => '127.0.0.1:7369'),
        ),
    );
}
```
###0.4.3-beta 变更
1. 取消multi事务中只能操作一个key的限制
2. 加入对pipeline的支持
3. 仅在随机模式下支持multi/pipeline(consistent = false)

###0.4.4-beta-2 变更
1. 支持新版连接池
2. 增加预定义常量JM_PHP_CONN_POOL_OLD_ON, 为true则切回老版本连接池

###0.4.4-beta3 变更
1. __call方法出现异常，unset掉连接
2. 优化redis事务实现，解决在0.4.3-beta中对于consistent=false模式下无法使用同一个key事务的bug。 

###0.4.4-beta4 变更
1. redis组件之前有个常量叫“JM_PHP_CONN_POOL_ON” 该配置文件决定是否使用连接池，true为使用（可能使用旧版，也可能使用新版），false为直连redis-server
2. 在JM_PHP_CONN_POOL_ON==true的基础上，如果JM_PHP_CONN_POOL_OLD_ON==true则使用老版本连接池，JM_PHP_CONN_POOL_OLD_ON==false或者不定义，则使用新版本连接池

###0.4.5-beta 变更
1. 异常中带上目标ip:port
2. config增加timeout

###0.4.5 变更
基于0.4.5-beta的稳定版


###0.4.6-beta变更日志
1. 支持全链路压测
    1.1 压测环境数据固定使用db 13;
    1.2 生产环境禁用db 13;
2. 汉化异常信息；
3. 去除旧版连接池逻辑；
