<?php
require 'common.php';
class JMDbKeyBatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $name 项目名称
     * @dataProvider projectFlags
     */
    public function testGetId($name)
    {
        $g = new \Globalkey\JMDbKeyBatch;
        $id = $g->getId($name);
        echo "New id {$id} for project $name\n";
        $this->assertEquals(true, $id>0);
    }

    public function projectFlags()
    {
        return array(
            array('payment_refund_bill'),
            array('payment_biz_deal'),
            array('payment_balance_txns'),
            array('order')
        );
    }

}