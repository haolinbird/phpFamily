<?php


namespace Thrift\Protocol;

use Thrift\Transport\Lark;

class TLarkBinaryProtocol extends TBinaryProtocol {
    public function readMessageBegin(&$name, &$type, &$seqid){
        // 10 = tag(2) + total_len(4) + first_lark_item_len (4)
        $data = $this->trans_->getTransport()->readAll(10);

        $arr = unpack('ntag/Nlength/Nlark_item_len', $data);

        // lark与PHPServer交互出错了
        if ($arr['tag'] != Lark::TAG_RPC_CLIENT_RECV) {
            $err = $this->trans_->getTransport()->readAll($arr['lark_item_len']);
            throw new \Exception("请求失败：$err");
        }

        return parent::readMessageBegin($name, $type, $seqid);
    }

    public function readMessageEnd() {
        return parent::readMessageEnd();
    }
}