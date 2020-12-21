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
        $opts = $this->pool->getOptions();
        $repeatNum = isset($opts['repeat_num']) ? intval($opts['repeat_num']) : 0;
        $repeatNum = $repeatNum <= 0 ? 0 : $repeatNum;
        $repeatInterval = isset($opts['repeat_interval']) ? intval($opts['repeat_interval']) : 1;
        $repeatInterval = $repeatInterval < 1 ? 1 : $repeatInterval;

        $e = null;
        $uniqid = uniqid(date('YmdHis'));

        if ($repeatNum > 0) {
            for ($i = 0; $i <= $repeatNum; $i++) {
                try {
                    $e = null;
                    $this->transaction($dataObj, $uniqid);
                    break;
                } catch (\Exception $e) {
                    $log = '[' . date('Y-m-d H:i:s') . '] ' . $dataObj->method . ' ' . $dataObj->url . PHP_EOL;
                    $log .= 'sleep ' . $repeatInterval . PHP_EOL;
                    $log .= $e->getCode() . ' ' . $e->getMessage() . PHP_EOL;
                    $log .= (is_array($dataObj->data) ? http_build_query($dataObj->data) : $dataObj->data);
                    $this->pool->debug('http_pool.request_repeat',  $log);
                    sleep($repeatInterval);
                }
            }
        } else {
            try {
                $this->transaction($dataObj, $uniqid);
            } catch (\Exception $e) {
                $log = '[' . date('Y-m-d H:i:s') . '] ' . $dataObj->method . ' ' . $dataObj->url . PHP_EOL;
                $log .= $e->getCode() . ' ' . $e->getMessage() . PHP_EOL;
                $log .= (is_array($dataObj->data) ? http_build_query($dataObj->data) : $dataObj->data);
                $this->pool->debug('http_pool.request_repeat',  $log);
            }
        }

        if ($e) {
            $this->pool->test();
            if (! $e instanceof RequestException) {
                throw new $e;
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

        curl_setopt($ch, CURLOPT_URL, $handle['host'] . $dataObj->url);

        if ($dataObj->method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataObj->data);
        }

        foreach ($dataObj->curlOpts as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        $this->lastResponse = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new RequestException(curl_error($ch), curl_errno($ch));
        }
    }
}