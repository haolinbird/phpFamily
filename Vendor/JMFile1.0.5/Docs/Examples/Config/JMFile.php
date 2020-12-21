<?php
namespace Config;

class JMFile{
    
    /**
     * 
     * 用户名密码由于是明文所以需要自己传，path 有可能会有多个地址的访问权限，所以需要用户自己传入
     * @var unknown
     */
	public $default = array(		
			'user'=>'test123',
			'password'=>'123456',
			'baseUrl' => 'http://192.168.20.69:8000',
			'exportUrl' => 'http://192.168.20.69:9000'
	);
    
    
    
}
