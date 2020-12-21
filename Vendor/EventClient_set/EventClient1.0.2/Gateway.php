<?php
namespace EventClient;

class Gateway
{
    /**
     * 设置客户端所需的配置。
     *
     * @param array $configs
     */
    public static function config(array $configs)
    {
        RpcClient::config($configs);
    }

    /**
     * Send a event/message to Jumei EventCenter.
     *
     * @param string $eventClass Event/Message Class name that defined in {@link http://meman.int.jumei.com/ EventCenter}
     * @param mixed $content event/message content which can be any PHP data type that can be serialized.
     * @param array $options available options are "delay", "priority".  "delay" is in senconds, "priority" are integers which are from 0 (most urgent) to 0xFFFFFFFF (least urgent).
     * @param string $endpoint EventServer configuration name.
     *
     * @return Boolean
     */
    public static function send($eventClass, $content, $options = array('priority'=>null, 'delay'=>null), $endpoint = 'default')
    {
        return RpcClient::instance($endpoint)->setClass('Broadcast')->Send($eventClass, $content, $options['priority'], $options['delay']);
    }

}