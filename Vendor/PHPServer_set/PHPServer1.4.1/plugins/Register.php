<?php

/*
 * 注册服务插件
 * @author xudongw<xudongw@jumei.com>
 **/

class Register
{

    /**
     * etcd 服务的dovekey
     */
    protected $etcdServerDoveKey;

    // etcd上key=>lease id 映射
    protected $keyToLeaseMap = array();

    // key=>worker name 映射
    protected $keyToWorkerNameMap = array();

    // curl timeout
    const MY_CURL_TIMEOUT   = 3;

    // http requre retry count
    const RETRY_COUNT       = 2;

    // service expired time
    const TTL               = 10;

    private function __construct($etcdServerDoveKey) {
        $this->etcdServerDoveKey = $etcdServerDoveKey;
    }

    /**
     * 创建一个注册服务的实例.
     *
     * @param string $etcdServerDoveKey etcd dove key.
     *
     * @return \Register
     */
    public static function instance($etcdServerDoveKey)
    {
        static $instances = array();

        if (empty($instances[$etcdServerDoveKey])) {
            $instances[$etcdServerDoveKey] = new self($etcdServerDoveKey);
        }
        return $instances[$etcdServerDoveKey];
    }


    public function registerService($ip, $port, $workerName)
    {
        $key            = "service.address.$workerName.$ip:$port";
        $value          = '';
        $isNewLeaseId   = true;
        $leaseId        = $this->getLease($key, $isNewLeaseId);

        $this->keyToWorkerNameMap[$key] = $workerName;

        if (!$leaseId) {
            return false;
        }

        if (!$isNewLeaseId) {
            return true;
        }

        $data = array(
            'key'   => base64_encode($key),
            'value' => base64_encode($value),
            'lease' => $leaseId,
            'prev_kv'=>true,
        );

        $response = $this->request('/v3alpha/kv/put', json_encode($data));
        return $response !== false;
    }

    public function unregisterService()
    {
        $isAllServiceUnregistd = true;
        foreach ($this->keyToLeaseMap as $key=>$leaseId) {
            $response = $this->request('/v3alpha/kv/lease/revoke', json_encode(array('ID'=>$leaseId)));

            if (!$response) {
                $isAllServiceUnregistd = false;
                ServerLog::add("unregister service [{$this->keyToWorkerNameMap[$key]}] failed");
                continue;
            }

            unset($this->keyToLeaseMap[$key]);
            ServerLog::add("unregister service [{$this->keyToWorkerNameMap[$key]}] successfully");
        }
        return $isAllServiceUnregistd;
    }

    public function getLease($key, &$isNewLeaseId)
    {
        $leaseId = @$this->keyToLeaseMap[$key];
        if (!empty($leaseId)) {
            if ($this->keepLeaseAlive($leaseId)) {
                $isNewLeaseId = false;
                return $leaseId;
            }
        }

        $leaseId = $this->grantLease();

        if (!$leaseId) {
            return $leaseId;
        }

        $this->keyToLeaseMap[$key] = $leaseId;

        $isNewLeaseId = true;
        return $leaseId;
    }

    public function keepLeaseAlive($leaseId)
    {
        $data = array(
            'ID'    => $leaseId
        );

        $response = $this->request('/v3alpha/lease/keepalive', json_encode($data));


        if (!$response || empty($response['result']['TTL'])) {
            return false;
        }

        return true;
    }

    public function grantLease()
    {
        $data = array(
            'ID'    => 0,
            'TTL'   => self::TTL,
        );

        $response = $this->request('/v3alpha/lease/grant', json_encode($data));

        if (!$response) {
            return false;
        }

        return $response['ID'];
    }

    public function request($uri, $data) {
        $isRequestSuccess = false;

        try {
            $etcdServers = DoveClient\Config::get($this->etcdServerDoveKey, true);
        } catch(Exception $e) {
            ServerLog::add("get etcd server of dove key [{$this->etcdServerDoveKey}] failed with err: ". $e->getMessage());
            return false;
        }

        if (!is_array($etcdServers)) {
            ServerLog::add("get etcd server of dove key [{$this->etcdServerDoveKey}] failed with err : result is not array");
            return false;
        }

        foreach ($etcdServers as $server) {
            $url = $server . $uri;
            $response = $this->http($url, $data);
            $retArr = json_decode($response['return'], true);
            if ($response['status'] == 200 && is_array($retArr)) {
                $isRequestSuccess = true;
                break;
            }
        }

        if (!$isRequestSuccess) {
            ServerLog::add($url . ' data:'. $data . ' request faield. response is ' . json_encode($response));
            return false;
        }

        return $retArr;
    }
    /**
     * 支持以post方式提交数据.
     *
     * @param string $url      提交的地址.
     * @param array  $postData 提交的post数据.
     * @param array  $headers  附件的头信息.
     *
     * @return array
     */
    public function http($url, $data){

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, count($data));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::MY_CURL_TIMEOUT);

        $tryTimes = 0;
        $status   = 0;
        while ($status != 200 && ++$tryTimes < self::RETRY_COUNT) {
            $result = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }

        curl_close($curl);

        return array(
            'return' => $result,
            'status' => $status
        );
    }
}
