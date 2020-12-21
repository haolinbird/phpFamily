<?php
require_once('base.php');

$appid = "appid";
$idfa = "idfa";
$idfv = "idfv";
$imei = "imei";
$token = "token";
$uid = "1204245";
$source = "baidu";
$version = "2.12";


$client = new Notification\Device();
$ret = $client->remove('123','97902829910046732233');
var_dump($ret, $client->error);