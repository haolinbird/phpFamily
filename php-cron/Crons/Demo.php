<?php
/**
 * php Start.php --class=Demo --test_params=test
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-07-30 21:04:11
 */

namespace Crons;

use Crons\Process\ProcessBase;

require_once __DIR__.'/../init.php';
/**
 * Demo.
 */
class Demo extends ProcessBase
{
    /**
     * 处理数据.
     *
     * @param array $params 子进程入参
     *
     * @return void
     * @throws \Exception
     */
    public function run($params = [])
    {
        // 接收脚本参数
        $option = \Util\CliOptions::ParseFromArgv();
        // 获取命令行参数
        $testParams = $option->getOption('test_params');

        echo "run demo, start_runtime:".date('Y-m-d H:i:s')."\r\n";
        echo "cli_params:".$testParams."\r\n";
    }

}
