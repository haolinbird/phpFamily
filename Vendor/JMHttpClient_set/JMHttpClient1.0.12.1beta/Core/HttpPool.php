<?php
/**
 * Connection Manager.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace JMHttpClient\Core;

use JMHttpClient\Exception\HostGroupException;
use \JMHttpClient\Exception\ConfigNotFoundException;
use \JMHttpClient\Exception\ServerException;

/**
 * Connection Manager.
 */
class HttpPool
{
    // 当前分组名.
    protected $hostGroupName = null;

    // 当前分组下的host list.
    protected $hosts = array();
    // 异常的host list.
    protected $faultHosts = array();

    // 对外部业务方单次组件接口调用的sessionid.
    protected $requestUniqid = null;

    // 配置文件中的curl options.
    protected $curlOptions = array();
    // 用户配置, 例如重试, 重试间隔.
    protected $opts = array();

    // Curl句柄.
    protected $handle = null;
    // 当前使用的host, 格式为schema://ip:port
    protected $currentUseHost = null;

    // 子进程id.
    protected $subProcessId = 0;
    // Unix域套接字, 用于父子进程间的通信.
    protected $sockets = null;

    public function __construct($hostGroupName)
    {
        $cfgObj = new \Config\HttpClient;
        if (! property_exists($cfgObj, $hostGroupName)) {
            throw new ConfigNotFoundException('host分组没有找到!');
        }

        $this->hostGroupName = $hostGroupName;

        foreach ($cfgObj->{$hostGroupName}['hosts'] as $host => $weight) {
            $urlInfo = parse_url($host);
            if (! isset($urlInfo['host'])) {
                throw new HostGroupException('host配置无效');
            }

            $scheme = isset($urlInfo['scheme']) ? strtolower($urlInfo['scheme']) : 'http';
            $host = $urlInfo['host'];
            $port = isset($urlInfo['port']) ? $urlInfo['port'] : 80;
            $weight = intval($weight) <= 0 ? 1 : intval($weight);
            $weight = $weight > 10 ? 10 : $weight;

            if (! in_array($scheme, array('http', 'https'))) {
                throw new HostGroupException('当前仅支持http/https协议');
            }

            $key = "$scheme://$host:$port";
            $this->hosts[$key] = $weight;
        }

        if (! $this->hosts) {
            throw new HostGroupException("没有可用的host");
        }

        $this->opts = (array) $cfgObj->{$hostGroupName}['opts'];
        $this->curlOptions = (array) $cfgObj->{$hostGroupName}['curlOptions'];
        if (! isset($this->curlOptions['CURLOPT_FORBID_REUSE'])) {
            $this->curlOptions['CURLOPT_FORBID_REUSE'] = false;
        }

        if (! isset($this->curlOptions['CURLOPT_RETURNTRANSFER'])) {
            $this->curlOptions['CURLOPT_RETURNTRANSFER'] = true;
        }

        if (! isset($this->curlOptions['CURLOPT_HEADER'])) {
            $this->curlOptions['CURLOPT_HEADER'] = false;
        }
    }

    /**
     * 读取用户配置的选项, 例如重试, 重试间隔.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->opts;
    }

    /**
     * 按照配置文件中的Curl配置, 重新初始化Curl resource对象.
     *
     * @param object $ch Curl resource.
     *
     * @return void
     */
    protected function reInitCurlOptions($ch)
    {
        // curl_reset($ch);
        foreach ($this->curlOptions as $key => $value) {
            if (is_string($key)) {
                curl_setopt($ch, constant($key), $value);
            } else {
                curl_setopt($ch, $key, $value);
            }
        }
    }

    /**
     * 初始化一个curl句柄.
     *
     * @return void
     */
    protected function initHandle()
    {
        $ch = curl_init();
        $this->reInitCurlOptions($ch);
        return $ch;
    }

    /**
     * 得到可用的host list.
     *
     * @return array
     */
    protected function getHostPool()
    {
        $pool = array();
        foreach ($this->hosts as $host => $weight) {
            if (! isset($this->faultHosts[$host])) {
                $values = array_fill(count($pool), $weight, $host);
                $pool = array_merge($pool, $values);
            }
        }

        shuffle($pool);
        return $pool;
    }

    /**
     * 根据权重选择一台服务器.
     *
     * @return string
     */
    protected function getHitServer()
    {
        $pool = $this->getHostPool();
        $this->debug('http_pool.valid_hosts', '[' . date('Y-m-d H:i:s') . '] ' . json_encode($pool));
        $this->debug('http_pool.invalid_hosts', '[' . date('Y-m-d H:i:s') . '] ' . json_encode($this->faultHosts));
        if (! $pool) {
            throw new ServerException('在' . $this->hostGroupName . '分组中没有可用的机器');
        }

        return $pool[rand(0, count($pool) - 1)];
    }

