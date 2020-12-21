<?php
/**
 * 凑团以及普通售卖红包规则.
 *
 * @author wenqiang tao<wenqiangt@jumei.com>
 */

namespace ProductUtils;

/**
 * 凑团以及普通售卖红包规则.
 */
class CouTuanRedEnvelopeUtil
{

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

    //////////  以上的接口，兼容以前的逻辑  ////////
    ############## 华丽分分割线 ##################
    //////////  以下的接口，提供新的业务支持  ///////

    /**
     * 获取凑团红包活动配置.
     *
     * @return array
     */
    protected static function getCouTuanActivitiesConfigs()
    {
        // 基本配置检查
        if (!class_exists("\\Config\\ProductUtils") ||
            empty(\Config\ProductUtils::$couTuanRedEnvelopeGradientConfig) ||
            !is_array(\Config\ProductUtils::$couTuanRedEnvelopeGradientConfig)) {
            return array();
        }
        $activities = array();
        // 获取配置
        $config = \Config\ProductUtils::$couTuanRedEnvelopeGradientConfig;
        // 读取活动配置
        if (!empty($config['activities']) && is_array($config['activities'])) {
            foreach ($config['activities'] as $activityId => $activityCfgs) {
                if (isset($activityCfgs['is_enabled']) && !$activityCfgs['is_enabled']) {
                    continue;
                }
                // 新的逻辑，没有ab的直接忽略
                if (!isset($activityCfgs['ab'])) {
                    continue;
                }
                if (!isset($activityCfgs['priority'])) {
                    $activityCfgs['priority'] = 0;
                }
                $activities[$activityId] = $activityCfgs;
            }
        }
        // 合并default的配置
        if (!empty($config['default']) && is_array($config['default'])) {
            foreach ($activities as $activityId => &$activityCfgs) {
                foreach ($config['default'] as $k => $v) {
                    if (!key_exists($k, $activityCfgs)) {
                        $activityCfgs[$k] = $v;
                    }
                }
                // 开团抵扣比例
                if (!isset($activityCfgs['open_rate']) && isset($activityCfgs['rate'])) {
                    $activityCfgs['open_rate'] = $activityCfgs['rate'];
                }
                // 参团抵扣比例
                if (!isset($activityCfgs['join_rate'])) {
                    $activityCfgs['join_rate'] = 0;
                }
            }
        }
        return $activities;
    }

