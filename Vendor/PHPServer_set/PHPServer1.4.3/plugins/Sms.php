<?php

/**
 * 短信报警插件
 *
 * @author xuodongw <xudongw@jumei.com>
 *
 */

class Sms
{
    /**
     * 发送短信
     *
     * @param int $phone_num
     * @param string $content
     *
     * @return void
     */
    public static function sendSms($projectName, $data, $content, $forceNormalAlarm = false)
    {
        $alarm_phone_config = array();
        // 短信告警，先尝试从config/other.php获得该项目接收告警手机
        if (is_file(__DIR__ . '/../config/other.php')) {
            include __DIR__ . '/../config/other.php';
            if (isset($rpc_alarm_config) && is_array($rpc_alarm_config)) {
                $alarm_phone_config = $rpc_alarm_config;
            }
        }
        $phone_array = array();
        if ($projectName) {
            $phone_array = isset($alarm_phone_config[$projectName]) ? $alarm_phone_config[$projectName] : array();
        }

        // 没得到告警手机，则用main.php中的配置
        if (!$phone_array) {
            $phone_array = explode(',', PHPServerConfig::get('workers.Monitor.framework.phone'));
        }
        // 去掉空的手机
        foreach ($phone_array as $key => $value) {
            if (empty($value)) {
                unset($phone_array[$key]);
            } else {
                $phone_array[$key] = trim($value);
            }
        }
        // 告警参数
        $param = PHPServerConfig::get('workers.Monitor.framework.param');
        $url = PHPServerConfig::get('workers.Monitor.framework.url');
        foreach ($phone_array as $phone) {
            $data['phone'] = $phone;
            if (!self::sendAlarm($data, $content, $forceNormalAlarm)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge(array('num' => $phone, 'content' => $content), $param)));
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                ServerLog::add('send phone:' . $phone . ' msg:' . $content . ' send_ret:' . var_export(curl_exec($ch), true));

            }
        }
    }

    public static function sendAlarm($data, $content = null, $forceNormalAlarm = true)
    {
        $use_unified_alarm = false;

        try {
            $use_unified_alarm = DoveClient\Config::get('RPC.UseUnifiedAlarm', true);
        } catch (Exception $e) {
            ServerLog::add("get dove key use_unified_alarm failed with err: " . $e->getMessage());
        }

        // 某些case不能使用统计报警平台（比如ip获取失败了,统一报警也没法反推项目，可能报警失败）
        if ($use_unified_alarm && !$forceNormalAlarm) {
            return self::sendUnifiedAlarm($data, $content);
        }

        $alarm_uri = PHPServerConfig::get('workers.Monitor.framework.alarm_uri');
        if (!$alarm_uri) {
            ServerLog::add("workers.Monitor.framework.alarm_uri empty");
            return false;
        }
        $client = stream_socket_client($alarm_uri, $err_no, $err_msg, 1);
        if (!$client) {
            ServerLog::add("sendAlarm fail . $err_msg");
            return false;
        }
        stream_set_timeout($client, 1);
        $buffer = json_encode($data);
        $send_len = fwrite($client, $buffer);
        if ($send_len !== strlen($buffer)) {
            ServerLog::add("sendAlarm fail . fwrite return " . var_export($send_len, true));
            return false;
        }
        //fread($client, 8196);
        ServerLog::add($buffer);
        return true;
    }

    public static function sendUnifiedAlarm($data, $content)
    {
        try {
            $service_info = DoveClient\Config::get('RPC.UnifiedAlarmService', true);
        } catch (\Exception $e) {
            return false;
        }
        $url = sprintf("%s/interface/rest/api/v1/alarm/receive?token=%s&appkey=%s&host=%s&level=fatal&content=%s",
            $service_info['url'],
            $service_info['token'],
            $service_info['appkey'],
            $data['ip'],
            urlencode($content)
        );
        $res = file_get_contents($url);
        ServerLog::add("send unified alarm :$content. return: $res");
        return true;
    }
}