    /**
     * Get Handler.
     *
     * @param string $uniqid 对于用户发起的特定的一次请求, 内部可能会重试, uniqid用于标识是否为同一个外部请求.
     *
     * @return mixed
     */
    public function getHandle($uniqid)
    {
        pcntl_signal_dispatch();
        
        if ($this->curlOptions['CURLOPT_FORBID_REUSE'] == true) {
            if (is_resource($this->handle)) {
                curl_close($this->handle);
            }

            $this->handle = $this->initHandle();
        } else {
            if (is_resource($this->handle)) {
                $this->reInitCurlOptions($this->handle);
            } else {
                $this->handle = $this->initHandle();
            }

            $handle = $this->handle;
        }

        // 外部切换到另外一个调用请求, 或 上次使用的host变更为无效.
        // 一个外部请求的多个内部重试, 不切换host.
        if ($this->requestUniqid != $uniqid || ! in_array($this->currentUseHost, $this->getHostPool())) {
            $this->requestUniqid = $uniqid;

            if ($this->curlOptions['CURLOPT_FORBID_REUSE'] == true) {
                $this->currentUseHost = $this->getHitServer();
            } else {

                if (! in_array($this->currentUseHost, $this->getHostPool())) {
                    $this->currentUseHost = $this->getHitServer();
                }
            }
        }

        return array(
            'ch' => $this->handle,
            'host' => $this->currentUseHost,
        );
    }

    /**
     * 主进程的信号处理函数.
     *
     * @param string $sign Sign.
     *
     * @return void
     */
    public function mainSignHandler($sign)
    {
        switch ($sign) {
            case SIGUSR2:
                $host = trim(fgets($this->sockets[0]));
                $this->debug('http_pool.fault_recovery', '[' . date('Y-m-d H:i:s') . '] ' . $host);
                if (isset($this->faultHosts[$host])) {
                    unset($this->faultHosts[$host]);
                }
        }
    }

    /**
     * 子进程的信号处理函数.
     *
     * @param string $sign Sign.
     *
     * @return void
     */
    public function subSignHandler($sign)
    {
        switch ($sign) {
            case SIGUSR1:
                $invalidHost = trim(fgets($this->sockets[1]));
                $this->debug('http_pool.sub_process_accept', '[' . date('Y-m-d H:i:s') . '] ' . $invalidHost);
                if (! isset($this->faultHosts[$invalidHost])) {
                    $this->faultHosts[$invalidHost] = time();
                }
        }
    }

    /**
     * 在故障条件下, 通知子进程对特定的host进行检查.
     *
     * @return void
     */
    public function test()
    {
        $this->debug('http_pool.join_test_queue', '[' . date('Y-m-d H:i:s') . '] ' . $this->currentUseHost);

        if (! isset($this->opts['check_falut_host']) || $this->opts['check_falut_host'] == false) {
            $this->currentUseHost = null;
            return;
        }

        if (php_sapi_name() != 'cli') {
            $pool = $this->getHostPool();
            // 只有一台服务器的情况下, 不允许剔除.
            if (count($pool) > 1) {
                $this->faultHosts[$this->currentUseHost] = time();
            }
            $this->currentUseHost = null;
            return;
        }

        pcntl_signal_dispatch();
        pcntl_signal(SIGUSR2, array($this, 'mainSignHandler'));

        $pid = 0;
        if ($this->subProcessId > 0) {
            if (posix_kill($this->subProcessId, 0)) {
                $pid = $this->subProcessId;
            }
        }

        if (! isset($this->faultHosts[$this->currentUseHost])) {
            $this->faultHosts[$this->currentUseHost] = time();
        }

        $currentUseHost = $this->currentUseHost;
        $this->currentUseHost = null;

        if ($pid > 0) {
            fwrite($this->sockets[0], $currentUseHost . PHP_EOL);
            posix_kill($pid, SIGUSR1);
        } else {
            $this->sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            $pid = pcntl_fork();

            if ($pid == -1) {
                return false;
            } else if ($pid > 0) {
                fclose($this->sockets[1]);
                fwrite($this->sockets[0], $currentUseHost . PHP_EOL);

                sleep(1);
                posix_kill($pid, SIGUSR1);

                $this->subProcessId = $pid;
            } else {
                fclose($this->sockets[0]);
                pcntl_signal(SIGUSR1, array($this, 'subSignHandler'));
                while (true) {
                    pcntl_signal_dispatch();

                    $faultHosts = $this->faultHosts;
                    foreach ($faultHosts as $host => $time) {
                        $ret = 'Error';

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $host);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
                        curl_exec($ch);
                        if (! curl_errno($ch)) {
                            $ret = 'Success';
                            unset($this->faultHosts[$host]);
                            fwrite($this->sockets[1], $host . PHP_EOL);
                            posix_kill(posix_getppid(), SIGUSR2);
                        }

                        curl_close($ch);

                        $this->debug('http_pool.sub_process_testing', '[' . date('Y-m-d H:i:s') . '] ' . $host . ' - ' . $ret);
                    }

                    sleep(1);
                }

                exit;
            }
        }

        return true;
    }

    public function debug($file, $content)
    {
        if (isset($this->opts['debug']) && $this->opts['debug'] == true) {
            if (isset($this->opts['log_dir']) && $this->opts['log_dir']) {
                $log_dir = $this->opts['log_dir'];
                if (! is_dir($log_dir)) {
                    if (! mkdir($log_dir, 0755, true)) {
                        throw new \Exception('无法创建日志目录:' . $log_dir);
                    }
                }
            } else {
                $log_dir = sys_get_temp_dir();
            }

            if (substr($content, -1) != "\n") {
                $content .= "\n";
            }

            file_put_contents("$log_dir/$file", $content, FILE_APPEND | LOCK_EX);
        }
    }
}