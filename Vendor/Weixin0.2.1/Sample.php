<?php
require_once __DIR__ . '/Vendor/Bootstrap/Autoloader.php';
require_once __DIR__ . '/Base.php';
require_once __DIR__ . '/Applet.php';
require_once __DIR__ . '/H5.php';
\Bootstrap\Autoloader::instance()->init();

// $a = \Weixin\H5::instance('shuabao')->getAccesstoken();
// var_dump($a);
// $b = \Weixin\Applet::instance('shuabao')->sendCustomMessage('oF6TT5KTAyMHvINjP-Iw0h5dHN8A', 'text', ['content' => '说话呀']);
// var_dump($b);
// $c = \Weixin\Applet::instance('shuabao')->jscode2session('ddd');
// var_dump($c);
// $d = \Weixin\H5::instance('shuabao')->checkSignature('', '', '');
// var_dump($d);