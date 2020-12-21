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
                $log = '[' . date('Y-m-d H:i:s') . '] ' . $dataObj->method . ' ' . $dataObj->url . PHP_EOL;
                $log .= $e->getCode() . ' ' . $e->getMessage() . PHP_EOL;
                $log .= (is_array($dataObj->data) ? http_build_query($dataObj->data) : $dataObj->data);
                $this->pool->debug('http_pool.request_repeat',  $log);

                $this->pool->test();
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

        curl_setopt($ch, CURLOPT_URL, rtrim($handle['host'], '/') . '/' . ltrim($dataObj->url, '/'));

        if ($dataObj->method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataObj->data);
        }

        foreach ($dataObj->curlOpts as $key => $value) {
            curl_setopt($ch, constant($key), $value);
        }

        $this->lastResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new RequestException(curl_error($ch), curl_errno($ch));
        }
    }
}