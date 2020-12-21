<?php
/**
 * XinYan.
 *
 * @author dengjing <jingd3@jumei.com>
 */

namespace Utils\VerifyBankCard;

/**
 * XinYan.
 */
class XinYan extends \Utils\Singleton
{

    /**
     * Get instance of the derived class.
     *
     * @return \Utils\VerifyBankCard\XinYan
     */
    public static function instance()
    {
        return parent::instance();
    }

    /**
     * 新颜-银行卡四要素短信认证申请.
     *
     * @param array $params 请求参数.
     * <pre>
     * array(
     *   'trans_id' => 1,
     *   'acc_no' => '6217003810002326976',
     *   'id_card' => '511081198304210214',
     *   'id_type' => '0' // 不传默认用0,即身份证.
     *   'id_holder' => '邓靖',
     *   'mobile' => '18108080402',
     * )
     * </pre>
     *
     * @return array
     */
    public function auth(array $params)
    {
        try {
            $transId = $params['trans_id'];
            $accNo = $params['acc_no'];
            $idCard = $params['id_card'];
            $idType = isset($params['id_type']) ? $params['id_type'] : '0'; // 证件类型0=身份证,1=军官证,2=护照, 不传默认使用身份证.
            $idHolder = $params['id_holder'];
            $cardType = isset($params['card_type']) ? $params['card_type'] : ''; // 借贷标示,101=借记卡,102=信用卡.
            $verifyElement = isset($params['verify_element']) ? $params['verify_element'] : '1234'; // 验证要素:12:两要素（银行卡号 + 姓名）,123:三要素（银行卡号 + 姓名 + 身份证号）,1234:四要素（银行卡号 + 姓名 + 身份证号 + 银行卡预留手机号）
            $sceneCode = isset($params['scene_code']) ? $params['scene_code'] : '99'; // 详见 : https://docs.xinyan.com/docs/bankcard_auth/fb?token=vVL8n8ZAGs4N
            $mobile = $params['mobile'];
            $customerName = isset($params['customer_name']) ? $params['customer_name'] : '';
            $request = array(
                'trans_id' => $transId,
                'member_id' => \Config\XinYan::MEMBER_ID,
                'terminal_id' => \Config\XinYan::TERMINAL_ID,
                'trade_date' => date('YmdHis'),
                'acc_no' => $accNo,
                'id_card' => $idCard,
                'id_type' => $idType,
                'id_holder' => $idHolder,
                'mobile' => $mobile,
                'verify_element' => $verifyElement,
                'scene_code' => $sceneCode,
                'customer_name' => $customerName,
            );
            if (!empty($cardType)) {
                $request['card_type'] = $cardType;
            }
            $post = $this->getPostData($request);
            $response = $this->request($post, \Config\XinYan::API_HOST . '/bankcard/v3/auth');
            $res = json_decode($response, true);
            \Utils\Log\Logger::instance()->log($res);
            return $res;
        } catch (\Exception $e) {
            \Utils\Log\Logger::instance()->log($e);
            throw $e;
        }
    }

    /**
     * 新颜-银行卡四要素短信认证申请.
     *
     * @param array $params 请求参数.
     * <pre>
     * array(
     *   'trans_id' => 1,
     *   'acc_no' => '6217003810002326976',
     *   'id_card' => '511081198304210214',
     *   'id_holder' => '邓靖',
     *   'mobile' => '18108080402',
     *   'rsa_path' => '',
     * )
     * </pre>
     *
     * @return array
     */
    public function authsms(array $params)
    {
        try {
            $transId = $params['trans_id'];
            $accNo = $params['acc_no'];
            $idCard = $params['id_card'];
            $idHolder = $params['id_holder'];
            $cardType = isset($params['card_type']) ? $params['card_type'] : '';
            $industryType = isset($params['industry_type']) ? $params['industry_type'] : 'A7';
            $mobile = $params['mobile'];
            $request = array(
                'member_id' => \Config\XinYan::MEMBER_ID,
                'terminal_id' => \Config\XinYan::TERMINAL_ID,
                'id_card' => $idCard,
                'id_holder' => $idHolder,
                'acc_no' => $accNo,
                'mobile' => $mobile,
                'trans_id' => $transId,
                'trade_date' => date('YmdHis'),
                'industry_type' => $industryType,
            );
            if (!empty($cardType)) {
                $request['card_type'] = $cardType;
            }
            $post = $this->getPostData($request);
            $response = $this->request($post, \Config\XinYan::API_HOST . '/bankcard/v1/authsms');
            $res = json_decode($response, true);
            \Utils\Log\Logger::instance()->log($res);
            return $res;
        } catch (\Exception $e) {
            \Utils\Log\Logger::instance()->log($e);
            throw $e;
        }
    }

