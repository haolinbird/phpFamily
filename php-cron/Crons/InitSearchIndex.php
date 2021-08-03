<?php

/**
 * php Start.php --class=InitSearchIndex
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-07-08 21:04:11
 */

namespace Crons;

use Module\Search;
use Util\Debug;
use Exception\BusinessException;

/**
 * 全量更新搜索索引.
 */
class InitSearchIndex
{
    /**
     * 处理数据.
     *
     * @return void
     * @throws \Exception System Exception.
     */
    public function process()
    {
        // 接收脚本参数
        $option = \Util\CliOptions::ParseFromArgv();

        $type = $option->getOption('type') ? $option->getOption('type') : 'all';

        Debug::promptOutput("开始全量更新 搜索索引");
        die;

        // 全量更新 搜索索引
        try {
            $updateResult = Search::instance()->updateSearchIndex($type);

            if ($updateResult) {
                Debug::promptOutput("搜索索引 - 全量更新成功");
            } else {
                Debug::promptOutput("搜索索引 - 全量更新失败");
            }
        } catch (BusinessException $e) {
            Debug::promptOutput($e->getMessage());
        }
    }
}
