<?php

require_once __DIR__ . '/../Vendor/Bootstrap/Autoloader.php';

Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/')->addRoot(__DIR__.'/../Vendor')->addRoot(__DIR__.'/../../')->init();

use MNLogger\MNLogger;

$stats_logger = MNLogger::instance();

$stats_logger->log('test_stats', json_encode(array('t1' => 123, 't2'=>321)));