<?php

/**
 * 线程基本类
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-01-18 19:28:30
 */

namespace Crons\Thread;

abstract class ThreadBase
{
    // 线程数量
    private $threadNum = 10;
    public static $instance = null;

    /**
     * 初始化 Redis.
     * @return void
     */
    public function __initRedis()
    {
    }

    /**
     * 构造函数.
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * 返回一个线程实例.
     *
     * @param integer $threadNum   线程数量.
     * @param boolean $isNeedRedis 是否需要初始化redis.
     *
     * @return \Crons\Thread\ThreadBase
     */
    public static function getInstance($threadNum = 10, $isNeedRedis = false)
    {
        if (self::$instance == null) {
            self::$instance = new static();
            if ($isNeedRedis) {
                self::$instance->__initRedis();
            }
        }
        if ($threadNum > 0) {
            self::$instance->threadNum = $threadNum;
        }
        return self::$instance;
    }

    /**
     * 启动之前，预处理方法.
     * @return void
     */
    public function beforeRun()
    {
    }

    /**
     * 启动.
     * @return void
     */
    public function start()
    {
        $this->beforeRun();
        if (function_exists('pcntl_fork') && $this->getThreadNum() > 1) {
            for ($x = 0; $x < $this->getThreadNum(); $x++) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    echo "创建子进程失败!!\n";
                } elseif ($pid == 0) {
                    $this->run();
                    exit();
                }
            }
            while (($pid = pcntl_waitpid(0, $status)) != -1) {
                echo "子进程[{$pid}] 退出!!\n";
            }
        } else {
            $this->run();
        }
        $this->afterRun();
    }

    /**
     * 运行结束处理方法.
     * @return void
     */
    public function afterRun()
    {
    }

    /**
     * 获取启动线程数.
     * @return integer
     */
    public function getThreadNum()
    {
        return $this->threadNum;
    }

    /**
     * 设置启动线程数.
     *
     * @param integer $num 启动线程数.
     *
     * @return void
     */
    public function setThreadNum($num)
    {
        $this->threadNum = $num;
    }

    /**
     * 抽象的业务方法.
     * @return void
     */
    abstract public function run();
}
