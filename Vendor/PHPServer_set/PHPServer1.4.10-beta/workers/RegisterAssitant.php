<?php 

/**
 * register service to consul
 *
 * @author xudongw
 *
 */
use \PHPServer\plugins\Utility;

class RegisterAssitant extends PHPServerWorker {
    const REGIST_STATE_FILE_PREFIX = '/var/run/phpserver/regist.state';

    const ETCD_DOVEKEY_PREFIX = 'RpcPool.Etcd.Servers';

    const KEEP_ALIVE_INTERVAL = 1;

    const UNREGISTING_STATE = 0;

    const REGISTING_STATE   = 1;

    protected $idcRegistState = array();

    protected $idc2EtcdCache = false;

    protected $etcdServerDoveKey;

    protected $larkAddress;

    /**
     * 该worker进程开始服务的时候会触发一次，可以在这里做一些全局的事情
     * @return bool
     */
    protected function onServe() {
        $bootstrap = PHPServerConfig::get('workers.'.$this->serviceName.'.bootstrap');
        if(is_file($bootstrap)) {
            require_once $bootstrap;
        }

        if(!class_exists('\Bootstrap\Autoloader')) {
            require_once SERVER_BASE. 'Vendor/Bootstrap/Autoloader.php';
        }
        Bootstrap\Autoloader::instance()->addRoot(__DIR__.'/../Vendor/')->init();

        $this->larkAddress = PHPServerConfig::get("keep_alive_address");
        if (empty($this->larkAddress)) {
            $this->larkAddress = 'tcp://127.0.0.1:12312';
        }

        while(1) {
            $this->asyncServiceState();
            sleep(5);
        }
    }

    protected function syncRegistState() {
        $idc2Etcd = $this->getIdc2EtcdServers($err);

        if (!$idc2Etcd) {
            return $this->idcRegistState;
        }

        foreach ($idc2Etcd as $idc=>$etcd) {
            $this->idcRegistState[$idc] = @file_get_contents(self::REGIST_STATE_FILE_PREFIX . '.' . $idc);
        }

        return $this->idcRegistState;
    }

    /**
     * 该worker进程停止服务的时候会触发一次，可以在这里做一些全局的事情
     * @return bool
     */
    protected function onStopServe() {
        $this->unRegistServices();
        return false;
    }

    /**
     * 每隔一段时间(5s)会触发该函数，用于触发worker某些流程
     * @return bool
     */
    protected function onAlarm() {
    }


    /**
     * 确定包是否完整
     * @see PHPServerWorker::dealInput()
     */
    public function dealInput($recv_str)
    {
    }
    
    /**
     * 处理业务
     * @see PHPServerWorker::dealProcess()
     */
    public function dealProcess($recv_str) {
    }

    protected function unRegistServices($idcs = array()) {
        $register = Register::instance($this->larkAddress);
        $services = $this->getServices();

        if (!$idc2EtcdServers = $this->getIdc2EtcdServers($err)) {
            return false;
        }

        $unregistEtcdServers = array();
        if (count($idcs)) {
            foreach ($idcs as $idc) {
                $unregistEtcdServers[$idc] = $idc2EtcdServers[$idc];
            }
        } else { //如果没有指定下线那个idc，就认为是全部下线
            $unregistEtcdServers = $idc2EtcdServers;
        }
        if (!$register->unregisterService($services, $unregistEtcdServers)) {
            return false;
        }
        return true;
    }


    /**
     * aysnc service regist state.
     *
     * @return boolean
     */
    public function asyncServiceState() {
        static $isError = false;
        // do中除了return true的分支逻辑，其他都是失败了，需要报警以及记录日志
        do {
            $idc2EtcdServers = $this->getIdc2EtcdServers($err);

            $this->syncRegistState();

            // 需要上报到的etcd服务集群。
            $registEtcdServers = array();
            foreach ($this->idcRegistState as $idc => $state) {
                if ($state == self::REGISTING_STATE) {
                    $registEtcdServers[$idc] = $idc2EtcdServers[$idc];
                }
            }

            $register = Register::instance($this->larkAddress);

            $services = $this->getServices();

            if ($register->registerService($services, $registEtcdServers)) {
                if (!empty($registEtcdServers)) {
                    if ($isError) {
                        $localIp = $this->getIp();
                        Sms::sendSms($this->projectName, array('ip' => $localIp), "PHPServer ip: $localIp. 服务上报恢复正常");
                    }
                    ServerLog::add("[服务上报(辅助进程)]成功");
                } else {
                    ServerLog::add("[端口监听(辅助进程)]成功");

                }
                $isError = false;//重置为正常状态
                return true;
            }
        } while(0);

        return false;
    }


