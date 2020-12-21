<?php
/**
 * Redis Locker.
 *
 * @author dengjing <jingd3@jumei.com>
 */

namespace Utils\Locker;

/**
 * Redis Locker.
 */
class Redis extends \Utils\Singleton
{

    /**
     * 分区标识.
     *
     * @var integer
     */
    protected $partition;

    /**
     * Get instance of the derived class.
     *
     * @return \Utils\Locker\Redis
     */
    public static function instance()
    {
        return parent::instance();
    }

    /**
     * 通过redis锁定一个需要执行的key, 注意key的长度不要超过.
     *
     * @param string  $prefix      锁前缀.
     * @param string  $key         锁的key.
     * @param integer $expireAfter 多久过期,默认为0,即300秒(5分钟)后过期.
     *
     * @return boolean 锁定成功返回true,锁定失败返回false.
     */
    public function lock($prefix, $key, $expireAfter = 0)
    {
        $partition = $this->getPartition();// 保险起见在第一行就重置$this->partition为null.
        $result = false;
        $timestamp = time();
        $expireAfterSeconds = $expireAfter > 0 ? $expireAfter : \Config\Locker::$redis['ttl'];
        $realExpireAfterSeconds = $expireAfterSeconds + 1; // 实际会在多少秒之后过期.
        $expireAt = $timestamp + $realExpireAfterSeconds; // 在哪个时间戳过期.
        $redisKey = $this->genKey($prefix, $key);
        $redisCfgName = \Config\Locker::$redis['name'];
        if (is_null($partition)) {
            $redis = \Redis\RedisMultiCache::getInstance($redisCfgName);
        } else {
            $redis = \Redis\RedisMultiCache::getInstance($redisCfgName)->partitionByUID($partition);
        }
        if ($redis->set($redisKey, $expireAt, ['NX', 'EX' => $realExpireAfterSeconds])) {
            // $redis->expireAt($redisKey, $expireAt);
            $result = true;
        } elseif ($timestamp > $redis->get($redisKey) && $timestamp > $redis->getSet($redisKey, $expireAt)) {
            $redis->expire($redisKey, $realExpireAfterSeconds);
            $result = true;
        }
        return $result;
    }

    /**
     * 设置锁使用的分区.
     *
     * @param integer $partition 分区标识.
     *
     * @return \Utils\Locker\Redis
     */
    public function partition($partition)
    {
        $this->partition = $partition;
        return $this;
    }

    /**
     * 获取当前设置的分区, 获取之后会清空属性上的分区设置.
     *
     * @return integer
     */
    public function getPartition()
    {
        $partition = $this->partition;
        $this->partition = null;
        return $partition;
    }

    /**
     * 生成redis的key.
     *
     * @return string
     */
    private function genKey()
    {
        return \Config\Locker::$redis['prefix'] . '_' . md5(implode('_', func_get_args()));
    }

    /**
     * 解锁.
     *
     * @param string $prefix 锁前缀.
     * @param string $key    锁的key.
     *
     * @return integer 解锁成功返回1, 没有需要解锁的返回0.
     */
    public function unlock($prefix, $key)
    {
        $partition = $this->getPartition();// 保险起见在第一行就重置$this->partition为null.
        $redisCfgName = \Config\Locker::$redis['name'];
        if (is_null($partition)) {
            $redis = \Redis\RedisMultiCache::getInstance($redisCfgName);
        } else {
            $redis = \Redis\RedisMultiCache::getInstance($redisCfgName)->partitionByUID($partition);
        }
        return $redis->del($this->genKey($prefix, $key));
    }

}
