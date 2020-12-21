<?php
require_once __DIR__ . '/../../Vendor/Bootstrap/Autoloader.php';
\Bootstrap\Autoloader::instance()->init();
require_once __DIR__ . '/../../Singleton.php';
require_once __DIR__ . '/../JMHiveApi.php';
require_once __DIR__ . '/../Config/JMHiveApiHosts.php';

$sql = "select * from sms_status_report limit 10";

$a = \Utils\JMHiveApi\JMHiveApi::instance()->request('crm', $sql, 3);
print_r($a);