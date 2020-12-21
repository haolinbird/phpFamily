<?php
require __DIR__.'/../../Connection.php';
require __DIR__.'/../../Exception.php';
require __DIR__.'/../../Pool.php';
require __DIR__.'/../../../MNLogger/Base.php';
require __DIR__.'/../../../MNLogger/Exception.php';
require __DIR__.'/../../../MNLogger/TraceLogger.php';
$configs['default'] = array(
    'backup_hosts'=>array(array('host'=>'127.0.0.1', 'port'=>6007),
                          array('host'=>'127.0.0.1', 'port'=>6007)
    ),//可以单独列一组backup
    array('host'=>'127.0.0.1', 'port'=>11211),
    array('host'=>'127.0.0.1', 'port'=>6006, 'type'=>'backup')//也采用这种格式,用type标识
);

\MNLogger\TraceLogger::setUp(array('trace'=>array('app'=>'mctest','on'=>true, 'logdir'=>'/tmp/log/')));
\Memcache\Pool::config($configs);
//var_dump(\Memcache\Pool::instance()->set('test_11', 33, 3));
var_dump(\Memcache\Pool::instance()->get('test_11'));
