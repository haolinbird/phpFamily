<?php
/**
 * Class HandlerBase
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2020-12-24 10:28:30
 */

namespace Handler;

/**
 * HandlerBase.
 */
abstract class HandlerBase
{
    // 调用接口的用户
    public static $requestUser;
    // 当前请求的类名
    public static $requestClass;
    // 当前请求的方法名
    public static $requestMethod;
    // 当前请求的参数
    public static $requestParams;

    // 错误码列表
    private static $errcode;
    
    /**
     * 设置PHPSERVER回调函数,记录接口请求信息.
     *
     * @param integer $requestInfo 请求信息.
     *
     * @return void
     */
    public function setRequestInfo($requestInfo)
    {
        self::$requestUser   = isset($requestInfo['user']) ? $requestInfo['user'] : '';
        self::$requestClass  = isset($requestInfo['class']) ? $requestInfo['class'] : '';
        self::$requestMethod = isset($requestInfo['method']) ? $requestInfo['method'] : '';
        self::$requestParams = isset($requestInfo['params']) ? $requestInfo['params'] : array();
    }

    /**
     * Generate response json.
     *
     * @param string $responseSign 错误码对照表下标.
     * @param mixed  $data         返回数据.
     *
     * @return array
     */
    public function genResponses($responseSign, $data = null)
    {
        if (!self::$errcode) {
            self::$errcode = \Util\ErrCode::$responseBody;
        }

        if (isset(self::$errcode[$responseSign])) {
            return array_merge(self::$errcode[$responseSign], array('data' => $data));
        } else {
            return array_merge(self::$errcode['EXCEPTION'], array('data' => $data));
        }
    }
}
