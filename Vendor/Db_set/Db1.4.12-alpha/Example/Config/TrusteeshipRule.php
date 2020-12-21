<?php

namespace Config;

/**
 * Class PromoCode
 *
 * @author Haojie Huang<haojieh@jumei.com>
 */

/**
 * 优惠劵拆表类.
 */
class TrusteeshipRule extends \Db\ShardingRule
{

    public $last3;

    const CFG_PREFIX = 'trusteeship_data_';

    protected static $charMap = array(
        'A' => 0,
        'B' => 1,
        'C' => 2,
        'D' => 3,
        'E' => 4,
        'F' => 5,
        'G' => 6,
        'H' => 7,
        'J' => 8,
        'K' => 9,
        'L' => 0,
        'M' => 1,
        'N' => 2,
        'P' => 3,
        'Q' => 4,
        'R' => 5,
        'S' => 6,
        'T' => 7,
        'U' => 8,
        'V' => 9,
        'W' => 0,
        'X' => 1,
        'Y' => 2,
        'Z' => 3,
        'O' => 4,
        'I' => 5,
    );

    /**
     * 构造方法.
     *
     * @param integer $last3 PromoCard后三位.
     *
     * @throws \Exception invalid PromoCard/Uid.
     */
    public function __construct($last3)
    {
        if (ctype_alnum((string)$last3)) {
            $this->last3 = strtr(substr($last3, -3), static::$charMap);
        } else {
            throw new \Exception("invalid id/  text '$last3'");
        }
    }

    /**
     * 获取sharding后的表名或表的Index.
     *
     * @param string $table Sharding 前的表名.
     *
     * @return string.
     */
    public function getTableName($table = '')
    {
        if ($table) {
            return $table . '_' . ($this->last3 % 128);
        } else {
            return $this->last3 % 128;
        }
    }

    /**
     * 获取sharding后的数据库名.
     *
     * @param string $db 数据库前缀.
     *
     * @return string.
     */
    public function getDbName($db = '')
    {
        return $db . '_' . floor($this->last3 % 128 / 8);
    }

    /**
     * 获取sharding后的服务器配置项名/服务器名.
     *
     * @return string.
     */
    public function getCfgName()
    {
        // 表->数据库 8进制 数据库->服务器/实例 4进制
        return static::CFG_PREFIX . floor($this->last3 % 128 / 8 / 4);
    }

    /**
     * 轮询操作.
     *
     * @param mixed $func 匿名函数.
     *
     * @return type desc
     */
    public static function pollAll($func)
    {
        \Db\ShardingConnection::clearAsyncConnections();
        for ($i = 128; $i < 128 * 2; $i++ ) {
            $rule = new static($i);
            $func($rule);
        }
        return \Db\ShardingConnection::asyncFetchAll();
    }

    /**
     * 把同一个规则的数据分到同一个组里.
     *
     * @param array  $data 要分组的数据.
     * @param string $key  按key进行分组.
     *
     * @return array
     */
    public static function splitGroup(array $data, $key = '')
    {
        $groupData = array();
        if ($key) {
            foreach ($data as $cData) {
                $instance = new static($cData[$key]);
                $index = $instance->getTableName();
                $groupData[$index][] = $cData;
            }
        } else {
            foreach ($data as $cData) {
                $instance = new static($cData);
                $index = $instance->getTableName();
                $groupData[$index][] = $cData;
            }
        }
        return $groupData;
    }
    
    /**
     * 把同一个规则的数据分到同一个组里面, 用于事务.
     * 
     * @param array  $data 要分组的数据.
     * @param string $key  按key进行分组.
     *
     * @return array
     */
    public static function splitTransGroup(array $data, $key = '')
    {
        $groupData = array();
        if ($key) {
            foreach ($data as $cData) {
                $instance = new static($cData[$key]);
                $groupData[$instance->getTableName()][$instance->getTableName()][] = $cData;
            }
        } else {
            foreach ($data as $cData) {
                $instance = new static($cData);
                $groupData[$instance->getTableName()][$instance->getTableName()][] = $cData;
            }
        }
        return $groupData;
    }

}
