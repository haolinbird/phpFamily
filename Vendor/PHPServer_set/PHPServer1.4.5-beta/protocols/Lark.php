<?php
/**
 * lark通信协议.
 *
 * @author xudongw <wuxudongjs@gmail.com>
 */

/**
 * 新 RPC 框架简单文本协议.
 */
class Lark
{
    CONST TAG_BYTE      = 2;
    CONST LENGTH_BYTE   = 4;
    CONST HEAD_LEN      = 6;

    CONST TAG_RPC_CLIENT_SEND       = 1000;
    CONST TAG_RPC_CLIENT_RECV       = 2000;
    CONST TAG_RPC_LARK_WRITE_ERR    = 2001;
    CONST TAG_RPC_LARK_READ_ERR     = 2002;
    CONST TAG_RPC_SERVER_RECV       = 3000;
    CONST TAG_RPC_SERVER_SEND       = 4000;

    CONST TAG_KEEPALIVE_TICK        = 5000;
    CONST TAG_KEEPALIVE_STOP        = 5010;
    CONST TAG_KEEPALIVE_OK          = 6000;
    CONST TAG_KEEPALIVE_ERR         = 6001;
    CONST TAG_STOPALIVE_OK          = 6010;
    CONST TAG_STOPALIVE_ERR         = 6011;

    CONST KEEP_CONNECTION           = 'keep';
    CONST RELEASE_CONNECTION        = 'release';

    // tag对应的value有几个item映射表
    protected static $valueItemCountMap = array(
        self::TAG_RPC_CLIENT_SEND       => 2,
        self::TAG_RPC_CLIENT_RECV       => 1,
        self::TAG_RPC_LARK_WRITE_ERR    => 1,
        self::TAG_RPC_LARK_READ_ERR     => 1,
        self::TAG_RPC_SERVER_RECV       => 1,
        self::TAG_RPC_SERVER_SEND       => 1,
        self::TAG_KEEPALIVE_TICK        => 2,
        self::TAG_KEEPALIVE_STOP        => 2,
        self::TAG_KEEPALIVE_OK          => 1,
        self::TAG_KEEPALIVE_ERR         => 1,
        self::TAG_STOPALIVE_OK          => 1,
        self::TAG_STOPALIVE_ERR         => 1,
    );

    /**
     * 处理数据流.
     *
     * @param string $data 数据流.
     *
     * @return integer|boolean
     */
    public static function input($data) {
        if (strlen($data) < self::HEAD_LEN) {
            return self::HEAD_LEN;
        }

        $array = unpack('Nlength', substr($data, self::TAG_BYTE, self::LENGTH_BYTE));

        $dataLength = $array['length'];

        if (!is_numeric($dataLength)) {
            return false;
        }

        if (strlen($data) < self::HEAD_LEN + $dataLength) {
            return self::HEAD_LEN + $dataLength - strlen($data);
        }

        return 0;
    }

    /**
     * 分解value中的每个item
     *
     * @param string $buffer    value内容
     * @param int    $itemCount 有多少个item
     *
     * @return array
     */
    protected static function getValueItem($buffer, $itemCount = 1) {
        $offset = 0;
        $values = array();
        for ($i = 0; $i < $itemCount; $i++) {
            $unpackData = unpack('Nlen', substr($buffer, $offset, 4));
            $len = $unpackData['len'];
            $offset += 4;
            $values[] = substr($buffer, $offset, $len);
            $offset += $len;
        }
        return $values;
    }

    /**
     * 解码数据流.
     *
     * @param string $data 数据流.
     *
     * @return array
     */
    public static function decode($data, &$err) {
        if (strlen($data) < self::HEAD_LEN) {
            $err = '协议长度不完整';
            return false;
        }

        $arr =  unpack('ntag/Nlength', substr($data, 0, self::HEAD_LEN));

        $tag        = $arr['tag'];
        $length     = $arr['length'];

        if (empty(self::$valueItemCountMap[$tag])) {
            $err = '无法识别的tag[' . $tag . ']';
            return false;
        }

        $buffer = substr($data, self::HEAD_LEN, $length);

        $data = self::getValueItem($buffer, self::$valueItemCountMap[$tag]);

        return array(
            'tag'   => $tag,
            'data'  => $data,
        );

    }

    /**
     * 编码数据流.
     *
     * @param string $tag   请求标识.
     * @param array  $value 数据
     *
     * @return string
     */
    public static function encode($tag, $values = array()) {
        $buffer = '';
        foreach($values as $val) {
            $buffer .= pack('Na*', strlen($val), $val);
        }

        // 最后要计算buffer的总长度，便于接收方一次性将buffer全部读取出来，以空间换效率
        return pack('nNa*', $tag, strlen($buffer), $buffer);
    }
}
