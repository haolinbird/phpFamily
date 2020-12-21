<?php

/*
 * 注册服务插件
 * @author xudongw<xudongw@jumei.com>
 **/

class Register
{
    const STATE_TICKING = 0;

    const STATE_STOP    = 1;

    protected $state = self::STATE_STOP;

    /**
     * lark服务启动地址
     */
    protected $larkAddress;

    // http requre retry count
    const RETRY_COUNT       = 2;


    private function __construct($larkAddress) {
        $this->larkAddress = $larkAddress;
    }

    /**
     * 创建一个注册服务的实例.
     *
     * @param string $etcdServerDoveKey etcd dove key.
     *
     * @return \Register
     */
    public static function instance($larkAddress) {
        static $instances = array();

        if (empty($instances[$larkAddress])) {
            $instances[$larkAddress] = new self($larkAddress);
        }
        return $instances[$larkAddress];
    }


    public function registerService($services, $etcdServers){
        $connRetried = false;
        while(true) {
            // 以防lark还未启起来,etcd集群抖动等原因,给一次重试连接的机会.
            $re = $this->_doRegisterService($services, $etcdServers);
            if(!$re && !$connRetried){
                $connRetried = true;
                sleep(5);
                continue;
            }
            break;
        }
        return $re;
    }
    protected function _doRegisterService($services, $etcdServers) {
        $title = '[服务上报]';

        if (empty($etcdServers)) {
            $title = '[端口监听]';
        }
        $con = @stream_socket_client($this->larkAddress, $errno, $errstr, 1);
        if (!$con) {
            ServerLog::add($title. '连接lark心跳接口失败：' . $errstr);
            return false;
        }

        // lark预设每个服务注册超时时间为3秒
        stream_set_timeout($con, count($services) * 3);


        $services = json_encode($services);
        if (empty($etcdServers)) {
            $etcdServers = json_encode($etcdServers, JSON_FORCE_OBJECT);
        } else {
            $etcdServers = json_encode($etcdServers);
        }
        $buffer = Lark::encode(Lark::TAG_KEEPALIVE_TICK, array($services, $etcdServers));

        $len = fwrite($con, $buffer, strlen($buffer));

        if($len != strlen($buffer)) {
            ServerLog::add($title. '向lark写数据失败');
            return false;
        }

        $buffer = '';
        while (($leftLen = \Lark::input($buffer)) > 0) {
            $tmp = fread($con, $leftLen);
            if (!$tmp) {
                ServerLog::add($title. '读取数据失败');
                return false;
            }
            $buffer .= $tmp;
        }

        $larkData = Lark::decode($buffer, $err);

        if ($larkData === false) {
            ServerLog::add($title. 'lark返回数据不完整');
            return false;
        }


        if ($larkData['tag'] == Lark::TAG_KEEPALIVE_OK) {
            return true;
        }

        ServerLog::add($title. "上报心跳失败：{$larkData['data'][0]}");
        return false;

    }

    public function unregisterService($services, $etcdServers) {
        $con = @stream_socket_client($this->larkAddress, $errno, $errstr, 1);

        if (!$con) {
            ServerLog::add('[服务下线]链接lark心跳接口失败：' . $errstr);
            return false;
        }
        stream_set_timeout($con, 5);

        $services = json_encode($services);
        $etcdServers = json_encode($etcdServers);
        $larBuffer = Lark::encode(Lark::TAG_KEEPALIVE_STOP, array($services, $etcdServers));
        $len = fwrite($con,  $larBuffer, strlen($larBuffer));
        if($len != strlen($larBuffer)) {
            ServerLog::add('[服务下线]向lark写数据失败');
            return false;
        }

        $buffer = '';
        while (($leftLen = \Lark::input($buffer)) > 0) {
            $tmp = fread($con, $leftLen);
            if (!$tmp) {
                ServerLog::add('[服务下线]读取数据失败');
                return false;
            }
            $buffer .= $tmp;
        }

        $larkData = Lark::decode($buffer, $err);

        if ($larkData === false) {
            ServerLog::add("[服务下线]失败： lark协议返回失败:$err");
            return false;
        }

        if ($larkData['tag'] == Lark::TAG_STOPALIVE_OK) {
            ServerLog::add("[服务下线]成功");
            return true;
        }

        ServerLog::add("[服务下线]失败：{$larkData['data'][0]}");
        return false;
    }
}
