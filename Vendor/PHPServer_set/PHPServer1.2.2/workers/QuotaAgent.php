<?php

/**
 * 
 * 返回1表示有配额，0表示没有配额
 * @author liangl
 * 
 */
class QuotaAgent extends PHPServerWorker
{
	/**
	 * [class=>[method=>[user=>count, user2=>count], method2=>[..]], class2=>[method=>[..], ..]]
	 * @var array
	 */
	protected $quotaData = array();
	
	/**
	 * [class=>[method=>[user=>count, user2=>count], method2=>[..]], class2=>[method=>[..], ..]]
	 * @var array
	 */
	protected $qpsData = array();
	
	/**
	 * 定时器时间间隔
	 * @var int
	 */
	protected $timeIntervalMS = 1000;
	
	/**
	 * 配额总控地址
	 * @var string
	 */
	protected $quotaServiceUri = '';
	
	/**
	 * 到quotaService的长连接
	 * @var resource
	 */
	protected $quotaServiceConnection = null;
	
	
	/**
     * 该worker进程开始服务的时候会触发一次
     * @return bool
     */
    protected function onServe()
    {
    	$quota_service_uri = PHPServerConfig::get('workers.'.$this->serviceName.'.quota_service_uri');
    	if($quota_service_uri)
    	{
    		$this->quotaServiceUri = $quota_service_uri;
    	}
    	
        $this->eventLoopName = 'Select';
        
        $this->installSignal();
        
        $this->event = new $this->eventLoopName();
        
        // 添加管道可读事件
        $this->event->add($this->channel,  BaseEvent::EV_READ, array($this, 'dealCmd'), null, 0, 0);
        
        // 增加select超时事件
        $this->event->add(0, Select::EV_SELECT_TIMEOUT, array($this, 'onTimeCheck'), array() , $this->timeIntervalMS);
        
        // 添加accept事件
        $this->event->add($this->mainSocket,  BaseEvent::EV_ACCEPT, array($this, 'accept'));
        
        $this->lastCallOnTime = microtime(true);
        
        $this->connectToQuotaService();
        
        // 主体循环
        while(1)
        {
            $ret = $this->event->loop();
            $this->notice("evet->loop returned " . var_export($ret, true));
        }
    }
    
    /**
     * 处理住进成发过来的命令
     * @see PHPServerWorker::dealCmd()
     */
    public function dealCmd($channel, $length, $buffer)
    {
        $this->onTimeCheck();
        parent::dealCmd($channel, $length, $buffer);
        $this->onTimeCheck();
    }
    
    /**
     * 检查是否到达设定时间
     */
    public function onTimeCheck()
    {
        $time_now = microtime(true);
        if(($time_now - $this->lastCallOnTime)*1000 >= $this->timeIntervalMS)
        {
            $this->lastCallOnTime = $time_now;
            $this->onTime();
        }
        $time_diff = ($this->lastCallOnTime*1000 + $this->timeIntervalMS) - microtime(true)*1000;
        if($time_diff <= 0)
        {
            call_user_func(array($this, 'onTimeCheck'));
        }
        else
       {
            $this->event->setReadTimeOut($time_diff);
        }
    }
    
    public function onTime()
    {
    	if(!$this->qpsData)
    	{
    		return;
    	}
    	$this->connectToQuotaService();
    	if(is_resource($this->quotaServiceConnection) && !feof($this->quotaServiceConnection))
    	{
    		fwrite($this->quotaServiceConnection, json_encode($this->qpsData)."\n");
    		$response_buffer = fgets($this->quotaServiceConnection, 10240000);
    		$data = json_decode(trim($response_buffer), true);
	    	if(!is_array($data))
	    	{
	    		$this->quotaData = array();
	    	    $this->qpsData = array();
	    		$this->notice("dealQuotaServcieProcess fail data:".var_export($response_buffer, true));
	    		echo "dealQuotaServcieProcess fail data:".var_export($response_buffer, true)."\n";
	    		return false;
	    	}
	    	$this->quotaData = $data;
	    	$this->qpsData = array();
	    	return;
    	}
    	else 
    	{
    		$this->notice("onTime send quota data to service fail");
    	}
    	$this->quotaData = array();
	    $this->qpsData = array();
    }
    
