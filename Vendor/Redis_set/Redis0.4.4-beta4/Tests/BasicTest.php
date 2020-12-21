<?php
require 'common.php';

try{
    $r = \Redis\RedisMultiStorage::getInstance('fav');
    $res = $r->get('foo');
    var_dump($res);
} catch(Exception $e) {
    echo $e->getMessage().PHP_EOL;
}
