<?php

/**
 * 多进程基本类
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-07-30 21:04:11
 */

namespace Crons\Process;

abstract class ProcessBase
{
    // 存储单例对象
    public static $instance = null;

    // 最大子进程数量
    private $maxProcessNum = 10;

    // 当前子进程数量
    private $currentProcessNum = 0;

    // 父进程传递给子进程的入参
    private $currentProcessParams = [];

    /**
     * 构造函数.
     * @return void
     */
    function __construct()
    {
    }

    /**
     * 返回一个进程实例.
     *
     * @param integer $maxProcessNum 进程数量.
     *
     * @return \Crons\Process\ProcessBase
     */
    public static function getInstance($maxProcessNum = 10)
    {
        if (self::$instance == null) {
            self::$instance = new static();
        }
        if ($maxProcessNum > 0) {
            self::$instance->maxProcessNum = $maxProcessNum;
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
     * 运行结束处理方法.
     * @return void
     */
    public function afterRun()
    {
    }

    /**
     * 启动.
     * @return void
     */
    public function start()
    {
        // 安装子进程退出信号处理器
        pcntl_signal(SIGHUP, function ($signal) {
            switch ($signal) {
                // 子进程退出时,减少子进程计数
                case SIGCHLD:
                    echo 'SIGCHLD', PHP_EOL;
                    $this->currentProcessNum--;
                    break;
                default:
                    break;
            }
        });

        // 启动之前的初始化操作
        $this->beforeRun();

        // 判断
        if (function_exists('pcntl_fork') && $this->getProcessNum() > 1) {
            while (true) {
                $this->currentProcessNum++;

                // 执行分发程序
                $result = $this->map();
                if ($result === false) {
                    break;
                }

                $pid = pcntl_fork();

                //父进程和子进程都会执行下面代码
                if ($pid == -1) {
                    // 错误处理：创建子进程失败时返回-1.
                    die('could not fork');
                } elseif ($pid) {
                    // 父进程会得到子进程号，父进程运行代码
                    $this->parentProcess();

                    // 达到上限时父进程阻塞等待任一子进程退出后while循环继续
                    if ($this->currentProcessNum >= $this->maxProcessNum) {
                        pcntl_wait($status); //等待子进程中断，防止子进程成为僵尸进程。
                    }
                } else {
                    // 子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
                    $this->run($this->currentProcessParams);
                    exit();
                }
            }

            // 等待所有子进程运行结束退出
            while (($pid = pcntl_waitpid(0, $status)) != -1) {
                echo "子进程[{$pid}] 退出!!\n";
            }
        } else {
            $this->run();
        }

        // 所有子进程操作结束后的逻辑
        $this->afterRun();
    }

    /**
     * 分发程序 用于分组待处理数据传递子进程归约.
     * @return boolean
     */
    public function map()
    {
        return false;
    }

    /**
     * 父进程处理函数.
     * @return void
     */
    public function parentProcess()
    {
    }

    /**
     * 抽象的子进程方法.
     *
     * @param array $params 子进程入参
     *
     * @return void
     */
    abstract public function run($params = []);
}
