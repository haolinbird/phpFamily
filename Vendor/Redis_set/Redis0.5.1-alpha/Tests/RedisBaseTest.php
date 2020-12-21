<?php
require 'common.php';
class RedisBaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     */
    public function testGet()
    {
        $uids = array(100,200,1100,1900);
        foreach ($uids as $uid) {
            // 从普通集群中获取实例
            $cache0 = \Redis\RedisMultiCache::getInstance('default');
            $storage0 = \Redis\RedisMultiStorage::getInstance('fav');


            // 从普通集群获取实例
            $storage = \Redis\RedisMultiStorage::getInstance('fav')->partitionByUID($uid);

            $key = __CLASS__ . '-' . __METHOD__;
            $data = 'storage:' . date('Y-m-d H:i:s');
            $data0 = 'vvv' . $data;
            $st = microtime(true);
            $storage0->set($key, $data0);
            echo printf("%.9f", microtime(true) - $st)."\n";
            $storage->set($key, $data);
            $logger = new MNLoggerCallerTest();
            $returnData0 = $storage0->get($key);
            $returnData = $logger->testCall1(array($storage, 'get'), array($key));
            echo "key({$key}): Test data read from rediststorage: " . var_export($returnData, true) . "\n";
            $this->assertEquals($data, $returnData);
            $this->assertEquals($data0, $returnData0);
            $this->assertNotEquals($returnData, $returnData0);

            $data0 = 'vvv' . $data;
            $cache0->set($key, $data0);
            $returnData0 = $cache0->get($key);
            $data = 'cache:' . date('Y-m-d H:i:s');

            // 从分区集群获取实例
            $cache = \Redis\RedisMultiCache::getInstance('default')->partitionByUID($uid);

            $cache->set($key, $data);
          //  var_dump($cache0,"----------------------------------------------------------",$cache);die;
            $returnData = $logger->testCall1(array($cache, 'get'), array($key));
            echo "分区集群-key({$key}): Test data read from redistcache: " . var_export($returnData, true) . "\n";
            echo "普通集群-key({$key}): Test data read from redistcache: " . var_export($returnData0, true) . "\n";
            $this->assertEquals($data, $returnData);
            $this->assertEquals($data0, $returnData0);
            $this->assertNotEquals($returnData, $returnData0);
            $cache0->close();
            $cache->close();
            $storage0->close();
            $storage->close();
           // var_dump($cache0->getConfig());
            //var_dump($cache->getConfig());
        }
    }

    public function testMget()
    {
        $key = __CLASS__.'-'.__METHOD__;
        $d = array("{$key}_1"=>32, "{$key}_2"=>321);
        \Redis\RedisMultiCache::getInstance('fav')->mSet($d);
        \Redis\RedisMultiCache::getInstance('fav')->partitionByUID(20)->mSet($d);
        $r = \Redis\RedisMultiCache::getInstance('fav')->partitionByUID(20)->mGet(array("{$key}_1", "{$key}_2"));
        $this->assertEquals($r[0], $d["{$key}_1"]);
        $this->assertEquals($r[1], $d["{$key}_2"]);
    }

}


class MNLoggerCallerTest{
    public function testCall1($callback, $args)
    {
        return call_user_func_array($callback, $args);
    }
}
