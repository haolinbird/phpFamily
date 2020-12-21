<?php

/**
 * 多进程测试id生成器
 *
 * @author xudongw xudongw@jumei.com
 */

require 'common.php';
require_once 'interfaces.php';

$g = new \Globalkey\JMDbKeyBatch;
$id = $g->getId('payment_refund_bill', 3);
echo $id . PHP_EOL;
