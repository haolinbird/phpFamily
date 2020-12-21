<?php
/**
 * @author: dengjing<jingd3@jumei.com>.
 *
 */
namespace Weixin;

class H5 extends Base
{
    const JSAPI_TICKET_PREFIX = "jsapi_ticket_";
    const LOCK_REFRESH_JS_TICKET_PREFIX = "refresh_jsticket_lock_";

    /**
     * Get instance of the derived class.
     *
     * @param string $endpoint 配置的endpoint.
     *
     * @return \Weixin\H5
     */
    public static function instance($endpoint)
    {
        return parent::instance($endpoint);
    }

    /**
     * 文本格式的消息返回.
     *
     * @param string $toUserName   发送对象(用户的openid).
     * @param string $fromUserName 发送者.
     * @param string $text         文案.
     *
     * @return  mixed
     */
    public function textMessageResponse($toUserName, $fromUserName, $text)
    {
        $msg = array(
            'ToUserName' => "<![CDATA[{$toUserName}]]>",
            'FromUserName' => "<![CDATA[{$fromUserName}]]>",
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => "<![CDATA[{$text}]]>",
        );
        return $this->responseXml($msg);
    }

    /**
     * 图文格式的消息返回.
     *
     * @param string $toUserName   发送对象(用户的openid).
     * @param string $fromUserName 发送者.
     * @param array  $items        图文内容.
     *
     * @return  mixed
     */
    public function newsMessageResponse($toUserName, $fromUserName, $items)
    {
        $response = '';
        if (!empty($items)) {
            $msg = array(
                'ToUserName' => "<![CDATA[{$toUserName}]]>",
                'FromUserName' => "<![CDATA[{$fromUserName}]]>",
                'CreateTime' => time(),
                'MsgType' => "news",
                'ArticleCount' => count($items)
            );
            $articles = '';
            foreach ($items as $item) {
                $itemTpl = "<item><Title><![CDATA[%s]]></Title><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url><Description><![CDATA[%s]]></Description></item>";
                $articles .= sprintf($itemTpl, $item['title'], $item['pic_url'], $item['url'], $item['desc']);
            }
            $msg['Articles'] = $articles;
            $response = $this->responseXml($msg);
        }

        return $response;
    }

    /**
     * 接收普通消息.
     *
     * @param array $data 微信推送的数据.
     *
     * @return mixed
     */
    public function onMessage($data)
    {
        // 转发消息到客服系统
        $msg = array(
            'ToUserName' => "<![CDATA[{$data['FromUserName']}]]>",
            'FromUserName' => "<![CDATA[{$data['ToUserName']}]]>",
            'CreateTime' => time(),
            'MsgType' => '<![CDATA[transfer_customer_service]]>',
        );

        return $this->responseXml($msg);
    }

    /**
     * 输出xml格式数据.
     *
     * @param string|array $msg 消息内容.
     *
     * @return mixed
     */
    public function responseXml($msg)
    {
        $xml = \Weixin\Xml::array2Xml(array('xml' => $msg), true, 'utf-8', true);
        $response = $this->encryptionForH5($xml);
        return $response;
    }

    /**
     * 消息解密.
     *
     * @param string $postStr   微信事件内容.
     * @param string $signature 消息签名.
     * @param string $timestamp 时间戳.
     * @param string $nonce     随机字符.
     *
     * @return mixed
     */
    public function decryption($postStr, $signature, $timestamp, $nonce)
    {
        $crypt = new \Weixin\WXBizMsgCrypt($this->config['token'], $this->config['encoding_aes_key'], $this->config['app_id']);
        $crypt->decryptMsg($signature, $timestamp, $nonce, $postStr, $msg);
        return $msg;
    }

