<?php
define('ROOT_PATH', __DIR__.'/../../../');
require ROOT_PATH.'Vendor/Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->addRoot(__DIR__)->addRoot(__DIR__.'/')->init();
