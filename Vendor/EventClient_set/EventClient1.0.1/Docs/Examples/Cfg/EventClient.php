<?php
namespace Config;
/**
 * if you are to transfer objects you have to set protocol to msgpack(if you have that pecl ext installed), or php. Because the default JSON deserializer cannot restore a php object.<br />
 * In general, msgpack gain the performance, json is moderate but cannot restore a serialized object, php serialization has the lowest performace. Both json and php serializer are commonly supported.
 * @var array
 */
class EventClient{
    const DEBUG = TRUE;
    public $default = array('protocol'=>'php',
                                   'user'=>'jumei_order',
                                   'secret_key'=>'mypassword',
                                   'url'=>'http://rpc.event.jumeicd.com/Rpc.php'
    );
}