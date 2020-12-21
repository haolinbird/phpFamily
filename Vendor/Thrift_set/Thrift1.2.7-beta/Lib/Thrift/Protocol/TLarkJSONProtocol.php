<?php


namespace Thrift\Protocol;

class TLarkJSONProtocol extends TBinaryProtocol {
    public function readMessageBegin(&$name, &$type, &$seqid){
        // 10 = tag(2) + total_len(4) + thriftbuffer_len (4)
        $this->trans_->getTransport()->readAll(10);

        return parent::readMessageBegin($name, $type, $seqid);
    }

    public function readMessageEnd() {
        return parent::readMessageEnd();
    }
}