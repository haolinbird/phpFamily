<?php
/**
 * TSocket.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace Thrift\Pool;

/**
 * TSocket.
 */
class TSocket extends \Thrift\Transport\TSocket
{
    protected $useConnectPoolFlag = false;

    public function getConnnectPoolStatus()
    {
        return $this->useConnectPoolFlag;
    }

    public function open($useConnectPool = true)
    {
        if (\Thrift\Context::get('use_connect_pool') == true && $useConnectPool == true) {
            $proxyHost = null;
            $proxyPort = null;

            if (defined('MCP_SERVER')) {
                if (stripos(MCP_SERVER, 'unix://') === 0 || stripos(MCP_SERVER, '/') === 0) {
                    $proxyHost = MCP_SERVER;
                    $proxyPort = 0;
                } else {
                $pos = strrpos(MCP_SERVER, ':');
                    $proxyHost = substr(MCP_SERVER, 0, $pos);
                    $proxyPort = substr(MCP_SERVER, $pos + 1);
                }
            } else {
                $proxyHost = ini_get('redis.proxy.host');
                $proxyPort = ini_get('redis.proxy.port');
            }

            if ($proxyHost) {
                if ($proxyPort == -1) {
                    $proxyPort = 0;
                }

                if ($proxyHost{0} == '/') {
                    $proxyHost = "unix://$proxyHost";
                }

                if ($this->persist_) {
                    $this->handle_ = @pfsockopen(
                        $proxyHost,
                        $proxyPort,
                        $errno,
                        $errstr,
                        $this->sendTimeoutSec_ + ($this->sendTimeoutUsec_ / 1000000)
                    );
                } else {
                    $this->handle_ = @fsockopen(
                        $proxyHost,
                        $proxyPort,
                        $errno,
                        $errstr,
                        $this->sendTimeoutSec_ + ($this->sendTimeoutUsec_ / 1000000)
                    );
                }

                if ($this->isOpen()) {
                    $buf = "9\r\nthrift://\r\n";
                    $write = fwrite($this->handle_, $buf);
                    $response = fgets($this->handle_);

                    if ($write == strlen($buf) && $response{0} == '+') {
                        $this->host_ = $proxyHost;
                        $this->port_ = $proxyPort;

                        $this->useConnectPoolFlag = true;
                        return;
                    }
                }

                $this->handle_ = false;
            }
        }

        $this->useConnectPoolFlag = false;
        return parent::open();
    }
}
