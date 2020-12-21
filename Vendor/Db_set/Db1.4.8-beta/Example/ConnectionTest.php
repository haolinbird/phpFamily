<?php
require_once(__DIR__ . '/common.php');

while(1) {
    try{
$db = \Db\Connection::instance();
$res = $db->read('key_db')->queryScalar('SELECT `idc_numbers` FROM `idc_numbers` limit 1');
//$res = $db->write('key_db')->queryScalar('SELECT `idc_numbers` FROM `idc_numbers` limit 1');
var_dump($res);
    }catch (\Exception $e){
        echo $e->getMessage() . PHP_EOL;
    }
    if (rand(0,10) !== 1) {
        $db->closeAll();
    }
    //break;
    //usleep(500000);
}
