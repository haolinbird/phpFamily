<?php


namespace Thrift\Protocol;

use Thrift\Transport\Lark;

class TLarkBinaryProtocol extends TBinaryProtocol {
    public function readMessageBegin(&$name, &$type, &$seqid){
        // 10 = tag(2) + total_len(4) + first_lark_item_len (4)
        $data = $this->trans_->getTransport()->readAll(6);

        //$arr = unpack('ntag/Nlength/Nlark_item_len', $data);
        $arr = unpack('ntag/Nlength', $data);

        // lark与PHPServer交互出错了,全部读出来抛个异常
        if ($arr['tag'] != Lark::TAG_RPC_CLIENT_RECV) {
            //$err = $this->trans_->getTransport()->readAll($arr['lark_item_len']);
            $data .= $this->trans_->getTransport()->readAll($arr['length']);
            $larkData = Lark::decode($data, $err);
            if (!$larkData) {
                throw new \Exception("lark协议解析失败：$err");
            }

            $err = $larkData['data'][0];
            if (count($larkData['data']) > 1) {
                $context = json_decode($larkData['data'][1], true);
                $err .= ".  服务地址：{$context['server_ip']}";
            }
            throw new \Exception("lark协议解析失败：$err");
        }

        // 把剩余头读完，接下里交给thrift协议处理
        $backendProtocolLen = unpack('N', $this->trans_->getTransport()->readAll(4));
        // 底层协议的数据处理
        parent::readMessageBegin($name, $type, $seqid);
	    $remainingLength = $arr['length'] - $backendProtocolLen[1] - 4;
        // 剩余lark数据对象读取
        $remainingData = $this->trans_->getTransport()->readAll($remainingLength);
        // 除了底层协议数据,目前只剩context的数据了.
        if(strlen($remainingData) > 0){
            $larkItems = Lark::getValueItem($remainingData, 1);
            if(count($larkItems))
            $context = json_decode($larkItems[0], true);
            if($context && isset($context['server_ip'])){
                global $lark_context;
                $lark_context['server_ip'] = $context['server_ip'];
            }
        }
    }

    public function readMessageEnd() {
        return parent::readMessageEnd();
    }
}
