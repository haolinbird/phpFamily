<?php
/**
 * 工具包.
 *
 * @author quans<quans@jumei.com>
 */

namespace ProductUtils;

/**
 * 工具包,工具.
 */
class Util
{

    /**
     * 唯一ID分隔符.
     */
    const EXPLODE_KEY_FOR_UNIQUE_ID = '_';

    /**
     * Operation code.
     */
    const SUCCESS_CODE = 200;
    const ERROR_CODE = 500;


    /**
     * 生成产品唯一key.
     *
     * @param integer $id       Product_id/mall_id,hash_id.
     * @param string  $type     类型.
     * @param array   $typesMap 类型定义值.
     *
     * @return string
     */
    public static function getUniqueProductId($id, $type, $typesMap)
    {
        $key = '';
        foreach ($typesMap as $kType => $vCate) {
            if (in_array($type, $vCate)) {
                $key = str_replace(self::EXPLODE_KEY_FOR_UNIQUE_ID, "", $kType);
                break;
            }
        }
        return $key . '_' . $id;
    }

    /**
     * 通过商品唯一ID解析出商品类型和商品ID.
     *
     * @param string $uniqid 商品唯一ID.
     *
     * @return array
     */
    public static function getGoodsTypeFromUniqueId($uniqid)
    {
        $unique = explode(self::EXPLODE_KEY_FOR_UNIQUE_ID, $uniqid);

        return array(
            'type' => $unique[0],
            'id' => $unique[1]
        );
    }

    /**
     * 通过商品唯一ID解析出商品类型和商品ID.
     *
     * @param string $uniqid 商品唯一ID.
     *
     * @return string
     */
    public static function getJsonGoodsTypeFromUniqueId($uniqid)
    {

        $unique = self::getGoodsTypeFromUniqueId($uniqid);
        return json_encode($unique);
    }

    /**
     * 获取json形式正确信息.
     *
     * @param string $tip  提示信息.
     * @param array  $data 附加信息.
     *
     * @return string
     */
    public static function getJsonSuccessInfo($tip = 'success', $data = array())
    {
        return json_encode(self::getArraySuccessInfo($tip, $data));
    }

    /**
     * 获取json形式错误信息.
     *
     * @param string $tip  提示信息.
     * @param array  $data 附加信息.
     *
     * @return string
     */
    public static function getJsonErorrInfo($tip = 'failed', $data = array())
    {
        return json_encode(self::getArrayErrorInfo($tip, $data));
    }

    /**
     * 获取数组形式成功信息.
     *
     * @param string $tip  提示信息.
     * @param array  $data 附加信息.
     *
     * @return array
     */
    public static function getArraySuccessInfo($tip = 'success', $data = array())
    {
        return array(
            'code' => self::SUCCESS_CODE,
            'message' => $tip,
            'data' => $data
        );
    }

    /**
     * 获取数组形式错误信息.
     *
     * @param string $tip  提示信息.
     * @param array  $data 附加信息.
     *
     * @return array
     */
    public static function getArrayErrorInfo($tip = 'failed', $data = array())
    {
        return array(
            'code' => self::ERROR_CODE,
            'message' => $tip,
            'data' => $data
        );
    }

    /**
     * Check code.
     *
     * @param integer $code Code.
     *
     * @return boolean
     */
    public static function isSuccess($code)
    {
        return $code === self::SUCCESS_CODE;
    }

    /**
     * Check string.
     *
     * @param string  $string String.
     * @param string  $name   Var name.
     * @param boolean $empty  Whether allowed empty, default not allowed.
     *
     * @return array
     */
    public static function isString($string, $name = '', $empty = false)
    {
        if (!$empty && empty($string)) {
            return self::getArrayErrorInfo("Given var $name is not allow empty!");
        }

        if (!is_string($string)) {
            return self::getArrayErrorInfo("Given var $name is not a string!");
        }

        return self::getArraySuccessInfo();
    }

    /**
     * Check int.
     *
     * @param integer $int  Integer var.
     * @param string  $name Var name.
     *
     * @return array
     */
    public static function isInt($int, $name = ''){

        if (!is_integer($int)) {
            return self::getArrayErrorInfo("Given var $name is not a integer!");
        }

        return self::getArrayErrorInfo();
    }

    /**
     * Check string length.
     *
     * @param string  $string String.
     * @param integer $len    Matching length.
     * @param string  $name   Var name.
     * @param string  $type   Type.
     *
     * @return array
     */
    public static function checkStringLen($string, $len, $name = '', $type = 'gte')
    {
        $realLen = strlen($string);
        if ($type === 'gte') {
            if ($realLen < $len) {
                return self::getArrayErrorInfo("Given string $name is shorter than $len!");
            }
        } elseif ($type === 'lte') {
            if ($realLen > $len) {
                return self::getArrayErrorInfo("Given string $name is longer than $len!");
            }
        } else {
            return self::getArrayErrorInfo("type $type is not detected!");
        }

        return self::getArraySuccessInfo();
    }

