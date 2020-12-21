<?php
/**
 * Send message by Wechat.
 *
 * @author quans <quans@jumei.com>
 */

namespace ProductUtils;

/**
 * Send message Class.
 */
Class Wechat {

    private static $instance;

    /**
     * 获取静态对象.
     *
     * @return Cart
     */
    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(static::$instance[$class])) {
            self::$instance[$class] = new self();
        }
        return self::$instance[$class];
    }

    /**
     * Get wechat message url.
     *
     * @return string
     */
    private static function getWechatUrl()
    {
        return isset(\Config\Config::$wechatUrl) ? \Config\Config::$wechatUrl : '';
    }

    /**
     * Get wechat user of system.
     *
     * @return string
     */
    private static function getWechatUser()
    {
        return isset(\Config\Config::$wechatUser) ? \Config\Config::$wechatUser : '';
    }

    /**
     * Get wechat key of system.
     *
     * @return string
     */
    private static function getWechatKey()
    {
        return isset(\Config\Config::$wechatKey) ? \Config\Config::$wechatKey : '';
    }

    /**
     * Send wechat info.
     *
     * @param string $userId  User_id.
     * @param string $message Message.
     *
     * @return string
     */
    public function doWarning($userId, $message)
    {
        $params = self::getParams(array('userId' => $userId, 'message' => $message));
        $check = self::checkParams($params);
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        return $this->weixinBeeper($params);
    }

    /**
     * Send wechat info.
     *
     * @param string $userId  User_id.
     * @param string $message Message.
     * @param array  $params  Contain url,user,key.
     *
     * @return string
     */
    public function doWarningWithParams($userId, $message, $params = array('url' => '', 'user' => '', 'key' => ''))
    {
        $params = array_merge($params, array('userId' => $userId, 'message' => $message));
        $check = self::checkParams($params);
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        return $this->weixinBeeper($params);
    }

    /**
     * Do send core logic.
     *
     * @param array $params Send info.
     *
     * @return array
     */
    private function weixinBeeper($params)
    {

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
            return \ProductUtils\Util::getArrayErrorInfo($message, compact($errno));
        }

        $result = json_decode($info, true);
        if (empty($result)) {
            return \ProductUtils\Util::getArrayErrorInfo('json_decode failed!', compact($errno));
        }

        if ($result['code'] != 0) {
            return \ProductUtils\Util::getArrayErrorInfo($result['msg'], array('source_code' => $result['code'], 'data' => $result['data']));
        }

        return \ProductUtils\Util::getArraySuccessInfo();
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
        // http://weixin.beeper.int.jumeicd.com/Message/Beep/byUser?user_id=xxxx&message=helloworld&user=tester&key=444444.
        return "{$params['url']}Message/Beep/byUser?user_id={$params['userId']}&message={$params['message']}&user={$params['user']}&key={$params['key']}";
    }

    /**
     * Check Params.
     *
     * @param array $params Param.
     *
     * @return array
     */
    public function checkParams($params)
    {

        $check = \ProductUtils\Util::isString($params['url'], 'url');
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        $check = \ProductUtils\Util::isString($params['user'], 'user');
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        $check = \ProductUtils\Util::isString($params['key'], 'key');
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        $check = \ProductUtils\Util::isString($params['userId'], 'user_ids');
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        $check = \ProductUtils\Util::isString($params['message'], 'message');
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        $check = \ProductUtils\Util::checkStringLen($params['message'], 2048, 'message', 'lte');
        if (!\ProductUtils\Util::isSuccess($check['code'])) {
            return $check;
        }

        return \ProductUtils\Util::getArraySuccessInfo();
    }

    /**
     * Get wechat params.
     *
     * @param array $append Append data.
     *
     * @return array
     */
    private static function getParams($append)
    {
        $base = array(
            'url' => self::getWechatUrl(),
            'user' => self::getWechatUser(),
            'key' => self::getWechatKey(),
        );
        $append['message'] = urlencode($append['message']);

        return array_merge($base, $append);
    }


}