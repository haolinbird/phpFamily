<?php
define('ROOT_PATH', __DIR__.'/../Vendor/');
require_once('../Vendor/Bootstrap/Autoloader.php');
Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->addRoot(__DIR__ . '/../')->addRoot(__DIR__.'/../../')->init();
