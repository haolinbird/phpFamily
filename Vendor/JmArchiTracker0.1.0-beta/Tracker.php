<?php
/**
 * 环境初始化.
 *
 * @author xianwangs@jumei.com
 */
namespace JmArchiTracker;

class Tracker
{
    const PROD_ENV = 'prod';
    const BENCH_ENV = 'bench';
    const BENCH_UID_OFFSET = 500000000;

    /**
     * 环境初始化.
     */
    public static function init()
    {
        global $context, $owl_context;

        if (isset($_SERVER['REQUEST_METHOD']) && ! empty($_SERVER['REQUEST_METHOD']) && ! headers_sent()) {
            header('X-Jumei-Extended-Ver: 0.1.0-beta');
        }

        // 压测开关: 1压测, 0或没有则为生产.
        // X-Jumei-Loadbench.
        if (isset($_SERVER['HTTP_X_JUMEI_LOADBENCH'])) {
            $context['X-Jumei-Loadbench'] = $_SERVER['HTTP_X_JUMEI_LOADBENCH'];
        }

        if (isset($_SERVER['HTTP_X_JUMEI_CONTEXT'])) {
            $output = null;
            parse_str($_SERVER['HTTP_X_JUMEI_CONTEXT'], $output);
            if (is_array($output)) {
                foreach ($output as $key => $value) {
                    $context[$key] = $value;
                }
            }
        }

        if (isset($_SERVER['HTTP_X_JUMEI_OWL_CONTEXT'])) {
            $output = null;
            parse_str($_SERVER['HTTP_X_JUMEI_OWL_CONTEXT'], $output);
            if (is_array($output)) {
                foreach ($output as $key => $value) {
                    $owl_context[$key] = $value;
                }
            }
        }
    }

    // 判断是否为bench环境.
    public static function isBench()
    {
        global $context;

        if (! isset($context['X-Jumei-Loadbench'])) {
            return false;
        }

        if (strcasecmp($context['X-Jumei-Loadbench'], static::BENCH_ENV) !== 0) {
            return false;
        }

        return true;
    }

    // 判断是否为prod环境.
    public static function isProd()
    {
        global $context;

        if (! isset($context['X-Jumei-Loadbench']) || empty($context['X-Jumei-Loadbench']) || strcasecmp($context['X-Jumei-Loadbench'], static::PROD_ENV) === 0) {
            return true;
        }

        return false;
    }

    // 初始化为prod环境.
    public static function initProdEnv()
    {
        global $context;
        $context['X-Jumei-Loadbench'] = static::PROD_ENV;
    }

    // 初始化为bench环境.
    public static function initBenchEnv()
    {
        global $context;
        $context['X-Jumei-Loadbench'] = static::BENCH_ENV;
    }

    /**
     * 获取当前环境.
     */
    public static function getEnv() {
        global $context;

        if (isset($context['X-Jumei-Loadbench'])) {
            return $context['X-Jumei-Loadbench'];
        }

        return '';
    }

    /**
     * 判断uid是否为压测环境, 压测环境uid条件为大于5亿且尾号为4或9
     */
    public static function isBenchUid($uid) {
        if ($uid < self::BENCH_UID_OFFSET) {
            return false;
        }

        $lastNum = $uid % 10;

        if ($lastNum == 4 || $lastNum == 9) {
            return true;
        }

        return false;
    }
}