<?php


class RealConnectionTest extends  \PHPUnit_Framework_TestCase{
    public function testQuery()
    {
        $db = \Db\Connection::instance()->read('tuanmei');
        $db->select('1')->from('tuanmei_user')->where(array('1=1', 'uid'=>2))->queryAll();
        $this->assertEquals(true, true);
    }

    public function testInset(){
        $db = \Db\Connection::instance()->write('tuanmei');
        $resutl = $db->insert('tuanmei_user', array('uid'=>99999999,'nickname'=>'dbtestuser99999','email'=>'testemail99999'));
        $this->assertNotFalse($resutl);
        echo $db->delete('tuanmei_user', array('uid'=>99999999));
    }

    public function testShardingQuery()
    {
        $shard = new TestSharding();
        $data = $shard->Test();
        $this->assertEquals(true, true);
    }
}


class TestSharding extends \UnitTest\ShardingDbBase{
     const TABLE_MAP_NAME = "user";

     public function Test(){
	    $ciphertext = 128;
        $rule = new \UnitTest\TrusteeshipRule($ciphertext);

        $conn = $this->getDbSharding($rule);
        $conn->setDataCenterRule($rule);
	    $data = $conn->read($rule)->find($rule->getTableName(self::TABLE_MAP_NAME), array('uid' => $ciphertext));
	    $conn->setDataCenterRule(null);
        return $data;
     }
   
}


