<?php
/**
 * php Start.php --class=Demo --processNum=10 --arg1=foo --arg2=2
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-07-31 21:04:11
 */

namespace Crons {
    // 设置时区
    date_default_timezone_set('Asia/Shanghai');

    // 定义脚本目录
    define('SCRIPT_ROOT', __DIR__ . DIRECTORY_SEPARATOR);

    // 加载框架入口文件
    //require_once realpath(SCRIPT_ROOT . '..' . DIRECTORY_SEPARATOR .'init.php');
    require_once(SCRIPT_ROOT . '..' . DIRECTORY_SEPARATOR . 'init.php');


    $option = \Util\CliOptions::ParseFromArgv();
    $class = $option->getOption('class');

    if (empty($class)) {
        echo 'class is undefined' . PHP_EOL;
        return;
    } else {
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        if (!file_exists(SCRIPT_ROOT . $classPath . '.php')) {
            echo $class . '.php not found in path ' . SCRIPT_ROOT;
            return;
        }
    }

    // 设置运行进程数
    $processNum = intval($option->getOption('processNum', 1));
    $processNum = $processNum <= 0 ? 1 : $processNum;

    // 启动
    $className = __NAMESPACE__ . '\\' . $class;
    $className::getInstance($processNum)->start();
}