<?php
/**
 * Memcache 配置
 */
namespace Config;

class Memcache{
    public $default = array(
        array('host'=>'127.0.0.1', 'port'=>11211),
        array('host'=>'192.168.25.9', 'port'=>6660, 'type'=>'backup')
    );
}
