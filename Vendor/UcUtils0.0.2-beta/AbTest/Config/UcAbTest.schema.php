<?php
/**
 * ABTEST配置文件
 *
 * @author Hao Lin <linh@jumei.com>
 * @date 2018-05-11 15:28:30
 */

namespace Config;

/**
 * Class UcAbTest .
 * 
    AbTest方案配置示例
    // demo中每项均是必填项
    public $demoTest = array(
                          'enable'     => 1,                     // 测试开关, 1-开启, 0-关闭
                          'start_time' => '2018-05-13 00:00:00', // 测试开始时间,格式要求 'yyyy-MM-dd hh:ii:ss'
                          'end_time'   => '2018-12-31 23:59:59', // 测试开始时间,格式要求 'yyyy-MM-dd hh:ii:ss'
                          'blacklist'  => array(),               // 方案黑名单, 优先判断黑名单
                          'whitelist'  => array(),               // 方案白名单
                          'strategy'   => 1,                     // 方案策略 1 表示 - 按照标识位倒数第二位数字匹配ruler数组里的元素,若匹配则表示命中
                          'ruler'      => array(7,9)             // 具体规则 当策略为1时要求是数组,元素要求为0-9的整数,可配置多个元素
     );
 */
class UcAbTest
{

    public $jumeiPoint = "#{UserCenter.Config.ABTest.jumeiPoint}";

}