    protected function connectToQuotaService()
    {
    	if(!$this->quotaServiceUri)
    	{
    		return;
    	}
        if(!is_resource($this->quotaServiceConnection) || feof($this->quotaServiceConnection) || feof($this->quotaServiceConnection))
    	{
    	    $this->quotaServiceConnection = stream_socket_client($this->quotaServiceUri, $errno, $errmsg, 1);
	    	if(!$this->quotaServiceConnection)
	    	{
	    		$this->notice("can not connect to quota service {$this->quotaServiceUri} :".$errmsg);
	    		echo "can not connect to quota service {$this->quotaServiceUri} :".$errmsg."\n";
	    	}
	    	else
	    	{
	    		stream_set_timeout($this->quotaServiceConnection, 0, 10000);
	    	}
    	}
    }
	
	/**
	 * 协议为 json+换行
	 * @see PHPServerWorker::dealInput()
	 */
    public function dealInput($recv_str)
    {
    	return 0;
    }
    
    public function dealProcess($recv_str)
    {
       	$recv_str = trim($recv_str);
       	$data = explode("\n", $recv_str);
       	// ['class'=>xx, 'method'=>xx, 'user'=>xx]
    	$data = json_decode(end($data), true);
    	if(!isset($data['class']) || !isset($data['method']) || !isset($data['user']))
    	{
    		$this->notice("bad request \$recv_str:" . $recv_str);
    		return $this->sendToClient("bad request\n");
    	}
    	
    	$class = $data['class'];
    	$method = $data['method'];
    	$user = $data['user'];
    	
    	if(!isset($this->quotaData[$this->projectName][$class][$method][$user]))
    	{
    		$this->quotaData[$this->projectName][$class][$method][$user] = 0;
    	}
    	
        if(!isset($this->qpsData[$this->projectName][$class][$method][$user]))
    	{
    		$this->qpsData[$this->projectName][$class][$method][$user] = 0;
    	}
    	
    	// qps++
    	++$this->qpsData[$this->projectName][$class][$method][$user];
    	if(++$this->quotaData[$this->projectName][$class][$method][$user] > 0)
    	{
    		return $this->sendToClient('1');
    	}
    	
    	return $this->sendToClient('0');
    }
    
    /**
     * 处理受到的数据
     * @param event_buffer $event_buffer
     * @param int $fd
     * @return void
     */
    public function dealInputBase($connection, $length, $buffer, $fd = null)
    {
        $this->currentDealFd = $fd;
        
        // 出错了
        if($length == 0)
        {
            if(feof($connection))
            {
                // 客户端提前断开链接
                $this->statusInfo['client_close']++;
            }
            else
           {
                // 超时了
                $this->statusInfo['recv_timeout']++;
            }
            $this->closeClient($fd);
            if($this->workerStatus == self::STATUS_SHUTDOWN)
            {
                $this->stopServe();
            }
            return;
        }
        
        if(isset($this->recvBuffers[$fd]))
        {
            $buffer = $this->recvBuffers[$fd] . $buffer;
        }
        
        $remain_len = $this->dealInput($buffer);
        // 包接收完毕
        if(0 === $remain_len)
        {
            // 业务处理
            $this->dealProcess($buffer);
            
            // 是否是长连接
            if($this->isPersistentConnection)
            {
                // 清空缓冲buffer
                unset($this->recvBuffers[$fd]);
            }
            else
           {
                // 关闭链接
                $this->closeClient($fd);
            }
        }
        // 出错
        else if(false === $remain_len)
        {
            // 出错
            $this->statusInfo['packet_err']++;
            $this->sendToClient('packet_err:'.$buffer);
            $this->notice('packet_err:'.$this->getRemoteIp().'\n'.$buffer);
            $this->closeClient($fd);
        }
        // 还有数据没收完，则保存收到的数据，等待其它数据
        else
       {
            $this->recvBuffers[$fd] = $buffer;
        }

        // 检查是否到达请求上限或者服务是否是关闭状态
        if($this->workerStatus == self::STATUS_SHUTDOWN)
        {
            // 停止服务
            $this->stopServe();
            // 5秒后退出进程
            pcntl_alarm(self::EXIT_WAIT_TIME);
        }
    }
    