    /**
     * 获取产品图片的基本路径.
     *
     * @param integer $productId ProductId.
     *
     * @return string
     */
    private static function getProductImageBaseUrl($productId) {
        $path_array = str_split(substr('000000000' . $productId, -9), 3); // 假设总数不超过 10 亿.
        $path_array = array_slice($path_array, 0, 2);
        $config = \Config\Config::$productImageConfig;
        $url = $config['prefix'] . ($productId % $config['count']) . $config['suffix'].join('/', $path_array) . '/' . $productId;
        return $url;
    }

    /**
     * 指定尺寸的产品图片.
     *
     * @param integer $productId 产品 ID.
     * @param integer $width     图片尺寸, 可选参数: 960(400), 400, 350, 320, 200, 160, 100, 60.
     *
     * @return string
     */
    public static function getProductImageUrlBySize($productId, $width)
    {
        $height = ($width == 960) ? 400 : $width;
        return sprintf('%s_std/%d_%d_%d.jpg', self::getProductImageBaseUrl($productId), $productId, $width, $height);
    }


    /**
     * 根据sku和宽度返回图片地址.
     *
     * @param        $sku_no Sku_no.
     * @param string $width Width.
     * @param int    $order Order.
     *
     * @return array
     */
    public static function getSkuImageUrlBySize($sku_no, $width, $order = 0)
    {
        if (empty($sku_no))
            return self::getArrayErrorInfo("sku_no 参数不能为空！");

        if(!is_string($sku_no))
            return self::getArrayErrorInfo(" sku_no 只支持字符串类型！");

        if (!isset(\Config\Config::$sku_image))
            return self::getArrayErrorInfo("Config缺少sku_image配置！");

        if (empty($width))
            return self::getArrayErrorInfo("width 参数不能为空！");

        $path = self::getImageBasePath($sku_no);
        $size = "{$width}_{$width}";

        $config = \Config\Config::$sku_image;
        $productSquareImageSize = $config['square']['size'];

        if (!in_array($size, $productSquareImageSize)) {
            return self::getArrayErrorInfo("输入的宽度不在指定的图片尺寸范围内！");
        }

        $directory = $config['square']['directory'];
        // 如果传入了$size,则直接返回 url；否则，返回sku的所有图片.
        $file_name = $order > 0 ? $sku_no . '_' . $order . '_' . $size . '.jpg' : $sku_no . '_' . $size . '.jpg';
        $file_name = 's_' . $file_name;

        return DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $file_name;
    }

    /**
     * 根据spu_id和宽度返回图片地址.
     *
     * @param        $spu_id Spu_id.
     * @param string $width  Width.
     * @param int    $order  Order.
     *
     * @return array
     */
    public static function getSpuImageUrlBySize($spu_id, $width, $order = 0)
    {
        if (empty($spu_id))
            return self::getArrayErrorInfo("spu_id 参数不能为空！");

        // 解决rpctest工具问题，故采用is_numeric.
        if (!is_numeric($spu_id))
            return self::getArrayErrorInfo(" spu_id 只能为数值类型！");

        if (!isset(\Config\Config::$spu_image))
            return self::getArrayErrorInfo("Config缺少 sku_image 配置！");

        if (empty($width))
            return self::getArrayErrorInfo("width 参数不能为空！");

        $path = self::getImageBasePath($spu_id, 'spu_id');
        $size = "{$width}_{$width}";

        $config = \Config\Config::$spu_image;
        $productSquareImageSize = $config['square']['size'];
        $directory = $config['square']['directory'];

        if (!in_array($size, $productSquareImageSize)) {
            return self::getArrayErrorInfo("输入的宽度不在指定的图片尺寸范围内！");
        }

        $file_name = $order > 0 ? $spu_id . '_' . $order . '_' . $size . '.jpg' : $spu_id . '_' . $size . '.jpg';

        return DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $file_name;
    }


    /**
     * 根据skuno/spuno生成图片子目录.
     *
     * @param string $param Param.
     *
     * @return string.
     */
    public static function getImageBasePath($param, $type = 'sku_no')
    {
        $path_array = str_split(substr("000000000" . $param, -9), 3); // 假设总数不超过 10亿。
        $path_array = array_slice($path_array, 0, 2);

        $base_path = join(DIRECTORY_SEPARATOR, $path_array) . DIRECTORY_SEPARATOR . $param;
        switch ($type) {
            case 'sku_no':
                $return = $base_path . '_std';
                break;
            case 'spu_id':
                $return = $base_path . '_spu_normal';
                break;

            default:
                $return = $base_path . '_std';
        }

        return $return;
    }

    /**
     * 视屏红包任务：梯度计算deal级预付配置.
     *
     * @param mixed $dealPrice Deal级价格(discounted_price)。
     *
     * @return array
     */
    public static function getVideoTaskGradientConfig($dealPrice)
    {
        $default = array('price' => $dealPrice,'plan_id' => 0);
        if (!empty(\Config\Config::$videoTaskGradientConfig)) {
            foreach (\Config\Config::$videoTaskGradientConfig as $k => $v) {
                $tmpPrice = ceil($dealPrice);
                if ($tmpPrice > intval($v['gt']) && $tmpPrice <= intval($v['lte'])) {
                    $default['price'] = $v['price'];
                    $default['plan_id'] = $v['plan_id'];
                    break;
                }
            }
        }
        return $default;
    }

