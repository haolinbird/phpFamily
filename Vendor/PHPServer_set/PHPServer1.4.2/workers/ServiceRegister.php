<?php 

/**
 * register service to consul
 *
 * @author xudongw
 *
 */
class ServiceRegister extends PHPServerWorker
{
    const KEEP_ALIVE_INTERVAL = 1;

    const REGIST_STATE_FILE = '/var/run/phpserver/regist.state';

    const UNREGISTING_STATE = 0;

    const REGISTING_STATE   = 1;

    protected $registState = 0;

    protected $etcdServerDoveKey;

    /**
     * 该worker进程开始服务的时候会触发一次，可以在这里做一些全局的事情
     * @return bool
     */
    protected function onServe()
    {
        $bootstrap = PHPServerConfig::get('workers.'.$this->serviceName.'.bootstrap');
        if(is_file($bootstrap))
        {
            require_once $bootstrap;
        }

        require_once SERVER_BASE. 'Vendor/Bootstrap/Autoloader.php';
        Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../Vendor/')->init();

        $this->etcdServerDoveKey = PHPServerConfig::get('etc_server_dove_key');
        if (empty($this->etcdServerDoveKey)) {
            $this->etcdServerDoveKey = 'RpcPool.Etcd.Servers';
        }
        $this->registState = @file_get_contents(self::REGIST_STATE_FILE);
        $this->asyncServiceState();
        Task::add(self::KEEP_ALIVE_INTERVAL, array($this, 'asyncServiceState'));
    }

    /**
     * 该worker进程停止服务的时候会触发一次，可以在这里做一些全局的事情
     * @return bool
     */
    protected function onStopServe()
    {
        self::unregistServices();
        return false;
    }

    /**
     * 每隔一段时间(5s)会触发该函数，用于触发worker某些流程
     * @return bool
     */
    protected function onAlarm()
    {
        Task::tick();
    }


    /**
     * 确定包是否完整
     * @see PHPServerWorker::dealInput()
     */
    public function dealInput($recv_str)
    {
        if (strpos($recv_str, "\n") !== false) {
            return 0;
        }
        return 1;
    }
    
    /**
     * 处理业务
     * @see PHPServerWorker::dealProcess()
     */
    public function dealProcess($recv_str)
    {
        $cmd = trim(substr($recv_str, 0, -1));
        $remoteIp = $this->getRemoteIp();
        $localIp  = self::getIp();
        if ($remoteIp != $localIp && $remoteIp != '127.0.0.1') {
            $this->sendToClient("Sorry. Only local ip can communicate with this port. Bye Bye!\n");
            return;
        }
        switch ($cmd) {
            case 'regist':
                $this->registState = self::REGISTING_STATE;
                file_put_contents(self::REGIST_STATE_FILE, self::REGISTING_STATE);
                if ($this->asyncServiceState()) {
                    $this->sendToClient("regist ok\n");
                } else {
                    $this->sendToClient("regist failed. check the server log for details\n");
                }
                break;
            case 'unregist':
                $this->registState = self::UNREGISTING_STATE;
                file_put_contents(self::REGIST_STATE_FILE, self::UNREGISTING_STATE);
                if ($this->asyncServiceState()) {
                    $this->sendToClient("unregist ok\n");
                } else {
                    $this->sendToClient("unregist failed. check the server log for details\n");
                }
                break;
            default:
                $this->sendToClient("wrong cmd, avliable cmds are [regist,unregist]\n");
                break;
        }
    }

    /**
     * aysnc service regist state.
     *
     * @return boolean
     */
    public function asyncServiceState() {
        if ($this->registState == self::UNREGISTING_STATE) {
            return self::unregistServices();
        }
        $workers = PHPServerConfig::get('workers');

        $etcdRegister = Register::instance($this->etcdServerDoveKey) ;

        $isAllServiceRegist = true;
        foreach ($workers as $workerName => $config) {
            $workerClass = PHPServerConfig::get('workers.' . $workerName . '.worker_class');

            if (!$this->isRpcWorker($workerClass)) {
                continue;
            }

            $ip     = self::getIp($workerName);
            $port   = PHPServerConfig::get('workers.'.$workerName.'.port');

            if ($etcdRegister->registerService($ip, $port, $workerName)) {
                ServerLog::add("keepalive service [$workerName] success");
            } else {
                ServerLog::add("keepalive service [$workerName] failed");
                $isAllServiceRegist = false;
            }
        }
        return $isAllServiceRegist;
    }

    public function unregistServices() {
        $etcdRegister = Register::instance($this->etcdServerDoveKey) ;
        return $etcdRegister->unregisterService();
    }

    /**
     * 获取本地ip，优先获取JumeiWorker配置的ip
     * @param string $workerName
     * @return string
     */
    public static function getIp($workerName = 'JumeiWorker')
    {
        $ip = gethostbyname(gethostname());

        $env = PHPServerConfig::get('ENV');
        if($env == 'dev' || empty($ip) || $ip == '0.0.0.0' || $ip == '127.0.0.1')
        {
            if($workerName)
            {
                $ip = PHPServerConfig::get('workers.' . $workerName . '.ip');
            }
            if(empty($ip) || $ip == '0.0.0.0' || $ip == '127.0.0.1')
            {
                $ret_string = shell_exec('ifconfig');
                if(preg_match("/:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $ret_string, $match))
                {
                    $ip = $match[1];
                }
            }
        }
        return $ip;
    }

    protected function send()
    {
        return false;
    }

    /**
     * 获取远程ip.
     *
     * @return string
     */
    public function getRemoteIp()
    {
        $ip = '';
        $sock_name = stream_socket_get_name($this->connections[$this->currentDealFd], true);
        if($sock_name)
        {
            $tmp = explode(':', $sock_name);
            $ip = $tmp[0];
        }
        return $ip;
    }
} 
