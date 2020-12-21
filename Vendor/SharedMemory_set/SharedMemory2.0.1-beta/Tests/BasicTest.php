<?php

require_once ('./common.php');

echo ftok(__FILE__, 'R') . PHP_EOL;
$shm = new \SharedMemory\Shm(ftok(__FILE__, 'R'));
$shm->setSeqKey(15);
$shm->setNoWait(true);

$shm->lockAndAttach();
echo 'lock success' . PHP_EOL;
$var = $shm->putVar(array('hello world'));
var_dump($shm->hasVar());
$var = @$shm->getVar();
$shm->unlockAndDettach();

var_dump($var);

unset($shm);

$ha = new \SharedMemory\HaConfig("222");
var_dump($ha->findOneConfig(array("23.2.11","23,31")));
