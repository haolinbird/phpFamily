<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

namespace Config;
/**
 * if you are to transfer objects you have to set protocol to msgpack(if you have that pecl ext installed), or php. Because the default JSON deserializer cannot restore a php object.<br />
 * In general, msgpack gain the performance, json is moderate but cannot restore a serialized object, php serialization has the lowest performace. Both json and php serializer are commonly supported.
 * @var array
 */
class EventClient{
    const DEBUG = TRUE;
//    public $default = array('protocol'=>'php',
//                                   'user'=>'jumei_order',
//                                   'secret_key'=>'mypassword',
//                                   'url'=>'http://rpc.event.jumeicd.com/Rpc.php'
//    );
    public $default = array('protocol'=>'php',
        'user'=>'test',
        'secret_key'=>'test_password',
        'url'=>'http://rpc.event.jumeicd.com/Rpc.php'
    );

    public $psProto = array(
        // rpc server secret key.
        'rpc_secret_key' => '769af463a39f077a0340a189e9c1ec28',
        'user'=>'test',
        // message/event center subscriber secret key.
        'secret_key'=>'test_password',
        'hosts' => array (
  0 => '172.20.4.200:2203',
)
    );
}
//