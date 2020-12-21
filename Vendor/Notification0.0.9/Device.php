<?php
/**
 * 此方法仅用于管理通知类数据，发送请使用 Client
 */
namespace Notification;

class Device extends Client
{

    public function add($appid, $idfa, $idfv, $imei, $token, $uid, $source, $version)
    {
        $payload = new Payload();
        $payload->set('appid', $appid);
        $payload->set('idfa', $idfa);
        $payload->set('idfv', $idfv);
        $payload->set('imei', $imei);
        $payload->set('token', $token);
        $payload->set('uid', $uid);
        $payload->set('source', $source);
        $payload->set('app_version', $version);
        $payload->sign($this->config['key'], $this->config['secret']);
        $res = $this->_call('/token/add', $payload);

        if ($res) {
            if (!empty($res['result'])) {
                return true;
            } else {
                if (isset($res['message'])) {
                    $this->error = $res['message'];
                }
            }
        }

        return false;

    }

    /**
     * 绑定设备和用户
     * @param $appid
     * @param $idfa
     * @param $idfv
     * @param $imei
     * @param $uid
     * @param $source
     * @param $version
     * @return bool
     */
    public function bind($appid, $idfa, $idfv, $imei, $uid, $source, $version)
    {
        $payload = new Payload();
        $payload->set('appid', $appid);
        $payload->set('idfa', $idfa);
        $payload->set('idfv', $idfv);
        $payload->set('imei', $imei);
        $payload->set('uid', $uid);
        $payload->set('source', $source);
        $payload->set('app_version', $version);
        $payload->sign($this->config['key'], $this->config['secret']);
        $res = $this->_call('/token/bind', $payload);

        if ($res) {
            if (!empty($res['result'])) {
                return true;
            } else {
                if (isset($res['message'])) {
                    $this->error = $res['message'];
                }
            }
        }

        return false;
    }

    /**
     * 删除特定Token
     * @param $appid
     * @param $token
     * @return bool
     */
    public function remove($appid, $token)
    {
        $payload = new Payload();
        $payload->set('appid', $appid);
        $payload->set('token', $token);

        $payload->sign($this->config['key'], $this->config['secret']);
        $res = $this->_call('/token/del', $payload);

        if ($res) {
            if (!empty($res['result'])) {
                return true;
            } else {
                if (isset($res['message'])) {
                    $this->error = $res['message'];
                }
            }
        }

        return false;
    }


}


