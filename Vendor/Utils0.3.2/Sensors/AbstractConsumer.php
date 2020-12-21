<?php
namespace Utils\Sensors;

abstract class AbstractConsumer {

    /**
     * 发送一条消息。
     *
     * @param string $msg 发送的消息体
     * @return bool
     */
    public abstract function send($msg);

    /**
     * 立即发送所有未发出的数据。
     *
     * @return bool
     */
    public function flush() {
    }

    /**
     * 关闭 Consumer 并释放资源。
     *
     * @return bool
     */
    public function  close() {
    }
}