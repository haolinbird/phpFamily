<?php
/** 
 * AbTest类文件
 *  
 * @author  linh
 * @version 1.0 版本号
 */ 

namespace UcUtils\AbTest;

/** 
* AbTest类 
* 
* @author linh<linh@jumei.com> 
* @since  1.0 
*/ 
class AbTest
{
    // ABTEST单例对象存储器
    static $instances = array();

    // ABTEST方案配置
    private $config = array();

    // 调试开关
    const DEBUG = false;

    /**
     * 获取单例对象
     * 
     * @param string $planName ABTEST方案名
     * 
     * @return static
     */
    public static function inst($planName = 'default')
    {
        if(!isset(static::$instances[$planName]))
        {
            static::$instances[$planName] = new static($planName);
        }

        return static::$instances[$planName];
    }

    /** 
    * 构造函数,初始化方案配置
    * 
    * @param string $planName ABTEST方案代号
    *
    * @return void
    */
    protected function __construct($planName)
    {
        // 获取ABTEST方案配置
        $allConfig = $this->config;
        // 如果配置为空,则尝试从配置文件读取配置
        if (empty($allConfig)) {
            if (class_exists('\Config\UcAbTest')) {
                $allConfig = (array) new \Config\UcAbTest;
            }
        }

        // 如果没有读取到配置文件或者ABTEST方案配置不存在,则进入异常处理
        if (empty($allConfig) || !isset($allConfig[$planName])) {
            $this->outPutError('Missing config File');
        } else {
            // 初始化配置
            $this->config = $allConfig[$planName];
        }
    }

    /** 
    * 异常处理 
    * 
    * @param string $errMsg 异常信息
    *
    * @return boolean
    * @throws \Exception 输出错误调试信息.
    */
    private function outPutError($errMsg)
    {
        // 如果打开了调试开关,则抛出异常,方便测试环境定位问题
        if (self::DEBUG) {
            throw new \Exception('AbTest: '.$errMsg);
        } else {
            return false;
        }
    }

    /** 
    * 获取标识位是否命中ABTEST
    * 命中新方案规则返回true,否则返回false
    * 
    * @param mixed $sign ABTEST标识位
    *
    * @version 1.0.0 2018-05-14更新
    * @since 1.0 
     *
    * @return boolean
    */
    public function getPlanAb($sign)
    {
        // 获取方案配置
        $testConfig = $this->config;
        // 如果方案配置为空,则返回false
        if (empty($testConfig)) {
            return $this->outPutError('Missing configurations');
        }

        // 检查方案配置元素是否完整
        if (!isset($testConfig['enable']) || !isset($testConfig['strategy']) || !isset($testConfig['ruler']) || !isset($testConfig['start_time']) || !isset($testConfig['end_time'])) {
            return $this->outPutError('Missing config params');
        }

        // 参数格式校验
        if (isset($testConfig['blacklist']) && !is_array($testConfig['blacklist'])) {
            return $this->outPutError('blacklist params format must be array');
        }

        if (isset($testConfig['whitelist']) && !is_array($testConfig['whitelist'])) {
            return $this->outPutError('whitelist params format must be array');
        }

        // 检查方案是否开启
        if (!$testConfig['enable']) {
            return $this->outPutError('plan is close');
        }

        // 检查方案时间
        $time = time();
        $startTime = strtotime($testConfig['start_time']);
        $endTime = strtotime($testConfig['end_time']);
        // 检查配置时间格式
        if ($startTime == false || $endTime == false) {
            return $this->outPutError('start_time or end_time format error');
        }
        // 如果不在方案生效时间范围内,返回错误
        if ($time < $startTime || $time > $endTime) {
            return $this->outPutError('not in the effective time range');
        }

        // 检查不同策略下的,ruler数据格式要求
        switch ($testConfig['strategy']) {
            // 策略1,取标识位倒数第二位数字匹配ruler里面配置的数组,如果属于ruler数组里面的元素,则表示命中
            case 1:
            {
                // 检查参数格式
                if (!is_array($testConfig['ruler'])) {
                    return $this->outPutError('strategy is 1 and ruler not array');
                }
                foreach ($testConfig['ruler'] as $key => $value) {
                    if (!is_int($value) || $value > 9 || $value < 0) {
                        return $this->outPutError('strategy is 1 and ruler element is not int, between 0-9');
                    }
                }

                // 将标识位转化为整数
                $sign = intval($sign);
                // 判断黑名单
                if (!empty($testConfig['blacklist']) && in_array($sign, $testConfig['blacklist'])) {
                    return false;
                }

                // 判断白名单
                if (!empty($testConfig['whitelist'])  && in_array($sign, $testConfig['whitelist'])) {
                    return true;
                }

                // 如果标识位小于2位,则表示满足命中方案的条件
                if (strlen($sign) < 2) {
                    return $this->outPutError('sign is less than two numbers');
                }
                // 获取标识位倒数第二位数字
                $lastSecondNumber = substr($sign, -2, 1);
                // 策略规则判断
                if (in_array($lastSecondNumber, $testConfig['ruler'])) {
                    return true;
                } else {
                    return false;
                }
            }
            break;
            default:
            {
                // 若为不识别方案策略标识,则返回错误
                return $this->outPutError('unknown strategy');
            }
            break;
        }
    }

}
