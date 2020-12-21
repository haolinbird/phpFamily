<?php
require 'common.php';
use Memcache\Pool;

global $context;
 $context['Loadbench'] = 1;
Pool::instance()->set('foo', time());
$v = Pool::instance()->get('foo');
var_dump($v);

sleep(1);
global $context;
 $context['Loadbench'] = 0;
Pool::instance()->set('foo', time());
$v = Pool::instance()->get('foo');

var_dump($v);
