<?php
/**
* CURL请求类文件
*
* 用于模拟 HTTP 请求。
* @author  HaoLin<haolinbird@163.com>
* @version 1.0
* @since   1.0
*/

/**
 * Class curl
 *
 * @author haolin
 */
class curl
{
    protected $_useragent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1';
    protected $_url;
    protected $_followlocation;
    protected $_timeout;
    protected $_maxRedirects;
    protected $_cookieFileLocation = './cookie.txt';
    protected $_post;
    protected $_postFields;
    protected $_referer ="http://www.google.com";

    protected $_session;
    protected $_response;
    protected $_includeHeader;
    protected $_noBody;
    protected $_status;
    protected $_binaryTransfer;
    public    $authentication = 0;
    public    $auth_name      = '';
    public    $auth_pass      = '';

    /**
     * 构造函数
     *
     * @access public
     * @param boolean $use 使用
     * @since 1.0
     */
    public function __construct($url, $followlocation = true, $timeOut = 30, $maxRedirecs = 4, $binaryTransfer = false, $includeHeader = false, $noBody = false)
    {
        $this->_url = $url;
        $this->_followlocation = $followlocation;
        $this->_timeout = $timeOut;
        $this->_maxRedirects = $maxRedirecs;
        $this->_noBody = $noBody;
        $this->_includeHeader = $includeHeader;
        $this->_binaryTransfer = $binaryTransfer;

        $this->_cookieFileLocation = dirname(__FILE__).'/cookie.txt';
    }

    /**
     * 添加 Auth 认证
     *
     * @access public
     * @param boolean $use 使用
     * @since 1.0
     */
    public function useAuth($use)
    {
        $this->authentication = 0;
        if ($use == true) $this->authentication = 1;
    }

    /**
     * 设置认证账号
     *
     * @access public
     * @param string $name 认证账号
     * @since 1.0
     */
    public function setName($name)
    {
        $this->auth_name = $name;
    }

    /**
     * 设置认证密码
     *
     * @access public
     * @param string $pass 认证密码
     * @since 1.0
     */
    public function setPass($pass)
    {
        $this->auth_pass = $pass;
    }

    /**
     * 设置请求来源
     *
     * @access public
     * @param string $referer 请求来源
     * @since 1.0
     */
    public function setReferer($referer)
    {
        $this->_referer = $referer;
    }

    /**
     * 设置COOKIE文件存储路径来源
     *
     * @access public
     * @param string $path 文件存储路径
     * @since 1.0
     */
    public function setCookiFileLocation($path)
    {
        $this->_cookieFileLocation = $path;
    }

    /**
     * 设置 POST 请求
     *
     * @access public
     * @param string $postFields POST请求参数
     * @since 1.0
     */
     public function setPost ($postFields)
     {
        $this->_post = true;
        $this->_postFields = $postFields;
     }

     /**
     * 设置 UserAgent
     *
     * @access public
     * @param string $userAgent POST请求参数
     * @since 1.0
     */
     public function setUserAgent($userAgent)
     {
         $this->_useragent = $userAgent;
     }

     /**
     * 发起请求
     *
     * @access public
     * @param string $userAgent POST请求参数
     * @since 1.0
     */
     public function request($url = 'nul', $header = [], $params = [])
     {
         if ($url != 'nul') {
             $this->_url = $url;
         }

         if ($params) {
             $this->_url = '?'. http_build_query($params);
         }

         $s = curl_init();

         // 设置请求地址
         curl_setopt($s, CURLOPT_URL, $this->_url);

         // 设置请求头信息
         curl_setopt($s, CURLOPT_HTTPHEADER, $header);

         // 设置请求超时时间
         curl_setopt($s, CURLOPT_TIMEOUT, $this->_timeout);

         // 指定最多的 HTTP 重定向次数，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的
         curl_setopt($s, CURLOPT_MAXREDIRS, $this->_maxRedirects);
         // TRUE 时将会根据服务器返回 HTTP 头中的 "Location: " 重定向。（注意：这是递归的，"Location: " 发送几次就重定向几次，除非设置了 CURLOPT_MAXREDIRS，限制最大重定向次数。）。
         curl_setopt($s, CURLOPT_FOLLOWLOCATION, $this->_followlocation);
         
         // 为TRUE时， 将curl_exec()获取的信息以字符串返回，而不是直接输出
         curl_setopt($s, CURLOPT_RETURNTRANSFER, true);

         // 连接结束后，比如，调用 curl_close 后，保存 cookie 信息的文件
         curl_setopt($s, CURLOPT_COOKIEJAR, $this->_cookieFileLocation);
         // 包含 cookie 数据的文件名，cookie 文件的格式可以是 Netscape 格式，或者只是纯 HTTP 头部风格，存入文件。如果文件名是空的，不会加载 cookie，但 cookie 的处理仍旧启用
         curl_setopt($s, CURLOPT_COOKIEFILE, $this->_cookieFileLocation);

         if ($this->authentication == 1) {
             curl_setopt($s, CURLOPT_USERPWD, $this->auth_name.':'.$this->auth_pass);
         }

         // FALSE 禁止 cURL 验证对等证书（peer's certificate)
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

         if ($this->_post) {
             curl_setopt($s,CURLOPT_POST,true);
             curl_setopt($s,CURLOPT_POSTFIELDS,$this->_postFields);

         }

         if ($this->_includeHeader) {
             curl_setopt($s,CURLOPT_HEADER,true);
         }

         if($this->_noBody)
         {
             curl_setopt($s,CURLOPT_NOBODY,true);
         }

         curl_setopt($s,CURLOPT_USERAGENT,$this->_useragent);
         curl_setopt($s,CURLOPT_REFERER,$this->_referer);

         $this->_response = curl_exec($s);
         $this->_status = curl_getinfo($s,CURLINFO_HTTP_CODE);
         curl_close($s);
    }

    /**
     * 获取响应结果状态码
     *
     * @access public
     * @return integer
     */
    public function getHttpStatus()
    {
        return $this->_status;
    }

    /**
     * 获取响应结果
     *
     * @access public
     * @return string
     */
    public function __tostring()
    {
        return $this->_response;
    }
}
