<?php

/**
 * JMText协议
 */

namespace protocol;

use \Exception;

class JMText {
    protected $rpcSecret;
    protected $user;
    protected $secret;

    public function __construct($rpcSecretKey, $user, $secret){
        $this->rpcSecret    = $rpcSecretKey;
        $this->user         = $user;
        $this->secret       = $secret;
    }


    public function getPacket($class, $method, $params) {
        $data = array(
                'version' => '2.0',
                'user' => $this->user,
                'password' => md5($this->user . ':' . $this->secret),
                'timestamp' => microtime(true),
                'class' => $class,
                'method' => $method,
                'params' => $params,
        );

        $packet['data'] = json_encode($data);
        if (!$packet['data']) {
            throw new Exception(sprintf('无法用json_encode序列化数据：%s', var_export($data, true)));
        }
        $packet['signature'] = $this->encrypt($packet['data'], $this->rpcSecret);

        return $packet;
    }

    public function encode($data) {
        $data = $this->addGlobalContext($data);

        if (!$data = json_encode($data)) {
            throw new Exception(sprintf('无法用json_encode序列化数据：%s', var_export($data, true)));
        }

        $command = 'RPC';
        return sprintf("%d\n%s\n%d\n%s\n", strlen($command), $command, strlen($data), $data);
    }

    public function decode($data) {
        return substr($data, strpos($data, "\n") + 1, -1);
    }

    protected function addGlobalContext($data) {
        global $owl_context, $context;

        $context = !is_array($context) ? array() : $context;
        $owl_context_client = $owl_context;
        if(!empty($owl_context_client)) {
            $owl_context_client['app_name'] = defined('JM_APP_NAME') ? JM_APP_NAME : 'undefined';
        }
        $context['owl_context'] = json_encode($owl_context_client);
        $data['CONTEXT'] = $context;
        return $data;
    }

    /**
     * 请求数据签名.
     *
     * @param string $data   待签名的数据.
     * @param string $secret 私钥.
     *
     * @return string
     */
    protected function encrypt($data, $secret){
        return md5($data . '&' . $secret);
    }

}
