<?php

/**
 * The class is abatain to gennator global key for database
 *
 * @author wanglibing<libingw@jumei.com>
 */

namespace Globalkey;

/**
 * JMDbKeyBatch.
 */
class JMDbKeyBatch {

    private $Db;

    const DB_NAME = 'key_db';
    const MAX_RETRY_COUNT = 5;

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
    private function getIdFromDb($projectFlag) {
        try {
//            if (!$this->Db) {
            $db = \Db\Connection::instance();
            $this->Db = $db->write(static::DB_NAME);
//            }

            static $retryCount;

            if (!$retryCount || $retryCount > static::MAX_RETRY_COUNT) {
                $retryCount = 0;
            }

            // 查询单个字段global_key
            $globalKey = $this->Db->queryScalar("SELECT `global_key` FROM `key_table` WHERE `project_flag`='" . $projectFlag . "'");
            if (null === $globalKey) {
                throw new Exception('Global key for project "' . $projectFlag . '" has not been initialized !');
            }
            // 影响的条数
            $rs = $this->Db->exec("UPDATE `key_table` SET `global_key`=`global_key`+2000 WHERE `global_key`=" . $this->Db->quote($globalKey) . " AND `project_flag`='" . $projectFlag . "'");
            while (!$rs) {
                $globalKey = $this->getIdFromDb($projectFlag);
                if ($globalKey) {
                    break;
                }
            }
        } catch (\Exception $e) {
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
     * Get id.
     *
     * @param string $projectFlag Project flag.
     *
     * @return integer
     * @throws Exception Invalid project flag.
     */
    public function getId($projectFlag) {
        if (!preg_match('#[a-zA-Z0-9]+[a-zA-Z0-9\-_]*#', $projectFlag)) {
            throw new Exception("invalid project flag !");
        }
        // ---------------------------------------------------
        // 依赖：
        // System V信号量，编译时加上 –enable-sysvsem
        // System V共享内存，编译时加上 –enable-sysvshm
        // ID存储在共享内存中(shared memory)，通过信号灯(semaphore)同步
        // ---------------------------------------------------
        //$IPC_KEY = 0x1234; // System V IPC KEY
        $IPC_KEY = ftok(__FILE__, 'R'); //因为这个IPC_KEY需要为整型，目前的方案选择取ftok，或者用整型标识各个项目
        $IPC_KEY = crc32($projectFlag) | $IPC_KEY;
        $SEQ_KEY = 8; // 共享内存中存储序列号ID的KEY  int类型
        // 创建或获得一个现有的，以"1234"为KEY的信号量
        $sem_id = sem_get($IPC_KEY);
        // 创建或关联一个现有的，以"1234"为KEY的共享内存
        $shm_id = shm_attach($IPC_KEY, 10000);
        // 占有信号量，相当于上锁，同一时间内只有一个流程运行此段代码
        sem_acquire($sem_id);
        // 从共享内存中获得序列号ID
        @$idArr = unserialize(shm_get_var($shm_id, $SEQ_KEY));
        if (is_array($idArr) && count($idArr) && ($idArr['currentId'] < $idArr['topId'])) {
            $idArr['currentId'] += 1;
            // 将"++"后的ID写入共享内存
            shm_put_var($shm_id, $SEQ_KEY, serialize($idArr));
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            return $idArr['currentId'];
        } else {
            try {
                $currentId = $this->getIdFromDb($projectFlag);
            } catch (\Exception $e) {
                // 释放信号量，相当于解锁
                sem_release($sem_id);
                // 关闭共享内存关联
                shm_detach($shm_id);
                throw new Exception('Message:' . $e->getMessage());
            }
            $putArr = array();
            $putArr['currentId'] = $currentId;
            $putArr['topId'] = $currentId + 999 + mt_rand(100, 999);
            shm_put_var($shm_id, $SEQ_KEY, serialize($putArr));
            // 释放信号量，相当于解锁
            sem_release($sem_id);
            // 关闭共享内存关联
            shm_detach($shm_id);
            return $putArr['currentId'];
        }
    }

}