    /**
     * 判断给定的用户是否命中AB.
     *
     * @param integer $userId  用户ID.
     * @param mixed   $abValue AB值.
     *
     * @return boolean
     */
    protected static function hitAb($userId, $abValue)
    {
        if (empty($abValue)) {
            return false;
        }
        $mod = $userId % 100;
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
     * 获取生效的活动配置.
     *
     * @param integer $openUserId 开团用户ID.
     *
     * @return array
     */
    protected static function getActiveActivityConfig($openUserId)
    {
        $config = array();
        // 读取全部活动配置
        $activities = self::getCouTuanActivitiesConfigs();
        if (empty($activities)) {
            return $config;
        }
        // 判断用户是否命中AB
        foreach ($activities as $activityId => $activityConfig) {
            // 如果没有命中AB，则跳过
            if (empty($activityConfig['ab']) || !self::hitAb($openUserId, $activityConfig['ab'])) {
                continue;
            }
            if (empty($config)) {
                $config = $activityConfig;
                $config['flag'] = $activityId;
                if (!isset($config['priority'])) {
                    $config['priority'] = 0;
                }
            } else {
                if ($activityConfig['priority'] > $config['priority']) {
                    $config = $activityConfig;
                    $config['flag'] = $activityId;
                }
            }
        }
        return $config;
    }

    /**
     * 获取抵扣金额.
     *
     * @param float $originPrice 原金额.
     * @param float $rate        抵扣比率.
     * @param float $minAmount   最小金额.
     * @param float $maxAmount   最大金额.
     *
     * @return float 实际最大抵扣金额.
     */
    protected static function getDeductAmount($originPrice, $rate, $minAmount = 0, $maxAmount = 0)
    {
        // 计算金额
        $deductAmount = $originPrice * $rate / 100;
        if ($maxAmount > 0 && bccomp($deductAmount, $maxAmount, 2) > 0) {
            $deductAmount = $maxAmount;
        } else if ($minAmount > 0 && bccomp($deductAmount, $minAmount, 2) < 0) {
            $deductAmount = $minAmount;
        }
        return round($deductAmount, 2);
    }

    /**
     * 获取凑团规则.
     *
     * @param integer $openUserId 开团用户ID.
     * @param float   $dealPrice  凑团原价.
     *
     * @return array
     */
    public static function getCouTuanRule($openUserId, $dealPrice)
    {
        $retVal = array(
            'hit_ab' => false,      // 是否命中AB
            'open_rate' => 0,       // 开团最大抵扣比率
            'join_rate' => 0,       // 参团最大抵扣比率
            'open_deduct' => 0,     // 开团最大抵扣金额
            'join_deduct' => 0,     // 参团最大抵扣金额
            'flag' => ''            // 标识，配置的key
        );
        // 读取有效活动配置
        $activityConfig = self::getActiveActivityConfig($openUserId);
        if (empty($activityConfig)) {
            return $retVal;
        }
        $retVal['flag'] = isset($activityConfig['flag']) ? $activityConfig['flag'] : '';
        $retVal['hit_ab'] = true;
        $retVal['open_rate'] = $activityConfig['open_rate'];
        $retVal['join_rate'] = $activityConfig['join_rate'];
        $minAmount = isset($activityConfig['min_amount']) ? $activityConfig['min_amount'] : 0;
        $maxAmount = isset($activityConfig['max_amount']) ? $activityConfig['max_amount'] : 0;
        if ($dealPrice) {
            $retVal['open_deduct'] = self::getDeductAmount($dealPrice, $activityConfig['open_rate'], $minAmount, $maxAmount);
            $retVal['join_deduct'] = self::getDeductAmount($dealPrice, $activityConfig['join_rate'], $minAmount, $maxAmount);
        }
        return $retVal;
    }

    ////////////////////////////////////////////
    ############## 华丽分分割线 ##################
    ///////  以下的接口，用于普通商品业务支持  ///////

    /**
     * 获取普通售卖配置.
     *
     * @return array
     */
    protected static function getNormalSaleConfigs()
    {
        // 基本配置检查
        if (!class_exists("\\Config\\ProductUtils") ||
            empty(\Config\ProductUtils::$couTuanRedEnvelopeGradientConfig) ||
            !is_array(\Config\ProductUtils::$couTuanRedEnvelopeGradientConfig)) {
            return array();
        }
        $normalRules = array();
        // 获取配置
        $config = \Config\ProductUtils::$couTuanRedEnvelopeGradientConfig;
        // 读取活动配置
        if (!empty($config['normal']) && is_array($config['normal'])) {
            foreach ($config['normal'] as $flag => $normalCfgs) {
                if (isset($normalCfgs['is_enabled']) && !$normalCfgs['is_enabled']) {
                    continue;
                }
                // 新的逻辑，没有ab的直接忽略
                if (!isset($normalCfgs['ab'])) {
                    continue;
                }
                if (!isset($normalCfgs['priority'])) {
                    $normalCfgs['priority'] = 0;
                }
                $normalRules[$flag] = $normalCfgs;
            }
        }
        // 合并default的配置
        if (!empty($config['default']) && is_array($config['default'])) {
            foreach ($normalRules as $flag => &$normalCfgs) {
                foreach ($config['default'] as $k => $v) {
                    if (!key_exists($k, $normalCfgs)) {
                        $normalCfgs[$k] = $v;
                    }
                }
            }
        }
        return $normalRules;
    }

    /**
     * 获取生效的售卖配置.
     *
     * @param integer $userId 用户ID.
     *
     * @return array
     */
    protected static function getActiveNormalSaleConfig($userId)
    {
        $config = array();
        // 读取全部活动配置
        $normalRules = self::getNormalSaleConfigs();
        if (empty($normalRules)) {
            return $config;
        }
        // 判断用户是否命中AB
        foreach ($normalRules as $flag => $saleConfig) {
            // 如果没有命中AB，则跳过
            if (empty($saleConfig['ab']) || !self::hitAb($userId, $saleConfig['ab'])) {
                continue;
            }
            if (empty($config)) {
                $config = $saleConfig;
                $config['flag'] = $flag;
                if (!isset($config['priority'])) {
                    $config['priority'] = 0;
                }
            } else {
                if ($saleConfig['priority'] > $config['priority']) {
                    $config = $saleConfig;
                    $config['flag'] = $flag;
                }
            }
        }
        return $config;
    }

    /**
     * 获取普通售卖规则.
     *
     * @param integer $userId    用户ID.
     * @param float   $dealPrice 凑团原价.
     *
     * @return array
     */
    public static function getNormalSaleRule($userId, $dealPrice)
    {
        $retVal = array(
            'hit_ab' => false,      // 是否命中AB
            'rate'   => 0,          // 最大抵扣比率
            'deduct' => 0,          // 最大抵扣金额
            'flag'   => ''          // 标识，配置的key
        );
        // 读取有效活动配置
        $saleConfig = self::getActiveNormalSaleConfig($userId);
        if (empty($saleConfig)) {
            return $retVal;
        }
        $retVal['flag'] = isset($saleConfig['flag']) ? $saleConfig['flag'] : '';
        $retVal['hit_ab'] = true;
        $retVal['rate'] = $saleConfig['rate'];
        $minAmount = isset($saleConfig['min_amount']) ? $saleConfig['min_amount'] : 0;
        $maxAmount = isset($saleConfig['max_amount']) ? $saleConfig['max_amount'] : 0;
        if ($dealPrice) {
            $retVal['deduct'] = self::getDeductAmount($dealPrice, $saleConfig['rate'], $minAmount, $maxAmount);
        }
        return $retVal;
    }


}
