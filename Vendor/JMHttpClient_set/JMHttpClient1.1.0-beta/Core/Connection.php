<?php
/**
 * Connection.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace JMHttpClient\Core;

use JMHttpClient\Exception\RequestException;

/**
 * Http Connection.
 */
class Connection
{
    protected $pool = null;
    protected $lastResponse = null;

    /**
     * Constructor.
     *
     * @param \JMHttpClient\Core\HttpPool $pool Pool object.
     */
    public function __construct(\JMHttpClient\Core\HttpPool $pool)
    {
        $this->pool = $pool;
    }
    
    /**
     * Send request.
     *
     * @param \JMHttpClient\Core\DataObject $dataObj Request data.
     *
     * @return mixed
     */
    public function send(\JMHttpClient\Core\DataObject $dataObj)
    {
        $this->lastResponse = null;

        // $opts = $this->pool->getOptions();

        $e = null;
        $uniqid = uniqid(date('YmdHis'));

        $validServerTotal = $this->pool->countValidServer();
        if ($validServerTotal == 0) {
            $this->pool->recoveryFalutHost(true);
            $validServerTotal = $this->pool->countValidServer();
        }

        for ($i = 0; $i < $validServerTotal; $i++) {
            try {
                $this->transaction($dataObj, $uniqid);
            } catch (\Exception $e) {
                \MNLogger\EXLogger::instance()->log($e);

                $log = '[' . date('Y-m-d H:i:s') . '] ' . $dataObj->method . ' ' . $dataObj->url . PHP_EOL;
                $log .= $e->getCode() . ' ' . $e->getMessage() . PHP_EOL;
                $log .= (is_array($dataObj->data) ? http_build_query($dataObj->data) : $dataObj->data);
                $this->pool->debug('http_pool.request_repeat',  $log);

                $this->pool->test();
                // 忽略重试机会.
                throw $e;
            }
        }

        return $this->lastResponse;
    }

    /**
     * Start transfer.
     *
     * @param \JMHttpClient\Core\DataObject $dataObj Data object.
     * @param string                        $uniqid  Req uniqid.
     *
     * @return mixed
     */
    protected function transaction(\JMHttpClient\Core\DataObject $dataObj, $uniqid)
    {
        $handle = $this->pool->getHandle($uniqid);
        $ch = $handle['ch'];

        $url = rtrim($handle['host'], '/') . '/' . ltrim($dataObj->url, '/');
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($dataObj->method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($dataObj->data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataObj->data);
            }
        }

        foreach ($dataObj->curlOpts as $key => $value) {
            curl_setopt($ch, constant($key), $value);
        }

        \MNLogger\TraceLogger::instance()->HTTP_CS($url, $dataObj->method, $dataObj->method == 'post' ? $dataObj->data : array());

        $headers = $this->getContextHeaders();
        if (! empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $this->lastResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            $errstr = curl_error($ch);
            $errno = curl_errno($ch);
            $error = "$errstr($errno)";

            \MNLogger\TraceLogger::instance()->HTTP_CR(\MNLogger\Base::T_EXCEPTION, strlen($error), $error);
            throw new RequestException($errstr, $errno);
        }

        \MNLogger\TraceLogger::instance()->HTTP_CR(\MNLogger\Base::T_SUCCESS, 0);
    }

    protected function getContextHeaders()
    {
        global $context, $owl_context;

        $headers = array();

        if (! empty($context)) {
            $headers[] = 'X-JUMEI-CONTEXT:' . http_build_query($context);
        }

        if (! empty($owl_context)) {
            $headers[] = 'X-JUMEI-OWL-CONTEXT:' . http_build_query($owl_context);
        }

        return $headers;
    }
}