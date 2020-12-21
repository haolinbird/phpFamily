<?php
require_once(__DIR__ . '/common.php');


while(1) {
global $context;
$context['X-Jumei-Loadbench'] = 'bench';
    try{
$db = \Db\Connection::instance();
$res = $db->read('ankerbox_work')->queryAll('SELECT `idc_name`,`idc_numbers` FROM `idc_numbers` limit 1');
//$res = $db->write('key_db')->queryScalar('SELECT `idc_numbers` FROM `idc_numbers` limit 1');
var_dump($res);
return;
    }catch (\Exception $e){
        echo time() .'   ' . $e->getMessage() . PHP_EOL;
    }
global $context;
$context['Loadbench'] = 0;
    try{
$db = \Db\Connection::instance();
$res = $db->read('key_db')->queryScalar('SELECT `idc_numbers` FROM `idc_numbers` limit 1');
//$res = $db->write('key_db')->queryScalar('SELECT `idc_numbers` FROM `idc_numbers` limit 1');
//var_dump($res);
    }catch (\Exception $e){
        echo time() .'   ' . $e->getMessage() . PHP_EOL;
    }
    if (rand(0,10) !== 1) {
        $db->closeAll();
    }
    //break;
    //usleep(500000);
}
