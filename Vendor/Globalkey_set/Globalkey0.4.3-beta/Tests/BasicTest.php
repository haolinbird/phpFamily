<?php

/**
 * 多进程测试id生成器
 *
 * @author xudongw xudongw@jumei.com
 */

require 'common.php';
require_once 'interfaces.php';

global $context;
$context['X-Jumei-Loadbench'] = 'bench';
$g = new \Globalkey\JMDbKeyBatch;
$id = $g->getId('payment_refund_bill');
echo $id . PHP_EOL;