    protected function closeClient()
    {
    	$fd = $this->currentDealFd;
    	if(isset($this->connections[$fd]))
    	{
            $this->event->delAll($this->connections[$fd]);
            fclose($this->connections[$fd]);
            unset($this->connections[$fd], $this->recvBuffers[$fd]);
    	}
    }
    
    
    protected function sendMsg($type, $phone, $info)
    {
    	$url ='http://sms.int.jumei.com/send';
    	$param = array(
    			'channel' => 'monternet',
    			'key'     => 'notice_rt902pnkl10udnq',
    			'task'    => 'int_notice',
    	);
    	$content = '';
    	switch ($type)
    	{
    		case self::TYPE_MAIN_PROCESS_EXIT:
    			$content = 'PHPServer框架告警 主进程意外退出 ip:'.implode(',', $info['ip']).' 时间：'.date('Y-m-d H:i:s');
    			break;
    		case self::TYPE_WORKER_BLOCKED:
    			$content = 'PHPServer业务告警 业务进程'. implode(',', $info['worker_name']) ."长时间阻塞，阻塞进程总数:{$info['count']} ip:" . implode(',', $info['ip']) . " 时间：".date('Y-m-d H:i:s');
    			break;
    		case self::TYPE_WORKER_FATAL_RROR:
    			$content = 'PHPServer业务告警 业务进程 '. implode(',', $info['worker_name'])." 5分钟内共发生FatalError {$info['count']}次 ip:".implode(',', $info['ip']). " 时间：".date('Y-m-d H:i:s');
    			break;
    		case self::TYPE_WORKER_EXIT:
    			$content = 'PHPServer框架告警 业务进程'. implode(',', $info['worker_name'])."共退出{$info['count']}次，退出状态码:".implode(',', $info['status'])." ip:".implode(',', $info['ip']). " 时间：".date('Y-m-d H:i:s');
    			break;
    		case self::TYPE_FRAME_SUCCES_RATE:
    			$content = 'PHPServer框架告警 业务'. implode(',', $info['worker_name'])." 成功率：".round(array_sum($info['percentage'])/count($info['percentage']), 2)."% ip:".implode(',', $info['ip']). " 时间：".date('Y-m-d H:i:s');
    			break;
    		case self::TYPE_WORKER_SUCCESS_RATE:
    			$content = 'PHPServer业务告警 调用接口'. implode(',', $info['interface'])." 共{$info['total_count']}次，失败{$info['fail_count']}次，成功率：".round((($info['total_count']-$info['fail_count'])*100)/$info['total_count'], 2)."% 服务端ip:".implode(',', $info['ip']). " 时间：".date('Y-m-d H:i:s');
    			break;
    		case self::TYPE_CLIENT_CONNECTION_FAIL:
    			$content = 'PHPServer客户端告警 客户端 '.implode(',', $info['ip']).' 连接 服务端 '.implode(',', $info['target_ip']).'失败 时间：'.date('Y-m-d H:i:s');
    			break;
    		default :
    			$this->notice("UNKNOW TYPE sendMsg($phone, ".json_encode($info).") fail");
    			return;
    	}
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge(array('num'=>$phone,'content'=>$content) , $param)));
    	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    	$ret = curl_exec($ch);
    	$this->notice('send phone:'.$phone.' msg:' . $content. ' send_ret:' .var_export($ret, true));
    	$date_time = date('Y-m-d H:i:s');
    	$phone = substr($phone, 0, 3)."****".substr($phone, -4);
    	if(!is_dir(SERVER_BASE . 'logs/statistic/alarm'))
    	{
    		mkdir(SERVER_BASE . 'logs/statistic/alarm', 0777);
    	}
    	
    	if($type == self::TYPE_CLIENT_CONNECTION_FAIL)
    	{
    		$ips = $info['target_ip'];
    	}
    	else 
    	{
    		$ips = $info['ip'];
    	}
    	
    	file_put_contents(SERVER_BASE . 'logs/statistic/alarm/'.date('Y-m-d'), "$content\t$date_time\t$phone\t".var_export($ret, true)."\t".json_encode($ips)."\n", FILE_APPEND);
    }
}
