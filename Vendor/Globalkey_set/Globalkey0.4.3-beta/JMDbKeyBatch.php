<?php

/**
 * The class is abatain to gennator global key for database
 *
 * @author wanglibing<libingw@jumei.com>
 */

namespace Globalkey;
use \Exception;

/**
 * JMDbKeyBatch.
 */
class JMDbKeyBatch {

    private $Db;

    const DB_NAME = 'key_db';
    const MAX_RETRY_COUNT = 5;

    /* 尾号总数 */
    const PART_COUNT = 10;

    const ENV_PROD          = 'prod';
    const ENV_BENCH         = 'bench';

    private static $instance = null;

    /**
     * Construct.
     */
    function __construct() {
        
    }

    /**
     * Get instance.
     *
     * @return JMDbKeyBatch
     */
    public static function instance() {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get id from db.
     *
     * @param string $projectFlag Project flag.
     *
     * @return integer
     * @throws Exception Get id failed.
     */
    private function getIdFromDb($projectFlag, $step = 2000) {
        try {
            static $retryCount;

            if (!$retryCount || $retryCount > static::MAX_RETRY_COUNT) {
                $retryCount = 0;
            }

            if ($step < static::PART_COUNT) {
                throw new Exception('step must not be less than '.static::PART_COUNT);
            }

            $db = \Db\Connection::instance();
            $this->Db = $db->write(static::DB_NAME);

            // 查询单个字段global_key
            $globalKey = $this->Db->queryScalar("SELECT `global_key` FROM `key_table` WHERE `project_flag`='" . $projectFlag . "'");
            if (null === $globalKey) {
                throw new Exception('Global key for project "' . $projectFlag . '" has not been initialized !');
            }
            // 影响的条数
            $rs = $this->Db->exec("UPDATE `key_table` SET `global_key`=`global_key`+$step WHERE `global_key`=" . $this->Db->quote($globalKey) . " AND `project_flag`='" . $projectFlag . "'");
            while (!$rs) {
                $globalKey = $this->getIdFromDb($projectFlag);
                if ($globalKey) {
                    break;
                }
            }
        } catch (Exception $e) {
            static $preExs = array();
            $preExs[] = $e;
            if ($retryCount < static::MAX_RETRY_COUNT) {
                $retryCount ++;
                $globalKey = $this->getIdFromDb($projectFlag);
            } else {
                $allExs = '';
                array_reverse($preExs);
                foreach($preExs as $preEx) {
                    $allExs .= $preEx."\n";
                }
                $preExs = array();
                throw new Exception('Get id failed after retry ' . static::MAX_RETRY_COUNT . ' times. All Traces:' . "\n" . $allExs . "\n---------------------------------\n", 81000);
            }
        }
        return $globalKey;
    }

    /**
     * 获取当前IDC允许的尾号.
     *
     * @var boolean $refresh 是否强制刷新共享内存
     *
     * @return array
     * @throws Exception
     */
    private function getIDCNumbers($refresh = false) {
        $IPC_KEY = ftok(__FILE__, 'R');
        $IPC_KEY = crc32('GLOBAL_KEY_IDC_NUMBERS' . $this->_getEnv() . '20180404') | $IPC_KEY;
        $SEQ_KEY = 8;
        // 共享内存中存储序列号ID的KEY  int类型
        // 创建或获得一个现有的，以"1234"为KEY的信号量
        $sem_id = sem_get($IPC_KEY);
        // 创建或关联一个现有的，以"1234"为KEY的共享内存
        $shm_id = shm_attach($IPC_KEY, 10000);
        // 占有信号量，相当于上锁，同一时间内只有一个流程运行此段代码
        sem_acquire($sem_id);
        // 从共享内存中获得序列号ID
        @$IDCNumbers = unserialize(shm_get_var($shm_id, $SEQ_KEY));

        // 如果需要强制刷新，或者没有设置，则需要从db中获取新的idc numbers配置
        if (!$refresh && is_array($IDCNumbers) && count($IDCNumbers)) {
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            return $IDCNumbers;
        }

        $db = \Db\Connection::instance();
        $this->Db = $db->write(static::DB_NAME);

        $IDCNumbersStr = $this->Db->queryScalar('SELECT `idc_numbers` FROM `idc_numbers` limit 1');

        if (empty($IDCNumbersStr)) {
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            throw new Exception('get idc numbers failed');
        }

        // db中是以','分隔的，切割成数组
        $IDCNumbers = explode(',', $IDCNumbersStr);

        foreach ($IDCNumbers as $number) {
            if (!is_numeric($number) || $number >= self::PART_COUNT || $number < 0) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new Exception("idc number [$number] is invalid");
            }
        }

        if(!shm_put_var($shm_id, $SEQ_KEY, serialize($IDCNumbers))) {
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            throw new \Exception('Globalkey组件：共享内存写入失败');
        }

        // 释放信号量，相当于解锁
        sem_release($sem_id);
        // 关闭共享内存关联
        shm_detach($shm_id);

        return $IDCNumbers;
    }

