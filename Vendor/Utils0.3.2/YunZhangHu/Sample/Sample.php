<?php
/**
 * 实名认证示例文件.
 *
 * @author qiangd <qiangd@jumei.com>
 */

date_default_timezone_set('Asia/Shanghai');
require_once __DIR__ . '/../../../Bootstrap/Autoloader.php';
\Bootstrap\Autoloader::instance()->addRoot(__DIR__  . '/../../../../')->init();
require_once __DIR__ . '/../../Singleton.php';
require_once __DIR__ . '/../DesUtils.php';
require_once __DIR__ . '/../IdentityVerify.php';
require_once __DIR__ . '/../Config/YunZhangHu.schema.php';
require_once __DIR__ . '/../../Log/Logger.php';

$params = array(
    'trans_id' => 'test-' . time(),
    'id_card' => '511081198304210214',
    'id_holder' => '董强',
);
// trans_id 为单据ID 全局唯一.

//  2 二要素.
$res = \Utils\YunZhangHu\IdentityVerify::instance()->auth($params, 2);
var_dump($res);
// 3 三要素.
$params = array(
    'trans_id' => 'test-' . time(),
    'acc_no' => '6217003810002326976',
    'id_card' => '511081198304210214',
    'id_holder' => '董强',
);
$res = \Utils\YunZhangHu\IdentityVerify::instance()->auth($params, 3);
var_dump($res);
// 4 四要素.
$params = array(
    'trans_id' => 'test-' . time(),
    'acc_no' => '6217003810002326976',
    'id_card' => '511081198304210214',
    'id_holder' => '董强',
    'mobile' => '18108080402',
);
$res = \Utils\YunZhangHu\IdentityVerify::instance()->auth($params, 4);
var_dump($res);
