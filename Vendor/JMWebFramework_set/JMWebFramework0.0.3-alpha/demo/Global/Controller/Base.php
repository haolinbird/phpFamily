<?php
/**
 * 包含了控制器基类.
 *
 * @author Heng Lo <hengl@jumei.com>
 */

/**
 * 控制器基类,负责初始化很多通用的变量,也可以在子类中覆盖然后重新赋值.
 */
class Controller_Base extends JMViewController_WebManagementBase
{
    /**
     * 获取memcache实例
     * @return Memcache
     */
    public function getMemcache($serverGroupName = 'default') {
        $this->memCache = \Memcache\Pool::instance($serverGroupName);
        return $this->memCache;
    }
}
