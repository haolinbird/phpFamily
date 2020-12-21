<?php 

/**
 * register service to consul
 *
 * @author xudongw
 *
 */

use \PHPServer\plugins\Utility;

class ServiceRegister extends PHPServerWorker {
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

        $this->asyncServiceState();
        Task::add(self::KEEP_ALIVE_INTERVAL, array($this, 'asyncServiceState'));
    }

    protected function syncRegistState() {
        if (!empty($this->idcRegistState)) {
            return $this->idcRegistState;
        }

        $idc2Etcd = $this->getIdc2EtcdServers($err);

        if (!$idc2Etcd) {
            ServerLog::add($err);
            $this->sendRegistAlarm("无法获取机房注册状态，该服务可能无法正常上报");
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
    public function dealProcess($recv_str) {
        $cmdGroupBuffer = trim(substr($recv_str, 0, -1));

        // 命令例子： regist [cl|yz|local(default value)]  兼容输入多个空格的情况;
        $cmdGroup = explode(' ', preg_replace('/ +/', ' ', $cmdGroupBuffer));

        if (!$env = $this->getEnv($err)) {
            $this->sendToClient($err.PHP_EOL);
            return;
        }

        //是否未指定idc
        $noIdc = false;
        if (count($cmdGroup) == 1) {
            $cmd = $cmdGroup[0];
            $idc = $env;
            $noIdc = true;
        } else {
            $cmd = $cmdGroup[0];
            $idc = $cmdGroup[1];
        }

        $remoteIp = $this->getRemoteIp();
        $localIp  = $this->getIp();
        if ($remoteIp != $localIp && $remoteIp != '127.0.0.1') {
            $this->sendToClient("对不起，只有本机能使用该端口，再见!\n");
            return;
        }

        if (!$idc2EtcdServers = $this->getIdc2EtcdServers($err)) {
            $this->sendToClient($err.PHP_EOL);
            return;
        }

        if ($cmd == 'help') {
            $idcs = array_keys($idc2EtcdServers);
            $help = "上线下格式为:命令+空格+机房代码\n命令可选项为regist,unregist\n机房代码可选为:" . implode(",", $idcs);
            $help .= "\nregist默认为{$env}，即为本机房.\nunregist默认为所有机房下线\n例子1:regist\n例子2:regist $idcs[1]";
            $this->sendToClient($help . PHP_EOL);
            return;
        }

        if (empty($idc2EtcdServers[$idc])) {
            $err = "操作失败：无法识别的机房代号:$idc. 可选项为:". implode(",", array_keys($idc2EtcdServers));
            ServerLog::add($err);
            $this->sendToClient($err.PHP_EOL);
            return;
        }

        switch ($cmd) {
            case 'regist':
                $this->idcRegistState[$idc] = self::REGISTING_STATE;
                file_put_contents(self::REGIST_STATE_FILE_PREFIX . '.' . $idc, self::REGISTING_STATE);
                if ($this->asyncServiceState()) {
                    $this->sendToClient("注册成功\n");
                } else {
                    $this->sendToClient("注册失败。详细原因请查看phpserver日志\n");
                }
                break;
            case 'unregist':
                if ($noIdc) {// 入参如果没有指定idc，则下线所有服务器
                    $idcs = array_keys($idc2EtcdServers);
                } else {
                    $idcs = array($idc);
                }
                foreach ($idcs as $tidc) {
                    $this->idcRegistState[$tidc] = self::UNREGISTING_STATE;
                    file_put_contents(self::REGIST_STATE_FILE_PREFIX . '.' . $tidc, self::UNREGISTING_STATE);
                }

                if ($this->unRegistServices($idcs)) {
                    $this->sendToClient("下线成功\n");
                } else {
                    $this->sendToClient("下线失败。详细原因请查看phpserver日志\n");
                }
                break;
            default:
                $this->sendToClient("操作失败：错误的命令：$cmd, 可用命令为：regist,unregist\n");
                break;
        }
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
            if (!$idc2EtcdServers = $this->getIdc2EtcdServers($err)) {
                $this->sendRegistAlarm("获取etcd集群地址失败，可能无法正常上报服务");
            }

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
                    ServerLog::add("[服务上报]成功");
                } else {
                    ServerLog::add("[端口监听]成功");

                }
                $isError = false;//重置为正常状态
                return true;
            }
        } while(0);

        if (!empty($registEtcdServers)) { // 只有注册状态才报警
            $isError = $this->sendRegistAlarm("向反向代理lark上报心跳失败");
        }
        return false;
    }

    protected function sendRegistAlarm($err) {
        if (PHPServerConfig::get('register_alarm') === false) { //确定不需要报警
            return false;
        }

        $date = date('Y-m-d');
        $err .= ".具体失败原因请上服务器查看PHPServer日志:/home/www/PHPServer/logs/$date/server.log";

        // 已经报警次数，按照报警次数计算下次报警时间
        static $notifyCounter = 0;
        static $nextNotifyTs = 0;

        if($notifyCounter >= 4) { // 间隔最多16分钟
            $notifyCounter = 0;
        }

        if (time() > $nextNotifyTs) {
            $localIp = $this->getIp();
            Sms::sendSms($this->projectName, array('ip' => $localIp), "PHPServer ip: $localIp. $err");
            $nextNotifyTs = time() + 60 * pow(2, $notifyCounter);
            $notifyCounter++;
            return true;
        }
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
        $n = 2;
        do{
            $n--;
            try {
                // 兼容centos下配置的不同doveclient地址
                $doveclientAddr = PHPServerConfig::get('doveclient_addr');
                if (!empty($doveclientAddr)) {
                    \DoveClient\Config::config(array('addr'=>$doveclientAddr));
                }
                $idc2EtcdServers = DoveClient\Config::get('RpcPool.Etcd.Idc2EtcdServers', true);
            } catch(Exception $e) {
                $errT = "无法从Dove读取[RpcPool.Etcd.Idc2EtcdServers]，错误原因：". $e->getMessage();
                if($n > 0){
                    ServerLog::add($errT);
                    sleep(5);
                    continue;
                }
                $err = "无法从Dove读取[RpcPool.Etcd.Idc2EtcdServers]：将使用最近一次cache. 错误原因：". $e->getMessage();
                ServerLog::add($err);
                return $this->idc2EtcdCache;
            }
        }while($n > 0);
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
