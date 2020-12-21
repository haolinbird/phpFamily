<?php
/**
 * Http Request.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace JMHttpClient;

use \JMHttpClient\Core\HttpPool;
use \JMHttpClient\Core\Connection;
use \JMHttpClient\Core\DataObject;

/**
 * Jm Http Request.
 */
class Request
{
    protected static $instances = array();
    protected $conn = NULL;

    /**
     * Get req instance.
     *
     * @param string $hostGroup Host Group.
     *
     * @return \JMHttpClient\Request
     */
    static public function instance($hostGroup = 'default')
    {
        if (isset(self::$instances[$hostGroup])) {
            return self::$instances[$hostGroup];
        }

        self::$instances[$hostGroup] = new self($hostGroup);
        return self::$instances[$hostGroup];
    }

    /**
     * Constructor.
     *
     * @param string $hostGroup Host group.
     */
    protected function __construct($hostGroup = 'default')
    {
        try {
            $this->conn = new Connection(new HttpPool($hostGroup));
        } catch (\Exception $e) {
            \MNLogger\EXLogger::instance()->log($e);
            throw $e;
        }
    }

    /**
     * Get request.
     *
     * @param string $url      Url.
     * @param array  $curlOpts Curl options.
     *
     * @return mixed
     */
    public function get($url, array $curlOpts = array())
    {
        $dataObj = new DataObject;
        $dataObj->url = $url;
        $dataObj->curlOpts = $curlOpts;
        $dataObj->method = 'get';
        return $this->conn->send($dataObj);
    }

    /**
     * Post request.
     *
     * @param string $url      Url.
     * @param mixed  $curlOpts Curl options.
     *
     * @return mixed
     */
    public function post($url, $data, array $curlOpts = array())
    {
        $dataObj = new DataObject;
        $dataObj->url = $url;
        $dataObj->data = $data;
        $dataObj->curlOpts = $curlOpts;
        $dataObj->method = 'post';
        return $this->conn->send($dataObj);
    }
}