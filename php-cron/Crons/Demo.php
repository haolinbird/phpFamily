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
     * 分发程序 用于分组待处理数据传递子进程归约.
     * @return boolean
     */
    public function map()
    {

    }

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
        // 获取脚本参数
        $processNum = $this->getCronParams('processNum',5);

        echo "run demo, start_runtime:".date('Y-m-d H:i:s')."\r\n";
        echo "processNum:".$processNum."\r\n";
    }

}