    /**
     * 新颜-银⾏行行卡四要素短信认证确认.
     *
     * @param array $params 请求参数.
     * <pre>
     * array(
     *   'trade_no' => '201804111149253436685900',
     *   'sms_code' => '3847',
     * )
     * </pre>
     *
     * @return array
     */
    public function authconfirm(array $params)
    {
        try {
            $tradeNo = $params['trade_no'];
            $smsCode = $params['sms_code'];
            $request = array(
                'member_id' => \Config\XinYan::MEMBER_ID,
                'terminal_id' => \Config\XinYan::TERMINAL_ID,
                'trade_no' => $tradeNo,
                'sms_code' => $smsCode,
            );
            $post = $this->getPostData($request);
            $response = $this->request($post, \Config\XinYan::API_HOST . '/bankcard/v1/authconfirm');
            $res = json_decode($response, true);
            \Utils\Log\Logger::instance()->log($res);
            return $res;
        } catch (\Exception $e) {
            \Utils\Log\Logger::instance()->log($e);
            throw $e;
        }
    }

    /**
     * 获取POST的数据.
     *
     * @param array $request 请求参数.
     *
     * @return array
     */
    public function getPostData($request)
    {
        $json = json_encode($request);
        $jsonData = str_replace("\\/", "/",$json);
        $pfxPath = realpath(\Config\XinYan::RSA_PATH) . DIRECTORY_SEPARATOR . \Config\XinYan::PFX_FILE;
        $cerPath = realpath(\Config\XinYan::RSA_PATH) . DIRECTORY_SEPARATOR . \Config\XinYan::CER_FILE;
        $pfxPwd = \Config\XinYan::PASSWORD;
        $BFRsa = new \Utils\VerifyBankCard\BFRSA($pfxPath, $cerPath, $pfxPwd,false); //实例化加密类。
        $data = $BFRsa->encryptedByPrivateKey($jsonData);
        $post = array(
            'member_id' => \Config\XinYan::MEMBER_ID,
            'terminal_id' => \Config\XinYan::TERMINAL_ID,
            'data_type' => 'json',
            'data_content' => $data
        );
        return $post;
    }

    /**
     * 请求API.
     *
     * @param array  $post 请求参数.
     * @param string $url  接口URL.
     *
     * @return string 接口返回结果字符.
     * @throws \Exception
     */
    public function request($post, $url)
    {
        $postData = $post;
        $postDataString = http_build_query($postData); //格式化参数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postDataString); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环返回
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            $tmpInfo = curl_error($curl); //捕抓异常
            throw new \Exception($tmpInfo);
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }

    /**
     * 新颜-实名认证.
     *
     * @param array $params 请求参数.
     * <pre>
     * array(
     *   'trans_id' => 1,
     *   'id_card' => '511081198304210214',
     *   'id_holder' => '邓靖',
     * )
     * </pre>
     *
     * @return array
     */
    public function idCardAuth(array $params)
    {
        try {
            $transId = $params['trans_id'];
            $idCard = $params['id_card'];
            $idHolder = $params['id_holder'];
            $request = array(
                'trans_id' => $transId,
                'member_id' => \Config\XinYan::MEMBER_ID,
                'terminal_id' => \Config\XinYan::TERMINAL_ID,
                'trade_date' => date('YmdHis'),
                'id_card' => $idCard,
                'id_holder' => $idHolder,
            );
            $post = $this->getPostData($request);
            $response = $this->request($post, \Config\XinYan::API_HOST . '/idcard/v2/auth');
            $res = json_decode($response, true);
            \Utils\Log\Logger::instance()->log($res);
            return $res;
        } catch (\Exception $e) {
            \Utils\Log\Logger::instance()->log($e);
            throw $e;
        }
    }

}
