<?php
/**
 * Send message by sms.
 *
 * @author yuan min <miny1@jumei.com>
 */
namespace ProductUtils;

/**
 * Send message Class.
 */
Class Sms {
    private static $instance = array();
    public $smsUrl = 'http://sms.int.jumei.com'; // 线上使用url: sms.int.jumei.com // RD: http://192.168.69.133:8085
    public $key = 'product_system_983be676feaa62bede929dd8a90151bd'; // 正式环境KEY
    public $global = 1;
    public $encrypt = 0;
    public $channel = 'tencent';
    public $task = 'weixin_system_monitor';

    /**
     * 获取静态对象.
     * 
     * @param string $params Json.
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
     * @return string.
     */
    private function getSmsUrl()
    {
        return isset(\Config\Config::$smsUrl) ? \Config\Config::$smsUrl : $this->emailUrl;
    }
    
    /**
     * 获取channel.
     * 
     * @return string.
     */
    private function getSmsKey()
    {
        return isset(\Config\Config::$smsConfig['key']) ? \Config\Config::$smsConfig['key'] : $this->channel;
    }
    
    /**
     * 获取channel.
     * 
     * @return string.
     */
    private function getSmsChannel()
    {
        return isset(\Config\Config::$smsConfig['channel']) ? \Config\Config::$smsConfig['channel'] : $this->channel;
    }
    
    /**
     * 获取task.
     * 
     * @return string.
     */
    private function getSmsTask()
    {
        return isset(\Config\Config::$smsConfig['task']) ? \Config\Config::$smsConfig['task'] : $this->task;
    }
    
    /**
     * 发送邮件
     * 
     * @param array $param 收件人邮箱/业务方/邮件主题/邮件内容.
     * 
     * @return string JSON.
     */
    public function sendSms($param)
    {
        $params = json_decode($param, true);
        if (!is_array($params) || empty($params)) {
            return \ProductUtils\Util::getJsonErorrInfo('$params is empty!');
        }
        
        $data = $this->formatData($params);
        if (!empty($data['message'])) {
            return \ProductUtils\Util::getJsonErorrInfo($data['message']);
        }
        
        return $this->sendAction($data);
    }
    
    /**
     * 发送短信操作.
     * 
     * @param array $params Data.
     * 
     * @return string.
     */
    public function sendAction($params)
    {
        if (!is_array($params) || empty($params)) {
            return \ProductUtils\Util::getJsonErorrInfo('$data is empty!');
        }
        
        $message = '';
        $link = self::getLink($params);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $info = curl_exec($curl);

        if ($errno = curl_errno($curl)) {
            $message = curl_error($curl);
        }
        curl_close($curl);

        if (!empty($message)) {
            return \ProductUtils\Util::getJsonErorrInfo($message, compact($errno));
        }
        
        if ('ok' != $info) {
            return \ProductUtils\Util::getJsonErorrInfo($info, compact($errno));
        }
        
        return \ProductUtils\Util::getJsonSuccessInfo($info);
    }
    
    /**
     * Get request url.
     *
     * @param array $params Params.
     *
     * @return string
     */
    private static function getLink($params)
    {
        // curl "http://192.168.69.133:8085" -d "num=18580250064&key=example_5fb8fa158f&content=这是测试人员给自己的测试短信. 如果您意外收到该短信,敬请见谅.&global=1&encrypt=0&task=weixin_system_monitor"
        return "{$params['url']}?num={$params['num']}&key={$params['key']}&content={$params['content']}&global={$params['global']}&encrypt={$params['encrypt']}&task={$params['task']}";
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
        $reMoblie = '';
        if (empty($params['moblie'])) {
            return \ProductUtils\Util::getArrayErrorInfo('$params["moblie"] is empty!');
        } else {
            $moblie = explode(',', $params['moblie']);
            if (!$moblie) {
                return \ProductUtils\Util::getArrayErrorInfo('$params["moblie"] format error!');
            } else {
                foreach ($moblie as $mValue) {
                    if ($this->isMobile($mValue)) {
                        $reMoblie .= $mValue.',';
                    }
                }
            }
            
            if (empty($reMoblie)) {
                return \ProductUtils\Util::getArrayErrorInfo('$params["moblie"] format error!');;
            }
        }
        
        if (empty($params['content'])) {
            return \ProductUtils\Util::getArrayErrorInfo('$params["content"] is empty!');
        }
        $params['num'] = $reMoblie;
        $params['task'] = $this->getSmsTask();
        $params['channel'] = $this->getSmsChannel();
        $params['key'] = $this->getSmsKey();
        $params['global'] = $this->global;
        $params['encrypt'] = $this->encrypt;
        $params['url'] = $this->getSmsUrl();
        
        return $params;
    }
    
    /**
    * 验证手机号是否正确
    * @author honfei
    * @param number $mobile
    */
   public function isMobile($mobile)
    {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }
}