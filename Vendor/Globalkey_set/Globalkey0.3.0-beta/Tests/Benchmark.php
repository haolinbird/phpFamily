<?php
require 'common.php';
require_once 'interfaces.php';

const PROCCESS_COUNT = 20;
const PROCCESS_ID_COUNT = 3000;

$forkedCount = 0;
$arrChannels = array();
while ($forkedCount++ < PROCCESS_COUNT) {
    $channel = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    $arrChannels[] = $channel;
    $pid = pcntl_fork();

    if ($pid < 0) {
        continue;
    }

    if($pid == 0) {
        fclose($channel[0]);
        BenchMark::Work($channel);
        exit;
    }

    // 父进程关闭通道一端
    fclose($channel[1]);
}

$event = new Select();
foreach ($arrChannels as $channel) {
    $event->add($channel[0], Select::EV_READ, array("BenchMark", 'Callback'), 0, 0);
}

$event->loop();

class Benchmark{
    public static $data;
    public static $ids = array();
    public static $processNum = 0;

    public static function strToIds($strIds){
        return explode(PHP_EOL, trim($strIds));
    }

    public static function Callback($fd, $dataLen, $data){
        global $event;
        if ($dataLen == 0) {
            $event->delAll($fd);
            return;
        }
        self::$ids = array_merge(self::$ids, self::strToIds($data)); 
        $supposeCount = PROCCESS_COUNT * PROCCESS_ID_COUNT;
        if (count(self::$ids) == $supposeCount) {
            $realCount = count(array_unique(self::$ids));
            echo "suppose ids count are $supposeCount".PHP_EOL;
            echo "no duplicated real ids are $realCount".PHP_EOL;
            exit;
        }
    } 

    /*
     * 测试获取唯一id
     * @param void
     * @return void
     * */
    public static function Work($channel) {

        global $supposeCount;
        $g = new \Globalkey\JMDbKeyBatch;
        $i = 0;
        $s = microtime(true);
        while ($i++ < PROCCESS_ID_COUNT) {
            $id = $g->getRandomId('payment_refund_bill');
            //$id = $g->getId('payment_refund_bill');
            fwrite($channel[1], $id.PHP_EOL);
        }
        $e = microtime(true);
    }
}