    /**
     * 消息加密.
     *
     * @param string $text 微信事件内容.
     *
     * @return mixed
     */
    public function encryption($text)
    {
        $crypt = new \Weixin\WXBizMsgCrypt($this->config['token'], $this->config['encoding_aes_key'], $this->config['app_id']);
        $timestamp = time();
        $nonce = $this->createNonceStr();
        $crypt->encryptMsg($text, $timestamp, $nonce, $msg);
        return $msg;
    }

    /**
     * 消息解密.
     *
     * @param string $postStr   微信事件内容.
     * @param string $signature 消息签名.
     * @param string $timestamp 时间戳.
     * @param string $nonce     随机字符.
     *
     * @return array
     */
    public function decryptionForH5($postStr, $signature, $timestamp, $nonce)
    {
        // 启用加解密功能（即选择兼容模式或安全模式）
        if (isset($this->config['is_safe_mode']) && $this->config['is_safe_mode'] && !empty($this->config['encoding_aes_key'])) {
            $msg = $this->decryption($postStr, $signature, $timestamp, $nonce);
        } else {
            $msg = $postStr;
        }
        return \Weixin\Xml::xml2Array($msg);
    }

    /**
     * 消息加密.
     *
     * @param string $text         微信事件内容.
     * @param string $msgSignature 消息签名.
     * @param string $timestamp    时间戳.
     * @param string $nonce        随机字符.
     *
     * @return mixed
     */
    public function encryptionForH5($text)
    {
        // 启用加解密功能（即选择兼容模式或安全模式）
        if (isset($this->config['is_safe_mode']) && $this->config['is_safe_mode'] && !empty($this->config['encoding_aes_key'])) {
            $msg = $this->encryption($text);
        } else {
            $msg = $text;
        }
        return $msg;
    }

    /**
     * 获取用户基本信息(公众号交互获取，请注意和网页授权区分开)
     *
     * @param string $appId 微信APPID
     * @param string $appSecret 微信AppSecret.
     * @param string $openid 微信openid
     *
     * @return mixed
     */
    public function getUserInfo($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info";
        $accessToken = $this->getAccesstoken();
        $json = $this->get($url, array('access_token' => $accessToken, 'openid' => $openid, 'lang' => "zh_CN"));
        $result = !empty($json) ? json_decode($json, true) : array();
        return $result;
    }

    /**
     * 根据access_token获取二维码ticket
     *
     * @param string  $content 内容
     * @param string  $type    二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
     * @param integer $expire  有效期，如果是临时类型需指定
     *
     * @return string ticket
     */
    public function getQRCodeTicket($content, $type = 'QR_SCENE', $expire = 2592000) {
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
        //post发送的数据
        switch ($type){
            case 'QR_SCENE':
                $params['expire_seconds'] = $expire;
                $params['action_name'] = $type;
                $params['action_info']['scene']['scene_id'] = $content;
                break;
            case 'QR_STR_SCENE':
                $params['expire_seconds'] = $expire;
                $params['action_name'] = $type;
                $params['action_info']['scene']['scene_str'] = $content;
                break;
            case 'QR_LIMIT_SCENE':
                $params['action_name'] = $type;
                $params['action_info']['scene']['scene_id'] = $content;
                break;
            case 'QR_LIMIT_STR_SCENE':
                $params['action_name'] = $type;
                $params['action_info']['scene']['scene_str'] = $content;
                break;
        }
        $result = $this->postjson($url, json_encode($params));
        return isset($result['ticket']) && $result['ticket'] ? $result['ticket'] : '';
    }

