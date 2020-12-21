<?php
require_once('./common.php');

if ($argc < 2) {
    exit("Usage $argv[0] ipcKey" . PHP_EOL);
}

$ipcKey = $argv[1];
$shm = new \SharedMemory\Shm($ipcKey);

$shm->lockAndAttach();

var_dump($shm->getArrVar());

$shm->unlockAndDettach();
