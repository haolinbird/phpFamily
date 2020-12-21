<?php
namespace Thrift;
define('THRIFT_CLIENT_DIR', realpath(__dir__) . '/');

require_once THRIFT_CLIENT_DIR . 'Lib/KLogger/KLogger.php';

if(!defined('IN_THRIFT_WORKER')) {
    require_once THRIFT_CLIENT_DIR . 'Lib/Thrift/ClassLoader/ThriftClassLoader.php';
    require_once THRIFT_CLIENT_DIR . 'Lib/Thrift/Context.php';
    require_once THRIFT_CLIENT_DIR . 'Lib/Thrift/ContextSerialize.php';
    require_once THRIFT_CLIENT_DIR . 'Lib/Thrift/ThriftInstance.php';
    
    // 加载Thrift相关类
    $loader = new \Thrift\ClassLoader\ThriftClassLoader();
    $loader->registerNamespace('Thrift', THRIFT_CLIENT_DIR. 'Lib');
    $loader->register(true);
} else {
    require_once THRIFT_CLIENT_DIR . 'Lib/Thrift/ThriftInstance.php';
}

require_once __DIR__.'/../MNLogger/Base.php';
require_once __DIR__.'/../MNLogger/TraceLogger.php';
require_once __DIR__.'/../MNLogger/MNLogger.php';
require_once __DIR__.'/../PHPClient/JMTextRpcClient.php';


class Client {
    /**
     * 客户端实例
     * @var array
     */
    private static $instance = array();

    /**
     * 配置
     * @var array
     */
    private static $config = null;

    

    public static function config(array $config=array()) {
        if(!empty($config)) {
            self::$config = $config;
        }
        
        // 如果配置为空，则尝试自动加载
        if(empty(self::$config) && class_exists("\\Config\\Thrift")) {
            self::$config = (array) new \Config\Thrift;
        }
        
        return self::$config;
    }
    
    /**
     * 获取实例
     * @param string $serviceName 服务名称
     * @param bool $newOne 是否强制获取一个新的实例
     * @return object/Exception
     */
    public static function instance($serviceName, $newOne = false, $rawThrift = false)
    {
        if(empty(self::$config)) {
            self::config();
        }

        // 判断$serviceName 是否是 项目.service 格式,例如Cart.User
        $project_and_service = explode('.', $serviceName);
        if(count($project_and_service) > 1) {
            $project = array_shift($project_and_service);
            $service = implode('\\', $project_and_service);
        } else {
            $project = $service = $serviceName;
        }

        self::initProject($project);

        // jmtext协议
        if(isset(self::$config[$project]['protocol']) && self::$config[$project]['protocol'] == 'text') {
            $config = array(
                'rpc_secret_key' => '769af463a39f077a0340a189e9c1ec28',
                $service => array(
                    'user' => 'Thrift',
                    'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
                    'service' => self::$config[$project]['service'],
                )
            );
            \PHPClient\JMTextRpcClient::config($config);
            return call_user_func(array('RpcClient_'.$service, 'instance'), $config);
        }

        
        if($newOne || $rawThrift) {
            unset(self::$instance[$serviceName]);
        }
        
        // 上一个实例使用的原始协议，当前实例不用原始协议，则删除当前实例，以便重新创建一个实例
        if(!$rawThrift && isset(self::$instance[$serviceName]) && self::$instance[$serviceName]->rawThrift) {
        	unset(self::$instance[$serviceName]);
        }
        
        if(!isset(self::$instance[$serviceName])) {
            $instance = new ThriftInstance($service, $project, $rawThrift);
            $instance->config(self::$config[$project]);
            self::$instance[$serviceName] = $instance;
        }
        
        return self::$instance[$serviceName];
    }

    public static function initProject($project) {
        global $owl_context;
        $owl_context_client = $owl_context;
        if(!empty($owl_context_client)) {
            $owl_context_client['app_name'] = defined('JM_APP_NAME') ? JM_APP_NAME : 'undefined';
        }
        \Thrift\Context::put('owl_context', json_encode($owl_context_client));
    }

    // 向后兼容,保留老接口，啥都不做
    public static function configAlarmPhone($p) { }
    public static function extConfig(array $e){}
}
