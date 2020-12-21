<?php
/**
 * @author: dengjing<jingd3@jumei.com>.
 *
 */
namespace Weixin;

class Applet extends Base
{

    /**
     * Get instance of the derived class.
     *
     * @param string $endpoint 配置的endpoint.
     *
     * @return \Weixin\Applet
     */
    public static function instance($endpoint)
    {
        return parent::instance($endpoint);
    }

    /**
     * 登录凭证校验, 通过 wx.login() 接口获得临时登录凭证 code 后传到开发者服务器调用此接口完成登录流程.
     *
     * @param string $jscode 登录时获取的code.
     *
     * @return array
     * @link https://developers.weixin.qq.com/miniprogram/dev/api/open-api/login/code2Session.html
     */
    public function jscode2session($jscode)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        return $this->getjson($url, ['appid' => $this->config['app_id'], 'secret' => $this->config['app_secret'], 'js_code' => $jscode, 'grant_type' => 'authorization_code']);
    }

    /**
     * 解密 UserInfo.
     *
     * @param string $sessionKey    SessionKey.
     * @param string $iv            Iv.
     * @param string $encryptedData Data.
     *
     * @return mixed
     */
    public function decryptUserInfo($sessionKey, $iv, $encryptedData)
    {
        $r = "";
        $pc = new \Weixin\WXBizDataCryptApplet($this->config['app_id'], $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $r);
        $r = json_decode($r, true);

        $result = array();
        if ($errCode == 0 && is_array($r) && !empty($r) && !empty($r['openId'])) {
            // 统一格式
            $result = array(
                'openid' => $r['openId'],
                'nickname' => $r['nickName'],
                'sex' => $r['gender'],
                'city' => $r['city'],
                'headimgurl' => $r['avatarUrl'],
                'unionid' => isset($r['unionId']) ? $r['unionId'] : '',
            );
        }
        return $result;
    }

    /**
     * 解密微信绑定的手机号.
     *
     * @param string $sessionKey    SessionKey.
     * @param string $iv            Iv.
     * @param string $encryptedData Data.
     *
     * @return mixed
     */
    public function decryptPhoneNumber($sessionKey, $iv, $encryptedData)
    {
        $r = "";
        $pc = new \Weixin\WXBizDataCryptApplet($this->config['app_id'], $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $r);
        $r = json_decode($r, true);

        if ($errCode == 0 && is_array($r) && !empty($r)) {
            return preg_match("/^\d+$/", $r['phoneNumber']) ? $r['phoneNumber'] : $r['purePhoneNumber'];
        }
        return false;
    }

    /**
     * 发送模板消息.
     *
     * @param array $params 发送信息.
     *
     * @return array
     */
    public function sendTemplateMsg($params)
    {
        $accessToken = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$accessToken}";
        return $this->postjson($url, $params);
    }

}
