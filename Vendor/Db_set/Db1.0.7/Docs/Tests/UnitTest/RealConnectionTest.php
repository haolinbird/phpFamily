<?php
class RealConnectionTest extends  \PHPUnit_Framework_TestCase{
    public function testQuery()
    {
        $db = \Db\Connection::instance()->read('jumei');
        $this->assertEquals(true, true);
    }
}