    /**
     * 获取凑团红包活动配置.
     *
     * @param string $activity 活动名称.
     *
     * @return array
     */
    protected static function getCouTuanRedEnvelopeActivityConfig($activity = '')
    {
        // 基本配置检查
        if (!class_exists("\\Config\\ProductUtils") ||
            empty(\Config\ProductUtils::$couTuanRedEnvelopeGradientConfig) ||
            !is_array(\Config\ProductUtils::$couTuanRedEnvelopeGradientConfig)) {
            return array();
        }
        $activityConfig = array('rate' => 10);
        // 获取配置
        $config = \Config\ProductUtils::$couTuanRedEnvelopeGradientConfig;
        // 读取活动配置
        if ($activity) {
            if (!empty($config['activities']) && !empty($config['activities'][$activity]) && is_array($config['activities'][$activity])) {
                foreach ($config['activities'][$activity] as $k => $v) {
                    $activityConfig[$k] = $v;
                }
            }
        }
        // 合并default的配置
        if (!empty($config['default']) && is_array($config['default'])) {
            foreach ($config['default'] as $k => $v) {
                if (!key_exists($k, $activityConfig)) {
                    $activityConfig[$k] = $v;
                }
            }
        }
        return $activityConfig;
    }

    /**
     * 凑团红包抵扣，获取最大抵扣比例.
     *
     * @param string $activity 活动名称.
     *
     * @return integer 最大抵扣比例.
     */
    public static function getCouTuanRedEnvelopeMaxDeductRate($activity)
    {
        // 获取配置
        $activityConfig = self::getCouTuanRedEnvelopeActivityConfig($activity);
        if (empty($activityConfig)) {
            return 0;
        }
        return $activityConfig['rate'];
    }

    /**
     * 凑团红包抵扣，获取最大抵扣金额.
     *
     * @param float  $price    Deal级价格(discounted_price).
     * @param string $activity 活动名称.
     *
     * @return float 最大抵扣金额.
     */
    public static function getCouTuanRedEnvelopeMaxDeductAmount($dealPrice, $activity = '')
    {
        // 基本配置检查
        if (filter_var($dealPrice, FILTER_VALIDATE_FLOAT) === false || $dealPrice <= 0) {
            return 0;
        }
        // 获取配置
        $activityConfig = self::getCouTuanRedEnvelopeActivityConfig($activity);
        if (empty($activityConfig)) {
            return 0;
        }
        // 计算金额
        $deductAmount = $dealPrice * $activityConfig['rate'] / 100;
        if (isset($activityConfig['max_amount']) && bccomp($deductAmount, $activityConfig['max_amount'], 2) > 0) {
            $deductAmount = $activityConfig['max_amount'];
        } else if (isset($activityConfig['min_amount']) && bccomp($deductAmount, $activityConfig['min_amount'], 2) < 0) {
            $deductAmount = $activityConfig['min_amount'];
        }
        return round($deductAmount, 2);
    }

    /**
     * 判断指定用户是否满足ab条件.
     *
     * @param integer $userId   用户ID.
     * @param string  $activity 活动名称.
     *
     * @return boolean
     */
    public static function isCouTuanRedEnvelopeAbUser($userId, $activity = '')
    {
        if (filter_var($userId, FILTER_VALIDATE_INT) === false || $userId <= 0) {
            return false;
        }
        $activityConfig = self::getCouTuanRedEnvelopeActivityConfig($activity);
        if (empty($activityConfig) || empty($activityConfig['ab'])) {
            return false;
        }
        $mod = $userId % 100;
        $abValue = $activityConfig['ab'];
        if (is_numeric($abValue) && $mod < $abValue) {
            return true;
        }
        if (is_array($abValue)) {
            $len = count($abValue);
            // 如果是一维数组，则表示单纯的范围
            if ($len == 2 && $len == count($abValue, 1) && isset($abValue[0]) && isset($abValue[1])) {
                if (intval($abValue[0]) <= $mod && $mod <= intval($abValue[1])) {
                    return true;
                }
                return false;
            }
            // 如果是二维数组，表示多组范围
            foreach ($abValue as $range) {
                if (!is_array($range) || empty($range) || !isset($range[0]) || !isset($range[1])) {
                    continue;
                }
                if (intval($range[0]) <= $mod && $mod <= intval($range[1])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 设置国家.
     *
     * @param string $state 语言.
     */
    public static function setState($state)
    {
        global $context;

        $context['state'] = $state;
    }

    /**
     * 获取国家.
     *
     * @param string $default 默认值.
     *
     * @return string
     */
    public static function getState($default = 'china')
    {
        global $context;

        if (!isset($context['state'])) {
            return $default;
        }

        return strtolower($context['state']);
    }

}
