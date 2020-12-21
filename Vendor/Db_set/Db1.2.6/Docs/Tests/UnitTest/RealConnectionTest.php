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
        $resutl = $db->insert('tuanmei_user', array('uid'=>999999990,'nickname'=>'dbtestuser','email'=>'testemail'));
        $this->assertNotFalse($resutl);
        $db->delete('tuanmei_user', array('uid'=>999999990));
    }
}
