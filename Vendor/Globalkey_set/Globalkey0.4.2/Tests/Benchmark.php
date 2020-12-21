<?php

/**
 * 多进程测试id生成器
 *
 * @author xudongw xudongw@jumei.com
 */

require 'common.php';
require_once 'interfaces.php';

const PROCCESS_COUNT = 10;
const PROCCESS_ID_COUNT = 10000;

$forkedCount = 0;
$arrChannels = array();
$startTime = microtime(true);

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
        exit(1);
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
        echo "return ids count ".count(self::strToIds($data)).PHP_EOL;
        self::$ids = array_merge(self::$ids, self::strToIds($data)); 
        $supposeCount = PROCCESS_COUNT * PROCCESS_ID_COUNT;
        if (count(self::$ids) == $supposeCount) {
            global $startTime;
            $endTime = microtime(true);
            $realCount = count(array_unique(self::$ids));
            $timeUsed = ($endTime - $startTime) * 1000;
            echo "suppose ids count are $supposeCount".PHP_EOL;
            echo "no duplicated real ids are $realCount".PHP_EOL;
            if ($realCount != $supposeCount) {
                echo "\e[31;40mERROR duplicate2 key found\e[0m" . PHP_EOL;
            }
            echo 'time used: ' . $timeUsed . ' ms' . PHP_EOL;
            exit;
        }
    } 

    /*
     * 测试获取唯一id
     * @param void
     * @return void
     * */
    public static function Work($channel) {
        $g = new \Globalkey\JMDbKeyBatch;
        $i = 0;
        $s = microtime(true);
        while ($i++ < PROCCESS_ID_COUNT) {
            try {
                //$id = $g->getRandomId('foo');
                $id = $g->getId('foo');
                echo $id . PHP_EOL;
                self::BlockWrite($channel[1], $id . PHP_EOL);
            }catch (Exception $e) {
                var_dump($e);
                sleep(3);
            }
        }
        $e = microtime(true);
    }

    public static function BlockWrite($fd, $data, $timeoutMs = 500) {
        $send_len = @fwrite($fd, $data);
        if($send_len == strlen($data)) {
            return true;
        }
        
        // 设置阻塞
        stream_set_blocking($fd, 1);
        // 设置超时
        $timeout_sec = floor($timeoutMs/1000);
        $timeoutMs = $timeoutMs%1000;
        stream_set_timeout($fd, $timeout_sec, $timeoutMs*1000);

        while($send_len != strlen($data)) {
            $send_len += @fwrite($fd, substr($data, $send_len));
        }

        // 改回非阻塞
        stream_set_blocking($fd, 0);
        
        return $send_len == strlen($data);
    }
}

