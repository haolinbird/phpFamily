<?php
class RealConnectionTest extends  \PHPUnit_Framework_TestCase{
    public function testQuery()
    {
        $db = \Db\Connection::instance()->read('tuanmei');
        $db->select('1')->from('tuanmei_user')->where(array('1=1', 'uid'=>2))->queryAll();
        $this->assertEquals(true, true);
    }
}
