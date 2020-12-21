<?php
/**
 * 图片白色背景探测工具，用于探测给定的图片是否白色背景.
 *
 * @author wenqiang tao<wenqiangt@jumei.com>
 */

namespace ProductUtils;

/**
 * 图片白色背景探测工具.
 */
class ImageBackgroundColorDetector
{

    /**
     * 获取需要检测的点列表.
     *
     * @param integer $width      图片宽.
     * @param integer $height     图片高.
     * @param integer $matrixSize 采取样点时，将图片拆分的矩阵，5表示将图片拆分为5x5的图片.
     * @param integer $blockSize  在每个样点附近采取的点阵大小，5表示取一个5x5的点阵.
     *
     * @return array 样点列表.
     */
    protected static function getCheckPoints($width, $height, $matrixSize=5, $blockSize = 5)
    {
        $points = array();
        // 第一步，将图片拆分成一个$matrixSize * $matrixSize的矩阵
        $xSpan = intval($width / $matrixSize);
        $ySpan = intval($height / $matrixSize);
        $xPoints = array();
        $yPoints = array();
        for ($i = 0; $i < $matrixSize; $i++) {
            if ($i == $matrixSize - 1) {
                $xPoints[$i] = $width - 1;
                $yPoints[$i] = $height - 1;
            } else {
                $xPoints[$i] = $i * $xSpan;
                $yPoints[$i] = $i * $ySpan;
            }
        }
        // 第二步，取点(只取图片边框附近的点)
        foreach ($xPoints as $x) {
            $rightSize = intval($blockSize / 2);
            $leftSize = $blockSize - $rightSize - 1;
            // 计算x坐标
            if ($x == 0 || $width - $x == 1) {
                if ($x == 0) {
                    $xRange = array(0, $x + $blockSize);
                } else {
                    $xRange = array($x - $blockSize, $x);
                }
                foreach ($yPoints as $y) {
                    // 原点
                    if ($y == 0) {
                        self::fillPointsBlock($points, $xRange, array(0, 0 + $blockSize));
                    } elseif ($height - 1 == $y) {
                        self::fillPointsBlock($points, $xRange, array($y-$blockSize, $y));
                    } else {
                        self::fillPointsBlock($points, $xRange, array($y - $rightSize, $y + $leftSize));
                    }
                }
            } else {
                $xRange = array($x - $rightSize, $x + $leftSize);
                // y轴0点
                self::fillPointsBlock($points, $xRange, array(0, 0 + $blockSize));
                // y轴顶点
                self::fillPointsBlock($points, $xRange, array($height-1-$blockSize, $height-1));
            }
        }
        return $points;
    }


    /**
     * 计算并填充点矩阵.
     *
     * @param array $points 用于接收填充点.
     * @param array $xRange X轴范围.
     * @param array $yRange Y轴范围.
     *
     * @return void
     */
    protected static function fillPointsBlock(&$points, $xRange, $yRange)
    {
        if (count($xRange) != 2 || !isset($xRange[0]) || !isset($xRange[1])) {
            return;
        }
        if (count($yRange) != 2 || !isset($yRange[0]) || !isset($yRange[1])) {
            return;
        }
        if (empty($points) || !is_array($points)) {
            $points = array();
        }
        for ($x = $xRange[0]; $x <= $xRange[1]; $x++) {
            for ($y = $yRange[0]; $y <= $yRange[1]; $y++) {
                $points[$x.'_'.$y] = array('x' => $x, 'y' => $y);
            }
        }
    }

    /**
     * 根据图片路径获取图片资源.
     *
     * @param string $imgPath 图片路径.
     *
     * @return null|resource
     */
    protected static function getImageResource($imgPath)
    {
        $ext = '';
        if (preg_match('/\.(\w+)$/', $imgPath, $match)) {
            $ext = strtolower($match[1]);
        }
        $img = null;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $img = imagecreatefromjpeg($imgPath);
                break;
            case 'png':
                $img = imagecreatefrompng($imgPath);
                break;
            case 'gif':
                $img = imagecreatefromgif($imgPath);
                break;
        }
        return $img;
    }

    /**
     * 计算白色背景的比率（可能性）.
     *
     * @param string  $imgPath    图片路径，支持本地和http地址.
     * @param integer $matrixSize 取样时将图片拆分的矩阵，10表示将图片拆分为10x10的矩阵.
     * @param integer $blockSize  在取样点附近采取的点阵大小，5表示5x5的点阵.
     *
     * @return float|int
     */
    public static function calculateWhiteBackgroundRate($imgPath, $matrixSize = 10, $blockSize = 5)
    {
        // 获取图片资源
        $img = self::getImageResource($imgPath);
        if (null == $img) {
            return 0;
        }
        // 获取图片大小
        $width = imagesx($img);
        $height = imagesy($img);
        // 获取样本识别点阵
        $checkPoints = self::getCheckPoints($width, $height, $matrixSize, $blockSize);
        // rgb颜色最小值
        $rMin = 0xFC;
        $gMin = 0xFC;
        $bMin = 0xFC;
        $whitePoints = array();
        // 开始计算
        foreach ($checkPoints as $i => $point) {
            $rgb = imagecolorat($img,$point['x'],$point['y']);
            $r  = ($rgb >> 16) & 0xFF;
            $g  = ($rgb >> 8) & 0xFF;
            $b  = $rgb & 0xFF;
            if ($r >= $rMin && $g >= $gMin && $b >= $bMin) {
                $whitePoints[] = $point;
            }
        }
        return round(count($whitePoints) / count($checkPoints) * 100, 2);
    }

    /**
     * 判断图片是否是白色背景.
     *
     * @param string $imgPath 图片路径.
     * @param float  $minRate 最小比率，即样本点白色比率达到此值认为是符合条件的白色背景.
     *
     * @return boolean
     */
    public static function isWhiteBackgroundImage($imgPath, $minRate = 94.00)
    {
        $rate = self::calculateWhiteBackgroundRate($imgPath, 10, 5);
        return $rate >= $minRate;
    }

}
