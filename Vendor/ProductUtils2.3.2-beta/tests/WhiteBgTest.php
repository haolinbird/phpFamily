<?php
require __DIR__ . '/../ImageBackgroundColorDetector.php';

class WhiteBgTest extends PHPUnit_Framework_TestCase
{
    public function testCheckBgColor()
    {
        $images = array(
            'images/1001.jpg' => false,
            'images/1002.jpg' => true,
            'images/1003.jpg' => true,
            'images/1004.jpg' => false,
            'images/1005.jpg' => false
        );
        echo PHP_EOL;
        foreach ($images as $imgPath => $expected) {
            $rate = \ProductUtils\ImageBackgroundColorDetector::calculateWhiteBackgroundRate($imgPath, 10, 5);
            echo $imgPath, ' => ', $rate,'%',PHP_EOL;
            $rs = $rate >= 94.00;
            $this->assertEquals($rs, $expected);
        }
    }
}
?>
