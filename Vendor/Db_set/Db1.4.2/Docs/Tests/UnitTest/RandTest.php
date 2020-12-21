<?php

error_reporting(E_ALL);

$data  = array(
    array( 'dsn' => 'dsn1', 'weight' => 100 ),
    array( 'dsn' => 'dsn2', 'weight' => 200 ),
    array( 'dsn' => 'dsn3', 'weight' => 300 ),
    array( 'dsn' => 'dsn4', 'weight' => 400 ),
    array( 'dsn' => 'dsn5', 'weight' => 500 ),
);

$count = 150;

//randOne
$start = microtime(true);
$result = array();
for ($i =0; $i < $count; $i++){
    $a = randOne($data);
    isset($result[$a['dsn']]) ? '': $result[$a['dsn']] = 0;
    $result[$a['dsn']] = (int)$result[$a['dsn']] +  1;
}

print_r((microtime(true) - $start) . "\n");
print_r("mem:".memory_get_usage() . "\n");
print_r($result);

// //getOneNode
$start = microtime(true);
$result = array();
for ($i =0; $i < $count; $i++){
    $a = getOneNode($data);
    isset($result[$a['dsn']]) ? '': $result[$a['dsn']] = 0;
    $result[$a['dsn']] = (int)$result[$a['dsn']] +  1;
}
print_r((microtime(true) - $start) . "\n");
print_r("mem:".memory_get_usage() . "\n");
print_r($result);


function randOne(array $arr){
    $weight = array();
    $count = 0;
    foreach( $arr as $k => $v){
        $count += (int)$v['weight'];
        $weight[$k] = $count;
    }
    $one = mt_rand(1,$count);
    foreach( $weight as $k => $v){
        if ( $one <= $v){
            return $arr[$k];
        }
    }
}

function getOneNode(array $arr) {
    $tmp = array();
    foreach ($arr as $value) {
        if (isset($value['weight']) && (int) $value['weight'] > 1) {
            for ($i = 0; $i < (int) $value['weight']; $i++) {
                $tmp[] = $value;
            }
        } else {
            $tmp[] = $value;
        }
    }
    shuffle($tmp);
    return $tmp[0];
}