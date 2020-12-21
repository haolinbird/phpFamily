<?php

require_once __DIR__ . '/vendor/autoload.php';
use MNLogger\TraceLogger;


$config5 = array(
     'on' => true,
     'app' => 'rpc',
     'logdir' => './home/logs/monitor/trace'
);

$logger = TraceLogger::instance($config5);

global $owl_context;
// Nginx 传入
$owl_context['uuid'] = 'aaaaa-bbbbb-cccc-dddd';
//$owl_context['parent_id'];
//$owl_context['trace_id'];

$logger->HTTP_SR();
usleep(10000);
$url = 'http://rpc.int.jumei.com/ServcieA/MethodB?c=323333';
$method = 'GET';
$data = '';
$logger->HTTP_CS($url, $method, $data);
usleep(50000);
$response_type = 'SUCCESS';
$response_data = 'NULL';
$logger->HTTP_CR($response_type, $response_data);
usleep(20000);
$logger->HTTP_SS();