    /**
     * Get id.
     *
     * @param string $projectFlag Project flag.
     * @param int    $partNumber  分区编号.
     *
     * @return integer
     * @throws Exception Invalid project flag.
     */
    public function getId($projectFlag) {
        if (!preg_match('#[a-zA-Z0-9]+[a-zA-Z0-9\-_]*#', $projectFlag)) {
            throw new Exception("invalid project flag !");
        }

        $IPC_KEY = ftok(__FILE__, 'R'); //因为这个IPC_KEY需要为整型，目前的方案选择取ftok，或者用整型标识各个项目
        $IPC_KEY = crc32($projectFlag . 'OrderedPart' . $this->_getEnv() . '20180404') | $IPC_KEY;
        $SEQ_KEY = 8; // 共享内存中存储序列号ID的KEY  int类型
        // 创建或获得一个现有的，以"1234"为KEY的信号量
        $sem_id = sem_get($IPC_KEY);
        // 创建或关联一个现有的，以"1234"为KEY的共享内存
        $shm_id = shm_attach($IPC_KEY, 10000);
        // 占有信号量，相当于上锁，同一时间内只有一个流程运行此段代码
        sem_acquire($sem_id);
        // 从共享内存中获得序列号ID
        @$idArr = unserialize(shm_get_var($shm_id, $SEQ_KEY));

        if (is_array($idArr) && count($idArr) && ($idArr['currentId'] + static::PART_COUNT < $idArr['topId'])) {
            try {
                $IDCNumbers = $this->getIDCNumbers();
            } catch (\Exception $e) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new Exception('Message:' . $e->getMessage());
            }

            while ($idArr['currentId']++) {
                if (in_array($idArr['currentId'] % self::PART_COUNT, $IDCNumbers)) {
                    break;
                }
            }
            // 将"++"后的ID写入共享内存
            if(!shm_put_var($shm_id, $SEQ_KEY, serialize($idArr))) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new \Exception('Globalkey组件：共享内存写入失败');
            }
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            return $idArr['currentId'];
        } else {
            try {
                $currentId = $this->getIdFromDb($projectFlag);
                // 刷新idc numbers
                $IDCNumbers = $this->getIDCNumbers(true);

            } catch (\Exception $e) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new Exception('Message:' . $e->getMessage());
            }
            while ($currentId++) {
                if (in_array($currentId % self::PART_COUNT, $IDCNumbers)) {
                    break;
                }
            }
            $putArr = array();
            $putArr['currentId'] = $currentId;
            $putArr['topId'] = $currentId + 999 + mt_rand(100, 999) - static::PART_COUNT;
            if (!shm_put_var($shm_id, $SEQ_KEY, serialize($putArr))) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new \Exception('Globalkey组件：共享内存写入失败');
            }
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            return $putArr['currentId'];
        }
    }

    /**
     * Get random id.
     *
     * @param string $projectFlag Project flag.
     *
     * @return integer
     * @throws Exception Invalid project flag.
     */
    public function getRandomId($projectFlag) {
        if (!preg_match('#[a-zA-Z0-9]+[a-zA-Z0-9\-_]*#', $projectFlag)) {
            throw new Exception("invalid project flag !");
        }

        $IPC_KEY = ftok(__FILE__, 'R'); //因为这个IPC_KEY需要为整型，目前的方案选择取ftok，或者用整型标识各个项目
        $IPC_KEY = crc32($projectFlag . 'RandomPart' . $this->_getEnv() . '20180404') | $IPC_KEY;
        $SEQ_KEY = 8; // 共享内存中存储序列号ID的KEY  int类型
        // 创建或获得一个现有的，以"1234"为KEY的信号量
        $sem_id = sem_get($IPC_KEY);
        // 创建或关联一个现有的，以"1234"为KEY的共享内存
        $shm_id = shm_attach($IPC_KEY, 10000000);
        // 占有信号量，相当于上锁，同一时间内只有一个流程运行此段代码
        sem_acquire($sem_id);
        // 中获得序从共享内存列号ID
        @$idArr = shm_get_var($shm_id, $SEQ_KEY);
        $step = 50000;
        if (is_array($idArr) && count($idArr)) {
            $currentId = array_pop($idArr);
            if(!shm_put_var($shm_id, $SEQ_KEY, $idArr)) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new \Exception('Globalkey组件：共享内存写入失败');
            }
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            return $currentId;
        } else {
            try {
                $startId = $this->getIdFromDb($projectFlag, $step);
                // 刷新idc numbers
                $IDCNumbers = $this->getIDCNumbers(true);
            } catch (\Exception $e) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new Exception('Message:' . $e->getMessage());
            }

            $endId = $startId + $step -1 - static::PART_COUNT;
            $putArr = array();
            while ($startId <= $endId) {
                if (in_array($startId % self::PART_COUNT, $IDCNumbers)) {
                    $putArr[] = $startId;
                }
                $startId++;
            }
            shuffle($putArr);
            $currentId = array_pop($putArr);
            if(!shm_put_var($shm_id, $SEQ_KEY, $putArr)) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new \Exception('Globalkey组件：共享内存写入失败');
            }
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            return $currentId;
        }
    }

    protected function _getEnv() {
        global $context;
        if (@$context['X-Jumei-Loadbench'] == 'bench') {
            return self::ENV_BENCH;
        }
        return self::ENV_PROD;
    }
}
