<?php
/**
 * Send message by email.
 *
 * @author yuan min <miny1@jumei.com>
 */
namespace ProductUtils;

/**
 * Send message Class.
 */
Class Email {
    private static $instance = array();
    public $emailUrl = 'https://dashboard.edm.int.jumei.com/rpc.php'; // 线上使用url: https://dashboard.edm.int.jumei.com/rpc.php // RD: 192.168.69.133
    public $version = '1.0';
    public $class = 'RpcEdm_CrmSystemEdmQueue';
    public $method = 'sendCrmMailQueue';
    public $emailKey = '{1BA09530-F9E6-478D-9965-7EB31A59537E}';
    public $emailUser = 'product';
    public $type = 'crm_';

    /**
     * 获取静态对象.
     *
     * @param string $params Json.
     * 
     * @return Email.
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    /**
     * 获取URL.
     * 
     * @param array $params 用户传入参数.
     * 
     * @return string.
     */
    private function getEmailUrl($params)
    {
        $rpc = 'rpc.php';
        $uri = $this->formatUri($params);
        $url = isset(\Config\Config::$edmEmailConfig['emailUrl']) ? \Config\Config::$edmEmailConfig['emailUrl'] : $this->emailUrl;
        $urls = strpos($url, $rpc) ? $url : $url.'/'.$rpc;
        return $urls.'?'.http_build_query($uri);
    }
    
    /**
     * 格式化uri
     * 
     * @return array.
     */
    public function formatUri($params)
    {
        $uri = array(
            'protocol' => 'json',
            'sessionId' => "",
            'user' => $this->getEmailUser(),
        );
        $uri['sign'] = md5($uri['protocol'] . $uri['sessionId'] . json_encode($params) . $this->getEmailKey());
        
        return $uri;
    }
    /**
     * 获取user.
     * 
     * @return string.
     */
    private function getEmailUser()
    {
        return isset(\Config\Config::$edmEmailConfig['emailUser']) ? \Config\Config::$edmEmailConfig['emailUser'] : $this->emailUser;
    }
    
    /**
     * 获取key.
     * 
     * @return string.
     */
    private function getEmailKey()
    {
        return isset(\Config\Config::$edmEmailConfig['emailKey']) ? \Config\Config::$edmEmailConfig['emailKey'] : $this->emailKey;
    }
    
    /**
     * 发送邮件
     * 
     * @param array $param 收件人邮箱/业务方/邮件主题/邮件内容.
     * 
     * @return string JSON.
     */
    public function sendMail($param)
    {
        $params = json_decode($param, true);
        if (!is_array($params) || empty($params)) {
            return \ProductUtils\Util::getJsonErorrInfo('$params is empty!');
        }
        $data = $this->formatData($params);
        if (!empty($data['message'])) {
            return \ProductUtils\Util::getJsonErorrInfo($data['message']);
        }
        
        return $this->curlSendMail($data);
    }
    
    /**
     * 格式化发送数据.
     * 
     * @param array $params 发送参数.
     * 
     * @return array.
     */
    public function formatData($params)
    {
        $reEmail = '';
        if (empty($params['emailAddress'])) {
            return \ProductUtils\Util::getArrayErrorInfo('$params["emailAddress"] is empty!');
        } else {
            $email = explode(',', $params['emailAddress']);
            if (!$email) {
                return \ProductUtils\Util::getArrayErrorInfo('$params["emailAddress"] format error!');
            } else {
                foreach ($email as &$eValue) {
                    if (filter_var($eValue, FILTER_VALIDATE_EMAIL)) {
                        $reEmail .= $eValue.',';
                    }
                }
            }
            
            if (empty($reEmail)) {
                return \ProductUtils\Util::getArrayErrorInfo('$params["emailAddress"] format error!');;
            }
        }
        
        if (empty($params['type'])) {
            return \ProductUtils\Util::getArrayErrorInfo('$params["type"] is empty!');
        } else {
            if ((!strstr($params['type'], 'system_')) && (!strstr($params['type'], 'crm_'))) {
                $params['type'] = $this->type.$params['type'];
            }
        }
        
        if (empty($params['subject'])) {
            return \ProductUtils\Util::getArrayErrorInfo('$params["subject"] is empty!');
        }
        
        if (empty($params['content'])) {
            return \ProductUtils\Util::getArrayErrorInfo('$params["content"] is empty!');
        }
        
        $param = array($reEmail,$params['type'],$params['subject'],$params['content']);
        
        return array(
            'version' => $this->version,
            'class' => $this->class,
            'method' => $this->method,
            'params' => $param,
            'options' => "",
            'id' => '0',
        );
    }
    
    /**
     * 发送邮件.
     * 
     * @param array $data Data.
     * 
     * @return string.
     */
    public function curlSendMail($data){ 
        // 模拟提交数据函数
        if (!is_array($data) || empty($data)) {
            return \ProductUtils\Util::getJsonErorrInfo('$data is empty!');
        }
        $ch = curl_init ();
        curl_setopt ($ch, CURLOPT_URL, $this->getEmailUrl($data)); // 要访问的地址
        curl_setopt ($ch, CURLOPT_POST, true ); // POST 发送数据
        $requestHeaders = array (
                'Content-Type: application/x-rm-rpc; charset=utf-8',
                'Host:dashboard.edm.int.jumei.com',
                'Connection: close', 
        ); 
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $requestHeaders );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt ( $ch, CURLOPT_HEADER, false );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt ( $ch, CURLOPT_FORBID_REUSE, true );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
        $tmpInfo = json_decode(curl_exec($ch), true); // 执行操作
        if (curl_errno($ch)) {
            return \ProductUtils\Util::getJsonErorrInfo(curl_error($ch));
        }
        curl_close($ch); // 关闭CURL会话
        if (true == $tmpInfo['result']) {
            return \ProductUtils\Util::getJsonSuccessInfo();
        } else {
            return \ProductUtils\Util::getJsonErorrInfo($tmpInfo['exception']);
        }
    }

}
