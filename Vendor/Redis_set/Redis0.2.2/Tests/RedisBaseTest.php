<?php
require 'common.php';
class RedisBaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testGet()
    {
        $redis = \Redis\RedisMultiCache::getInstance('default');
        $key = __CLASS__.'-'.__METHOD__;
        $data = date('Y-m-d H:i:s');
        $redis->set($key, $data);
        $logger = new MNLoggerCallerTest();
        $returnData = $logger->testCall1(array($redis, 'get'), array($key));
        $this->assertEquals($data, $returnData);
    }

}


class MNLoggerCallerTest{
    public function testCall1($callback, $args)
    {
        return call_user_func_array($callback, $args);
    }
}