    protected function getServices() {
        $workers = PHPServerConfig::get('workers');
        $services = array();
        foreach ($workers as $serviceName => $config) {
            $workerClass = PHPServerConfig::get('workers.' . $serviceName . '.worker_class');

            if (!self::isRpcWorker($workerClass)) {
                continue;
            }

            $ip         = $this->getIp($serviceName);
            $port       = (int)PHPServerConfig::get('workers.'. $serviceName . '.port');
            $workerNum  = (int)PHPServerConfig::get('workers.' . $serviceName . '.child_count');
            $cgiPass    = Utility::getCgiPass($serviceName);

            if (!$this->isValidIp($ip)) {
                return false;
            }

            $item = array(
                'ip'            => $ip,
                'port'          => $port,
                'service'       => $serviceName,
                'worker_num'    => $workerNum,
                'cgi_pass'      => $cgiPass,
            );

            $services[] = $item;
        }
        return $services;
    }

    protected function getIdc2EtcdServers(&$err) {
        try {
            // 兼容centos下配置的不同doveclient地址
            $doveclientAddr = PHPServerConfig::get('doveclient_addr');
            if (!empty($doveclientAddr)) {
                \DoveClient\Config::config(array('addr'=>$doveclientAddr));
            }
            $idc2EtcdServers = DoveClient\Config::get('RpcPool.Etcd.Idc2EtcdServers', true);
        } catch(Exception $e) {
            $err = "无法从Dove读取[RpcPool.Etcd.Idc2EtcdServers]：将使用最近一次cache. 错误原因：". $e->getMessage();
            ServerLog::add($err);
            return $this->idc2EtcdCache;
        }
        $this->idc2EtcdCache = $idc2EtcdServers;
        return $idc2EtcdServers;
    }

    protected function getEnv(&$err) {
        try {
            // 兼容centos下配置的不同doveclient地址
            $doveclientAddr = PHPServerConfig::get('doveclient_addr');
            if (!empty($doveclientAddr)) {
                \DoveClient\Config::config(array('addr'=>$doveclientAddr));
            }
            $env = DoveClient\Config::get('RpcPool.ENV', true);
        } catch(Exception $e) {
            $err = "操作失败：无法从Dove读取[RpcPool.ENV]：". $e->getMessage();
            ServerLog::add($err);
            return false;
        }
        return $env;
    }

    protected function send(){
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

    protected function isValidIp($ip){
        static $alarmSendCount = array();
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            Sms::sendSms($this->projectName, null, "项目{$this->projectName}, ip:[$ip]不合法, 无法注册到etcd", true);
            return false;
        }
        try {
            $doveclientAddr = PHPServerConfig::get('doveclient_addr');
            if (!empty($doveclientAddr)) {
                \DoveClient\Config::config(array('addr'=>$doveclientAddr));
            }
            $ipSegmentConfig = DoveClient\Config::get('RpcPool.Register.AllowIpSegment', true);
        } catch(Exception $e) {
            ServerLog::add("获取注册ip段白名单失败, 错误原因:". $e->getMessage());
            return true;
        }

        $ipSeg = explode('.', $ip);
        if ($ipSegmentConfig['use'] && !in_array($ipSeg[0], $ipSegmentConfig['ipSegments'])) {
            $msg = "项目{$this->projectName}, ip:[$ip]疑似外网ip, 不能注册到etcd";
            if(@++$alarmSendCount[$ip] < 10) {
                Sms::sendSms($this->projectName, null, $msg, true);
            } else {
                ServerLog::add($msg);
            }
            return false;
        }

        return true;
    }
} 
