<?php
/**
 * 云账户身份验证.
 *
 * @author qiangd <qiangd@jumei.com>
 */

namespace Utils\YunZhangHu;

use Config;

/**
 * Create at 2019年12月4日 by qiangd <qiangd@jumei.com>.
 */
class IdentityVerify extends \Utils\Singleton
{

    protected static $dealerId = '';

    /**
     * Get instance of the derived class.
     *
     * @return \Utils\YunZhangHu\IdentityVerify
     */
    public static function instance()
    {
        self::$dealerId = \Config\YunZhangHu::DEALER_ID;
        return parent::instance();
    }

    /**
     * 加密请求数据.
     *
     * @param array $data 要加密的数据.
     *
     * @return string
     */
    protected static function encodeData($data)
    {
        $desUtils = DesUtils::instance();
        $result = $desUtils->encrypt(json_encode($data));
        return $result;
    }

    /**
     * 身份证实名认证 - 2要素.
     *
     * @param array   $param 请求包参数.
     * <pre>
     * array(
     *   'trans_id' => 1,
     *   'acc_no' => '6217003810002326976', // 银行卡号
     *   'id_card' => '511081198304210214', // 微分证号.
     *   'id_holder' => '董强', // 姓名.
     *   'mobile' => '18108080402', // 手机号.
     * )
     * </pre>.
     * @param integer $type  认证类型 2|二要素 4|四要素 其它默认走lh要素.
     *
     * @throws \Exception 异常信息.
     *
     * @return array
     */
    public function auth($param, $type = 0)
    {
        $res = array();
        try {
            $jsonRes = '';
            switch ($type) {
                case 2:
                    $jsonRes = $this->idCardAuth($param);
                    break;
                case 3:
                    $jsonRes = $this->bankCardSimple($param);
                    break;
                case 4:
                    $jsonRes = $this->bankCard($param);
                    break;
                default:
                    throw new \Exception("错误的认证类型");
            }
            $res = json_decode($jsonRes, true);
            \Utils\Log\Logger::instance()->log($res);
        } catch (\Exception $ex) {
            \Utils\Log\Logger::instance()->log($ex);
            throw $ex;
        }
        return $res;
    }

    /**
     * 身份证实名认证 - 银行卡4要素.
     *
     * @param array $param 请求包参数.
     * <pre>
     * array(
     *   'trans_id' => 1,
     *   'acc_no' => '6217003810002326976', // 银行卡号.
     *   'id_card' => '511081198304210214', // 身份证号.
     *   'id_holder' => '董强', // 姓名.
     *   'mobile' => '18108080402', // 手机号.
     * )
     * </pre>.
     *
     * @throws \Exception 异常.
     *
     * @return array
     */
    protected function bankCard($param)
    {
        $requestId = isset($param['trans_id']) ? $param['trans_id'] : '';
        // 初始化二要素的身份证信息.
        $data = array();
        $data['id_card'] = isset($param['id_card']) ? $param['id_card'] : '';
        $data['real_name'] = isset($param['id_holder']) ? $param['id_holder'] : '';
        $data['card_no'] = isset($param['acc_no']) ? $param['acc_no'] : '';
        $data['mobile'] = isset($param['mobile']) ? $param['mobile'] : '';
        $path = '/authentication/verify-bankcard-four-factor';
        $url = self::buildUrl($path);
        $postData = self::signData($data);
        $res = $this->request($postData, $url, $requestId);
        return $res;
    }

    /**
     * 身份证实名认证 - 银行卡3要素.
     *
     * @param array $param 请求包参数.
     * <pre>
     * array(
     *   'trans_id' => 1,
     *   'acc_no' => '6217003810002326976', // 银行卡号
     *   'id_card' => '511081198304210214', // 身份证号.
     *   'id_holder' => '董强', // 姓名.
     * )
     * </pre>.
     *
     * @throws \Exception 异常.
     *
     * @return array
     */
    protected function bankCardSimple($param)
    {
        $requestId = isset($param['trans_id']) ? $param['trans_id'] : '';
        // 初始化二要素的身份证信息.
        $data = array();
        $data['id_card'] = isset($param['id_card']) ? $param['id_card'] : '';
        $data['real_name'] = isset($param['id_holder']) ? $param['id_holder'] : '';
        $data['card_no'] = isset($param['acc_no']) ? $param['acc_no'] : '';
        $path = '/authentication/verify-bankcard-three-factor';
        $url = self::buildUrl($path);
        $postData = self::signData($data);
        $res = $this->request($postData, $url, $requestId);
        return $res;
    }

