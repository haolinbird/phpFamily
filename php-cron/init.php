<?php
/**
 * 入口文件.
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2020-12-24 10:28:30
 */

/**
 * 处理完成回调函数.
 *
 * @return void
 */
function on_phpserver_request_finish($class = '', $requestParam = '', $response = '')
{
    if (class_exists('\Redis\RedisMultiCache', false)) {
        \Redis\RedisMultiCache::close();
    }

    if (class_exists('\Redis\RedisMultiStorage', false)) {
        \Redis\RedisMultiStorage::close();
    }

    if (class_exists('\\Db\\Connection', false) && is_callable(array(\Db\Connection::instance(), 'closeAll'))) {
      //  \Db\Connection::instance()->closeAll();
    }

    if (class_exists('\\Db\\ShardingConnection', false) && is_callable(array(\Db\ShardingConnection::instance(), 'closeAll'))) {
      //  \Db\ShardingConnection::instance()->closeAll();
    }
}


define('ROOT_PATH', __DIR__.DIRECTORY_SEPARATOR);
require ROOT_PATH.'/Vendor/Bootstrap/Autoloader.php';
require ROOT_PATH.'/Vendor/autoload.php';
require_once(ROOT_PATH.'/Vendor/PHPClient/JMTextRpcClient.php');
\Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->init();

// 加载全局函数
require ROOT_PATH.'/Common/function.php';

define('JM_APP_NAME', 'php-cron');
