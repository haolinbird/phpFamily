<?php
/**
 * 归档工具.
 *
 * @author quans<quans@jumei.com>
 */

namespace ProductUtils;

/**
 * 归档相关操作.
 */
class TableFile
{
    /**
     * JDSR保留数据最长时间,单位秒.
     */
    const ALLOW_HOT_TIME = 7776000;

    /**
     * 根据end_time获取归档表后缀.
     * 
     * @param mixed   $times        归档数据.
     * @param array   $fileRule     归档规则.
     * @param integer $allowHotTime 获取源SR表最低限度时间节点.
     * 
     * @return array
     */
    public static function getSuffixOfJdsrFileByTimes($times, $fileRule, $allowHotTime = self::ALLOW_HOT_TIME)
    {
        $result = array();
        if (empty($times)) {
            return $result;
        }

        $single = false;
        if (filter_var($times, FILTER_VALIDATE_INT) !== false) {
            $time[] = array('end_time' => $times);
            $single = true;
            $times = $time;
        } elseif (!is_array($times)) {
            return $result;
        }

        foreach ($times as $k => $v) {
            $suffix = self::getSuffixByTime($v['end_time'], $fileRule, $allowHotTime);
            if (!isset($result[$suffix])) {
                $result[$suffix] = array();
            }
            $result[$suffix][] = $k;
        }

        if ($single) {
            return $suffix;
        }

        return $result;
    }

    /**
     * 根据时间获取suffix.
     * 
     * @param integer $time         时间.
     * @param array   $fileRule     归档规则.
     * @param integer $allowHotTime 获取源SR表最低限度时间节点.
     * 
     * @return string
     */
    public static function getSuffixByTime($time, $fileRule, $allowHotTime = self::ALLOW_HOT_TIME)
    {
        $result = '';
        if (time() <= $time + $allowHotTime) {
            return $result;
        }
        foreach ($fileRule as $k => $v) {
            if (isset($v['min'])) {
                $min = strtotime($v['min']);
                if ($min > $time) {
                    continue;
                }
            }

            if (isset($v['max'])) {
                $max = strtotime($v['max']);
                if ($max <= $time) {
                    continue;
                }
            }

            $result = $v['suffix'];
        }

        return $result;
    }

    /**
     * 获取所有后缀.
     *
     * @param string $fileRule 归档规则.
     *
     * @return array
     */
    public static function getAllJdsrSuffix($fileRule)
    {
        $suffix = array();
        foreach ($fileRule as $k => $v) {
            $suffix[] = $v['suffix'];
        }

        return $suffix;
    }

}
