<?php
/**
 * curl代理访问配置示例.
 *
 * @author gangw<gangw@jumei.com>
 */

namespace Config;

/**
 * JmProxy.
 */
class JmProxy
{
    /**
     * 是否启用本地代理.
     *
     * @var boolean
     */
    public static $enableJmProxy = "#{shuabao-service.JmProxy.enableJmProxy}";

    /**
     * RPC调用服务名称.
     *
     * @var string
     */
    public static $jmProxyServiceName = "#{UserInfo.JmProxy.jmProxyServiceName}";

}
