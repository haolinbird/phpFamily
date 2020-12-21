<?php
namespace Redis;

class HaConfigRule extends \SharedMemory\HaConfig {
    protected function isInConfig($needle, $haystack) {
        foreach ($haystack as $item) {
            if ($item['host'] == $needle['host'] && $item['port'] == $needle['port']) {
                return true;
            }
        }
        return false;
    }
}
