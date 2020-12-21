<?php
class Module_Example extends Module_Base{
    public function testRedisSet(){
        \Redis\RedisMultiCache::getInstance("default")->set("abc", 1);
    }
}