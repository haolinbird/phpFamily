<?php
require 'common.php';
use Memcache\Pool;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * 测试save方法。
     *
     * @dataProvider saveValues
     */
    public function testSet($key, $v)
    {
        Pool::instance()->set($key, $v, 10);
//        trigger_error(E_USER_WARNING, 23123);
    }

    /**
     * 测试字段内容。
     *
     * @reutrn array
     */
    public function saveValues()
    {
        return array(
            array('mc_test_key_1', 'mc_test_key1-data:'.date('Y-m-d H:i:s'))
        ,
            array('mc_test_key_2', 'mc_test_key2-data:'.date('Y-M-d H:i:s'))
        );
    }
}