    /**
     * 获取二维码
     *
     * @param integer|string $content qrcode内容标识
     * @param string         $type    二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
     * @param integer        $expire  如果是临时，标识有效期
     *
     * @return mixed
     */
    public function getQRCode($content, $type = 'QR_SCENE', $expire = 2592000) {
        //获取ticket
        $ticket = $this->getQRCodeTicket($content, $type, $expire);
        if (!$ticket) {
            return false;
        }
        $url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$ticket}";
        //发送，取得图片数据
        return $this->get($url);
    }

    /**
     * 获取jsapi_ticket.
     *
     * @return mixed
     */
    public function getJsapiTicket()
    {
        $redisKey = self::JSAPI_TICKET_PREFIX . $this->config['app_id'];
        $ticket = $this->redis()->get($redisKey);
        if (!$ticket) {
            $ticket = $this->refreshJsapiTicket();
        }
        return $ticket;
    }

    /**
     * 调用微信接口刷新jsapi_ticket.
     *
     * @return mixed
     */
    public function refreshJsapiTicket()
    {
        $ticket = '';
        if (!\Utils\Locker\Redis::instance()->lock(self::LOCK_REFRESH_JS_TICKET_PREFIX, $this->config['app_id'], 10)) {
            return $ticket;
        }
        $redisKey = self::JSAPI_TICKET_PREFIX . $this->config['app_id'];
        $accessToken = $this->getAccesstoken();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket";

        $retry = 3;
        while ($retry--) {
            $json = $this->get($url, array('type' => 'jsapi', 'access_token' => $accessToken));
            $result = !empty($json) ? json_decode($json, true) : array();
            if ($result['errcode'] == 0 && !empty($result['ticket'])) {
                $ticket = $result['ticket'];
                $expire = intval($result['expires_in']) - 600;
                $this->redis()->setex($redisKey, $expire, $ticket);
                break;
            } elseif (!empty($result['errcode']) && $result['errcode'] == 40001) {
                $accessToken = $this->refreshAccessToken();       // 强制刷新access_token
            }
        }
        \Utils\Locker\Redis::instance()->unlock(self::LOCK_REFRESH_JS_TICKET_PREFIX, $this->config['app_id']);
        return $ticket;
    }

    /**
     * 获取微信jsapi的签名,附录1-JS-SDK使用权限签名算法https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421141115.
     *
     * @param string $url      当前访问的URL.
     * @param string $time     时间戳.
     * @param string $nonceStr 随机字符串.
     *
     * @return string 签名.
     */
    public function getJsapiSign($url, $time, $nonceStr)
    {
        $jsapiTicket = $this->getJsapiTicket();
        $string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$time}&url={$url}";
        $signature = sha1($string);
        return $signature;
    }

    /**
     * 获取微信jsapi的配置.
     *
     * @param string $url 当前访问的URL.
     *
     * @return array
     */
    public function getJsSdkConfig($url)
    {
        $time = time();
        $nonceStr = $this->createNonceStr();
        $config = [
            'appId' => $this->config['app_id'],
            'timestamp' => $time,
            'nonceStr' => $nonceStr,
            'signature' => $this->getJsapiSign($url, $time, $nonceStr),
        ];
        return $config;
    }

    /**
     * 获取模板列表.
     *
     * @return array
     */
    public function getAllPrivateTemplate()
    {
        $accessToken = $this->getAccesstoken();
        $url = 'https://api.weixin.qq.com/cgi-bin/template/get_all_private_template';
        return $this->getjson($url, ['access_token' => $accessToken]);
    }

    /**
     * 通过模板ID获取模板信息.
     *
     * @return array
     *
     * <pre>
     * array(
     *   'template_id' => 'Q-Q6x5Fo5JqShhECLP2uJCUhEHlm3f5-WD1hAFXq_oA',
     *   'title' => '活动完成通知',
     *   'primary_industry' => '',
     *   'deputy_industry' => '',
     *   'content' => '{{first.DATA}}
     *   活动名称：{{keyword1.DATA}}
     *   活动详情：{{keyword2.DATA}}
     *   {{remark.DATA}}',
     *   'example' => '',
     * )
     * </pre>
     */
    public function getTemplateById($templateId)
    {
        $templates = $this->getAllPrivateTemplate();
        $result = [];
        foreach ($templates['template_list'] as $template) {
            if ($template['template_id'] == $templateId) {
                $result = $template;
                break;
            }
        }
        return $result;
    }

    /**
     * 发送公众号的模板消息.
     *
     * @param string $openid      用户的openid.
     * @param string $templateId  模板ID.
     * @param string $url         模板跳转链接（海外帐号没有跳转能力）
     * @param array  $miniprogram 跳小程序所需数据，不需跳小程序可不用传该数据['appid' => 'foo', 'pagepath' => 'bar']
     * @param array  $data        模板数据['first' => ['value' => '购买成功', 'color' => '#000'], 'keyword1' => ['value' => '巧克力', 'color' => '#000'], 'remark' => ['value' => '巧克力', '欢迎再次购买！' => '#000']].
     *
     * @return array ['errcode' => 0, 'errmsg' => 'ok', 'msgid' => 599105126028083202]
     */
    public function sendTemplateMessage($openid, $templateId, $url = '', $miniprogram = [], $data = [])
    {
        $post = [
            'touser' => $openid,
            'template_id' => $templateId,
            'url' => $url,
            'miniprogram' => $miniprogram,
            'data' => $data,
        ];
        $json = json_encode($post, JSON_UNESCAPED_UNICODE);
        $accessToken = $this->getAccesstoken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $accessToken;
        return $this->postjson($url, $json);
    }

    /**
     * 获取用户列表,一次拉取调用最多拉取10000个关注者的OpenID，可以通过多次拉取的方式来满足需求
     *
     * @param string $nextopenid 第一个拉取的OPENID，不填默认从头开始拉取.
     *
     * @return array
     *
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140840
     */
    public function getUserList($nextopenid = '')
    {
        $accessToken = $this->getAccesstoken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get';
        return $this->getjson($url, ['access_token' => $accessToken, 'next_openid' => $nextopenid]);
    }

    /**
     * 批量获取用户基本信息(开发者可通过该接口来批量获取用户基本信息。最多支持一次拉取100条。).
     *
     * @param string $openids 微信openids
     *
     * @return array
     *
     * @link https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839
     */
    public function batchGetUser($openids)
    {
        $accessToken = $this->getAccesstoken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=' . $accessToken;
        $postData = array('user_list' => array());
        foreach ($openids as $openid) {
            $tmp = array(
                'openid' => $openid,
                'lang' => 'zh_CN',
            );
            $postData['user_list'][] = $tmp;
        }
        $postDataJson = json_encode($postData);
        return $this->postjson($url, $postDataJson);
    }

    /**
     * 获取文章列表.
     *
     * @param string  $type    类型.news
     * @param integer $offset  偏移量.
     * @param integer $count   条数.
     *
     * @return array
     */
    public function getNewsList($type = 'news', $offset = 0, $count = 10)
    {
        $accesstoken = $this->getAccesstoken();
        $url = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=' . $accesstoken;
        $post = array(
            'type' => $type,
            'offset' => $offset,
            'count' => $count,
        );
        $txt = json_encode($post, JSON_UNESCAPED_UNICODE);
        return $this->postjson($url, $txt);
    }

    /**
     * 通过文章标题查询文章信息(不包含内容).
     *
     * @param array   $titles  需要查找的标题数组.
     * @param integer $offset  偏移量.
     * @param integer $count   查询数量.
     *
     * @return array
     */
    public function getNewsByTitle(array $titles, $offset = 0, $count = 10)
    {
        $news = $this->getNewsList('news', $offset, $count);
        $filterNews = array_filter($news['item'], function($v) use ($titles) {
           foreach ($v['content']['news_item'] as $item) {
                foreach ($titles as $t) {
                    if (mb_strpos($item['title'], $t) !== false) {
                        return true;
                    }
                }
            }
            return false;
        });
        foreach ($filterNews as &$each) {
            foreach ($each['content']['news_item'] as &$one) {
                unset($one['content']);
            }
            unset($one);
        }
        unset($each);
        return $filterNews;
    }

}
