<?php 

class JumeiWorker extends RpcWorker
{
    public static $appName = 'JumeiWorker';

    public function onServe()
    {
        // 业务引导程序bootstrap初始化（没有则忽略）
        $bootstrap = PHPServerConfig::get('workers.'.$this->serviceName.'.bootstrap');
        if(is_file($bootstrap))
        {
            require_once $bootstrap;
        }

        $app_name = PHPServerConfig::get('workers.'.$this->serviceName.'.framework.app_name');
        if($app_name)
        {
            self::$appName = $app_name;
        }

        try{
            if (class_exists('MNLogger\TraceLogger')) {
                self::$rpcTraceLogger = MNLogger\TraceLogger::instance('trace');
            }
        }
        catch(Exception $e) {
            ServerLog::add("worker [{$this->workerName}] get MNLogger instance failed: " . $e->getMessage());
        }
        
        // 初始化统计上报地址
        $report_address = PHPServerConfig::get('workers.'.$this->serviceName.'.report_address');
        if($report_address)
        {
            StatisticClient::config(array('report_address'=>$report_address));
        }
        // 没有配置则使用本地StatisticWorker中的配置
        else
        {
            if($config = PHPServerConfig::get('workers.StatisticWorker'))
            {
                if(!isset($config['ip']))
                {
                    $config['ip'] = '127.0.0.1';
                }
                StatisticClient::config(array('report_address'=>'udp://'.$config['ip'].':'.$config['port']));
            }
        }

        if(!class_exists('\Bootstrap\Autoloader')){
            require_once SERVER_BASE. 'Vendor/Bootstrap/Autoloader.php';
        }
    }
    
    protected function process($data)
    {
        self::$currentModule = $data['class'];
        self::$currentInterface = $data['method'];
        self::$currentClientIp = $this->getRemoteIp();
        self::$currentRequestBuffer = json_encode($data);
        
        $user = $data['user'];
        if($user == 'Thrift')
        {
        	if(isset($data['owl_context']['app_name']))
        	{
        		$user = $data['owl_context']['app_name'];
        	}
        	else
        	{
        		$user ='Thrift_Text';
        	}
        }
        self::$currentClientUser = $user;
        
        self::$rpcTraceLogger && self::$rpcTraceLogger->RPC_SR($data['class'], $data['method'], $data['params']);
        
        if (!class_exists('Core\Lib\RpcServer')) {
            $frameworkBootstrap = PHPServerConfig::get('workers.'.$this->serviceName.'.framework.path') .
                '/Serverroot/Autoload.php';
            require_once $frameworkBootstrap;
        }

        StatisticHelper::tick();
        try 
       {
            $this->checkQuota($data['class'], $data['method'], $data['user']);
        }
        catch (\Exception $ex)
        {
        	$ctx = array(
        	   'exception' => 
        	    array(
                    'class' => get_class($ex),
                    'message' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'traceAsString' => $ex->getTraceAsString(),
                )
            );
        }
        
        if(!isset($ctx))
        {
            $rpcServer = new Core\Lib\RpcServer;
            $ctx = $rpcServer->run($data);
        }
        
        $this->send($ctx);
        
        StatisticHelper::report($data, $ctx, $this->getRemoteIp());
        
        $ctx_string = json_encode($ctx);
        if(is_array($ctx) && isset($ctx['exception']))
        {
            self::$rpcTraceLogger && self::$rpcTraceLogger->RPC_SS('EXCEPTION', strlen($ctx_string), $ctx_string);
        }
        elseif(is_array($ctx) && (isset($ctx['error']) || isset($ctx['errors'])))
        {
            self::$rpcTraceLogger && self::$rpcTraceLogger->RPC_SS('EXCEPTION', strlen($ctx_string), $ctx_string);
        }
        else 
        {
            self::$rpcTraceLogger && self::$rpcTraceLogger->RPC_SS('SUCCESS', strlen($ctx_string));
        }
    }

    public function handleFatalErrors() {
        if ($errors = error_get_last()) {
            $this->send(array(
                'exception' => array(
                    'server_ip' => $this->getIp(),
                    'class' => 'ServiceFatalException',
                    'message' => $errors['message'],
                    'file' => $errors['file'],
                    'line' => $errors['line'],
                ),
            ));
        }
    }
}


/**
 * 针对JumeiWorker对统计模块的一层封装
 * @author liangl
 */
class StatisticHelper
{
    protected static $timeStart = 0;
    
    public static function tick()
    {
        self::$timeStart = StatisticClient::tick();
    }

    public static function report($data, $ctx, $source_ip)
    {
        $module = $data['class'];
        $interface = $data['method'];
        $code = 0;
        $msg = '';
        $success = true;
        if(is_array($ctx) && isset($ctx['exception']))
        {
            $success = false;
            $code = isset($ctx['exception']['code']) ? $ctx['exception']['code'] : 40404;
            $msg = isset($ctx['exception']['class']) ? $ctx['exception']['class'] . "::" : '';
            $msg .= isset($ctx['exception']['message']) ? $ctx['exception']['message'] : '';
            $msg .= "\n" . $ctx['exception']['traceAsString'];
            $msg .= "\nREQUEST_DATA:[" . json_encode($data) . "]\n";
        }
        
        StatisticClient::report($module, $interface, $code, $msg, $success, $source_ip, '', PHPServerWorker::$currentClientUser);
        PHPServerWorker::$currentModule = PHPServerWorker::$currentInterface = PHPServerWorker::$currentClientIp = PHPServerWorker::$currentRequestBuffer = '';
        PHPServerWorker::$currentClientUser = 'not_set';
    }
}

