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

        // 使用统一平台报警
        if (self::sendAlarm($data, $content, $forceNormalAlarm)) {
            return true;
        }

        // 最后才使用手机号报警
        $param = PHPServerConfig::get('workers.Monitor.framework.param');
        $url = PHPServerConfig::get('workers.Monitor.framework.url');
        foreach ($phone_array as $phone) {
            $data['phone'] = $phone;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge(array('num' => $phone, 'content' => $content), $param)));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            ServerLog::add('send phone:' . $phone . ' msg:' . $content . ' send_ret:' . var_export(curl_exec($ch), true));
        }
    }

    public static function sendAlarm($data, $content = null, $forceNormalAlarm = true)
    {
        $use_unified_alarm = false;

        try {
            $use_unified_alarm = DoveClient\Config::get('RPC.UseUnifiedAlarm', true);
        } catch (Exception $e) {
            ServerLog::add("获取 dovekey [use_unified_alarm]失败，错误信息: " . $e->getMessage());
        }

        // 某些case不能使用统一报警平台（比如ip获取失败了,统一报警也没法反推项目，可能报警失败）
        if ($use_unified_alarm && !$forceNormalAlarm) {
            return self::sendUnifiedAlarm($data, $content);
        }
        return false;
    }

    public static function sendUnifiedAlarm($data, $content) {
        try {
            $service_info = DoveClient\Config::get('RPC.UnifiedAlarmService', true);
        } catch (\Exception $e) {
            return false;
        }
        $url = sprintf("%s/interface/rest/api/v1/alarm/receive?token=%s&appkey=%s&host=%s&level=error&content=%s",
            $service_info['url'],
            $service_info['token'],
            $service_info['appkey'],
            $data['ip'],
            urlencode($content)
        );

		$opts = array(
            'http' => array(
                'timeout' => 2,
            )
		);
        $ctx = stream_context_create($opts);
        $res = file_get_contents($url, false, $ctx);
        ServerLog::add("发送内容到统一报警平台:{$content}。 发送结果: $res");
        return true;
    }
}
