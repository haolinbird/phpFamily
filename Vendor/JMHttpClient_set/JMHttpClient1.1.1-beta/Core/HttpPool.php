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

    // 故障的服务器被剔除后，在经过该时间后会被恢复(秒）.
    protected $faultRecoveryInterval = 60;

    // host recovery.
    protected $cfgTag = null;
    protected $shmsize = 0;

    public function __construct($hostGroupName)
    {
        if (empty($hostGroupName)) {
            return;
        }
        
        $cfgObj = null;
        if (class_exists('\Config\JMHttpClient')) {
            $cfgObj = new \Config\JMHttpClient;
        } else if (class_exists('\Config\HttpClient')) {
            // 兼容之前依赖的配置文件.
            $cfgObj = new \Config\HttpClient;
        }

        if (is_null($cfgObj) || ! property_exists($cfgObj, $hostGroupName)) {
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
            if (isset($urlInfo['port'])) {
                $port = $urlInfo['port'];
            } else {
                if ($scheme == 'https') {
                    $port = 443;
                } else {
                    $port = 80;
                }
            }

            $weight = intval($weight) <= 0 ? 1 : intval($weight);
            $weight = $weight > 10 ? 10 : $weight;

            if (! in_array($scheme, array('http', 'https'))) {
                throw new HostGroupException('当前仅支持http/https协议');
            }

            $key = "$scheme://$host:$port";
            if (isset($urlInfo['path'])) {
                $key .= $urlInfo['path'];
            }
            
            $this->hosts[$key] = $weight;
        }

        if (! $this->hosts) {
            throw new HostGroupException("没有可用的host");
        }

        if (isset($cfgObj->{$hostGroupName}['opts'])) {
            $this->opts = (array) $cfgObj->{$hostGroupName}['opts'];
        }

        if (isset($cfgObj->{$hostGroupName}['curlOptions'])) {
            $this->curlOptions = (array) $cfgObj->{$hostGroupName}['curlOptions'];
        }
        
        if (! isset($this->curlOptions['CURLOPT_FORBID_REUSE'])) {
            $this->curlOptions['CURLOPT_FORBID_REUSE'] = false;
        }

        if (! isset($this->curlOptions['CURLOPT_RETURNTRANSFER'])) {
            $this->curlOptions['CURLOPT_RETURNTRANSFER'] = true;
        }

        if (! isset($this->curlOptions['CURLOPT_HEADER'])) {
            $this->curlOptions['CURLOPT_HEADER'] = false;
        }

        $hosts = array_keys($this->hosts);
        sort($hosts);
        $this->cfgTag = md5(json_encode($hosts) . ":$hostGroupName");
        $this->shmsize = count($hosts) * 50 * 10;
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
        // if (function_exists('curl_reset')) {
        //     curl_reset($ch);
        // }

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
        if ($this->countValidServer() == 0) {
            $this->recoveryFalutHost(true);
        }

        $pool = array();
        $faultHosts = Repair::instance($this->cfgTag, $this->shmsize)->getFaultHosts();
        foreach ($this->hosts as $host => $weight) {
            if (! isset($faultHosts[$host])) {
                $values = array_fill(count($pool), $weight, $host);
                $pool = array_merge($pool, $values);
            }
        }

        shuffle($pool);
        return $pool;
    }

    /**
     * 解冻故障服务器.
     *
     * @param boolean $force 是否强制解冻所有故障服务器.
     *
     * @return void
     */
    public function recoveryFalutHost($force = false)
    {
        if ($force) {
            Repair::instance($this->cfgTag, $this->shmsize)->recoveryAll();
            return;
        }

        $recoveryInterval = isset($this->opts['fault_host_recovery_interval']) ? $this->opts['fault_host_recovery_interval'] : $this->faultRecoveryInterval;
        Repair::instance($this->cfgTag, $this->shmsize)->recoveryFalutHost($recoveryInterval);
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
        if (! $pool) {
            throw new ServerException('在' . $this->hostGroupName . '分组中没有可用的机器');
        }

        return $pool[rand(0, count($pool) - 1)];
    }

    /**
     * 得到有效服务器的数量.
     *
     * @return int
     */
    public function countValidServer()
    {
        $this->recoveryFalutHost();
        $faultHosts = Repair::instance($this->cfgTag, $this->shmsize)->getFaultHosts();
        $validHosts = array_diff(array_keys($this->hosts), array_keys($faultHosts));
        return count($validHosts);
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
        // pcntl_signal_dispatch();
        
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
     * 在故障条件下, 通知子进程对特定的host进行检查.
     *
     * @return void
     */
    public function test()
    {
        $this->debug('http_pool.join_test_queue', '[' . date('Y-m-d H:i:s') . '] ' . $this->currentUseHost);
        Repair::instance($this->cfgTag, $this->shmsize)->test($this->currentUseHost);
    }

    /**
     * Debug.
     *
     * @param string $file    Logfile path.
     * @param string $content Content.
     *
     * @return void
     */
    public function debug($file, $content)
    {
        // 只有开发和测试人员才可能使用此功能.
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
