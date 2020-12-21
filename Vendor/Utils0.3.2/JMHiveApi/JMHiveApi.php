<?php
/**
 * 链接聚美的HIVE的API来异步查询数据.
 *
 * @author dengjing <jingd3@jumei.com>
 */

namespace Utils\JMHiveApi;

/**
 * Redis Locker.
 */
class JMHiveApi extends \Utils\Singleton
{

    /**
     * Get instance of the derived class.
     *
     * @return \Utils\JMHiveApi\JMHiveApi
     */
    public static function instance()
    {
        return parent::instance();
    }

    /**
     * 执行http/https请求.
     *
     * @param string  $url     请求地址.
     * @param array   $get     GET参数.
     * @param array   $post    POST参数.
     * @param integer $timeout 超时时间.
     *
     * @return string.
     */
    private function httpRequest($url, $get = array(), $post = array(), $timeout = 30)
    {
        if (!empty($get)) {
            $getString = http_build_query($get);
            $url .= '?' . $getString;
        }
        $ch = curl_init($url);
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($info['http_code'] != 200 || curl_error($ch) != '') {
            $result = false;
        } else {
            $result = $response;
        }
        curl_close($ch);
        return $result;
    }

    /**
     * 异步请求HIVE获取数据.
     *
     * @param string  $db         查询的数据库.
     * @param string  $sql        查询的SQL.
     * @param integer $interval   重试间隔(秒)
     * @param integer $retryTimes 重试次数.
     *
     * @return array|boolean
     */
    public function request($db, $sql, $interval = 5, $retryTimes = 10)
    {
        $host = \Config\JMHiveApiHosts::$host;
        $queryUrl = $host . '/hive/handleSqlApi';
        $downloadUrl = $host . '/phoenix/downloadApi';
        $username = \Config\JMHiveApiHosts::$username;
        $token = \Config\JMHiveApiHosts::$token;
        if (empty($sql) || empty($db)) {
            return false;
        }
        $queryRes = $this->httpRequest($queryUrl, array('db' => $db, 'username' => $username, 'token' => $token, 'sql' => $sql, 'format' => 'json'));
        if (!$queryRes) {
            return $queryRes;
        }
        $content = json_decode($queryRes, true);
        if ($content['code'] != 1) {
            return $content;
        }
        $i = 0;
        do {
            sleep($interval);
            $downloadRes = $this->httpRequest($downloadUrl, array('fileName' => $content['fileName'], 'username' => $username));
            if ($downloadRes) {
                $downloadRes = json_decode($downloadRes, true);
                if ($downloadRes['code'] == 1) {
                    $downloadRes['data'] = json_decode($downloadRes['data'], true);
                    break;
                }
            }
            $i++;
        } while ($i <= $retryTimes && $downloadRes['code'] == 0);
        return $downloadRes;
    }

}