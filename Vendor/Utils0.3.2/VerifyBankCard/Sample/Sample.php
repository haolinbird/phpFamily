<?php
date_default_timezone_set('Asia/Shanghai');
require_once __DIR__ . '/../../Vendor/Bootstrap/Autoloader.php';
\Bootstrap\Autoloader::instance()->addRoot(__DIR__  . '/../../')->init();
require_once __DIR__ . '/../../Singleton.php';
require_once __DIR__ . '/../XinYan.php';
require_once __DIR__ . '/../BaoFu.php';
require_once __DIR__ . '/../BFRSA.php';
// require_once __DIR__ . '/../Config/XinYan.schema.php';
require_once __DIR__ . '/../Config/BaoFu.schema.php';
require_once __DIR__ . '/../Config/Log.php';
require_once __DIR__ . '/../../Log/Logger.php';

$params = array(
    'trans_id' => 'test-' . time(),
    'acc_no' => '6217003810002326976',
    'id_card' => '511081198304210214',
    'id_holder' => '董强',
    'mobile' => '18030732715',
);
$res = \Utils\VerifyBankCard\BaoFu::instance()->idCardAuth($params);
print_r($res);exit;
$params['trans_id'] = 'test-' . (time() + 1);
$response = \Utils\VerifyBankCard\BaoFu::instance()->authsms($params);
print_r($response);
if ($response['success'] && $response['data']['code'] == 2) {
    $res = \Utils\VerifyBankCard\BaoFu::instance()->authconfirm(array('trade_no' => $response['data']['trade_no'], 'sms_code' => 123456));
    print_r($res);
}