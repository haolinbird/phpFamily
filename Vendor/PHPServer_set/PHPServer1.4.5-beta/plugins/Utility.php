<?php
/**
 * 复用方法集合
 * @author xudongw
 */


class Utility {
    public static function getCgiPass($serviceName) {
        return 'unix://' . PHPServer::RUNTIME_DIR . '/' . $serviceName . '.sock';
    }

}
