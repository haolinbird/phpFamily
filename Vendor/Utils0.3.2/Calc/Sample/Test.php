<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Singleton.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Percent.php';

$items = array();
for ($i = 0; $i < 5; $i++) {
    $items[] = array('price' => rand(1, 100), 'quantity' => rand(1, 5));
}
$amount = 100;
print_r(\Utils\Calc\Percent::instance()->amountPercent($amount, $items));
print_r(\Utils\Calc\Percent::instance()->average($amount, $items));
