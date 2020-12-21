<?php

require_once __DIR__ . '/vendor/autoload.php';
use MNLogger\MNLogger;

$config1 = array(
     'on' => true,
     'app' => 'mq',
     'logdir' => './data/log/mq'
);
$config2 = array(
     'on' => true,
     'app' => 'rpc',
     'logdir' => './data/log/rpc'
);

$logger1 = MNLogger::instance($config1);
$logger2 = MNLogger::instance($config2);

$logger1->log('mobile,send', '1');
$logger2->log('mobile,send', '2');
$logger1->log('mobile,send', '3');
$logger2->log('mobile,send', '4');