    /**
     * 身份证实名认证 - 2要素.
     *
     * @param array $param 请求包参数.
     * <pre>
     * array(
     *   'trans_id' => 1,
     *   'id_card' => '511081198304210214', // 身份证号.
     *   'id_holder' => '董强', // 姓名.
     * )
     * </pre>.
     *
     * @throws \Exception 异常.
     *
     * @return array
     */
    protected function idCardAuth($param)
    {
        $requestId = isset($param['trans_id']) ? $param['trans_id'] : '';
        // 初始化二要素的身份证信息.
        $data = array();
        $data['id_card'] = isset($param['id_card']) ? $param['id_card'] : '';
        $data['real_name'] = isset($param['id_holder']) ? $param['id_holder'] : '';
        $path = '/authentication/verify-id';
        $url = self::buildUrl($path);
        $postData = self::signData($data);
        $res = $this->request($postData, $url, $requestId);
        return $res;
    }

    /**
     * 签名请求参数data.
     *
     * @param array $data 要签名的数据.
     *
     * @return array
     */
    protected static function signData($data)
    {
        $data = self::encodeData($data);
        $requestData = array();
        $requestData['data'] = $data;
        $requestData['mess'] = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $requestData['timestamp'] = time();
        $signData = $requestData;
        $signData['key'] = \Config\YunZhangHu::APP_KEY;
        // 拼接签名字符串.
        $str = 'data=' . $signData['data'] . '&mess=' . $signData['mess'] . '&timestamp=' . $signData['timestamp'] . '&key=' . $signData['key'];
        $requestData['sign_type'] = 'sha256';
        $requestData['sign'] = hash_hmac("sha256", $str, $signData['key']);
        return $requestData;
    }

    /**
     * 构造请求url参数.
     *
     * @param string $path  请求路径.
     * @param array  $param 请求参数.
     *
     * @return string
     */
    protected static function buildUrl($path, $param = array())
    {
        // host主域名.
        $hostUrl = rtrim(\Config\YunZhangHu::API_HOST, '/') . '/' . ltrim($path,  '/');
        $url = $hostUrl;
        if (!empty($param)) {
            $url .= '?' . http_build_query($param);
        }
        return $url;
    }

    /**
     * 请求API.
     *
     * @param array|string $postData   Post请求参数.
     * @param string       $url        接口URL.
     * @param string       $requestId  请求单据Id.
     * @param string       $type       请求类类型 get|post|put.
     * @param array        $headerData Header数据.
     * @param integer      $timeout    请求超时时间.
     *
     * @throws \Exception 异常.
     *
     * @return string 接口返回结果字符.
     */
    protected function request($postData, $url, $requestId, $type = 'post', $headerData = array(), $timeout = 30)
    {
        $postDataString = '';
        $headers = array();
        $headers[] = 'request-id:' . $requestId;
        $headers[] = 'dealer-id:' . self::$dealerId;
        if (! empty($headerData)) {
            foreach ($headerData as $key => $v) {
                $headers[] = $key . ':' . $v;
            }
        }
        // 格式化参数.
        if (is_scalar($postData)) {
            $postDataString = $postData;
        } else {
            $postDataString = http_build_query($postData);
        }

        // 启动一个CURL会话.
        $curl = curl_init();
        // 要访问的地址.
        curl_setopt($curl, CURLOPT_URL, $url);
        // 对认证证书来源的检查.
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // 从证书中检查SSL加密算法是否存在.
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // 设置头文件的信息作为数据流输出.
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if (strtolower($type) != 'get') {
            // 发送一个常规的Post请求.
            curl_setopt($curl, CURLOPT_POST, true);
            // Post提交的数据包.
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postDataString);
        }
        // 设置超时限制防止死循环返回.
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        // 执行操作.
        $tmpInfo = curl_exec($curl);
        if (curl_errno($curl)) {
            // 捕抓异常.
            $tmpInfo = curl_error($curl);
            // 抛出异常信息.
            throw new \Exception($tmpInfo);
        }
        // 关闭CURL会话.
        curl_close($curl);
        // 返回数据.
        return $tmpInfo;
    }

}
