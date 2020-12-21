<?php
require __DIR__ . '/../SaleType.php';

class SaleTypeTest extends PHPUnit_Framework_TestCase
{
    public function testMap()
    {
        $mask = \ProductUtils\SaleType::ST_POP
            | \ProductUtils\SaleType::ST_GLOBAL
            | \ProductUtils\SaleType::ST_MALL;

        $r = \ProductUtils\SaleType::map($mask);
        print_r($r);
    }
}
?>
