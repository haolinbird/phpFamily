<?php
/**
 * 带共享内存的错误高可用配置管理.
 *
 * @author xudongw <xudongw@jumei.com>
 */

namespace SharedMemory;

class HaConfig {
    const REFRESH_BAD_CONFIG_INTERVAL   = 3;
    const PUNISH_BAD_CONFIG_DURATION    = 5;
    /**
     * 坏节点最多在共享内存生存周期(为了保持共享内存与业务配置同步)，超出之后直接剔除.
     */
    const MAX_BAD_CONFIG_TIME           = 3600;
    protected static $configs;
    protected static $badConfigs = array();
    protected static $lastFreshBadConfigsTime = 0;

    /**
     * shared memory handler.
     *
     * @var \SharedMemory\Shm
     */
    protected $shm;

    public function __construct($IPCKey) {
        $this->shm = new Shm($IPCKey);
    }

    public function findOneConfig($configs, &$isRecovered = false) {
        $isRecovered = false;
        // 首先尝试从共享内存同步黑名单
        // 如果每次都从共享内存去查黑名单，对性能有较大影响.
        $this->tryFreshBadConfigs();

        // 每5s尝试恢复一个坏节点。
        $config = $this->recoverOneConfig($configs);
        if (!empty($config)) {
            $isRecovered = true;
            return $config;
        }

        // 没有可以恢复的节点，找到一个正常的节点
        $configsRelica = $configs;
        while (!empty($configsRelica)) {
            $config = $this->getOneNode($configsRelica, true);
            if (!$this->isBadConfig($config)) {
                return $config;
            }
        }

        // 如果都坏了，好吧，随便找一个dsn
        $randConfig = $configs[array_rand($configs)];

        return $randConfig;
    }

    /**
     * 剔除一个无法链接的节点
     *
     * @param array $config 需要剔除的配置.
     *
     * @return void
     */
    public function kickConfig($config) {
        try{
            $this->shm->lockAndAttach();
            $badConfigs = @$this->shm->getArrVar();

            // avoid push same config
            if (!$this->isInConfig($config, $badConfigs)) {
                // push到队尾
                $badConfigs[] = array(
                    'host'      => $config['host'],
                    'port'      => $config['port'],
                    'kick_time' => time(),
                );

            }
            $this->shm->putVar($badConfigs);
        } catch (\Exception $ex){
            if($this->shm->_isLocked()){
                $this->shm->unlockAndDettach();
            }
            throw $ex;
        }
        self::$badConfigs = $badConfigs;
    }

    protected function tryFreshBadConfigs() {
        $ts =time();
        if ( $ts - self::$lastFreshBadConfigsTime >= self::REFRESH_BAD_CONFIG_INTERVAL) {
            try{
                $this->shm->lockAndAttach();
                self::$badConfigs = @$this->shm->getArrVar();

                $this->shm->unlockAndDettach();
                self::$lastFreshBadConfigsTime = $ts;

            } catch (\Exception $ex){
                if($this->shm->_isLocked()){
                    $this->shm->unlockAndDettach();
                }
                throw $ex;
            }
        }
    }

    protected function recoverOneConfig($configs) {
        //没有坏节点，不管了
        if (empty(self::$badConfigs)) {
            return false;
        }

        // 从队首拿出一个节点，（如果仍然是坏的，重试后会再放进队尾）
        $now = time();
        $recoverConfig = array();
        // 是否需要刷新共享内存（为了提高效率，大部分请求并不需要刷新共享内存，而刷新shm需要加锁，对性能影响很大）
        $bNeedFreshShm = false;
        foreach (self::$badConfigs as $key => $badconfig) {
            $timeInterval =  $now - $badconfig['kick_time'];

            // 超过一定时限的黑名单，直接删掉.
            if ($timeInterval >= self::MAX_BAD_CONFIG_TIME) {
                unset(self::$badConfigs[$key]);
                $bNeedFreshShm = true;
                continue;
            }

            if ($timeInterval >= self::PUNISH_BAD_CONFIG_DURATION) {
                // 如果dsn已经不在目标中, 有两种可能 1.这个坏节点不属于这个config集合，2.这个坏节点已经被除名了.两种case都不用处理.
                if (!$this->isInConfig($badconfig, $configs)) {
                    continue;
                }
                unset(self::$badConfigs[$key]);
                $bNeedFreshShm = true;

                $recoverConfig = $badconfig;
                break;
            }
        }

        if ($bNeedFreshShm) {
            // 刷新共享内存
            $this->shm->lockAndAttach();
            try {
                $this->shm->putVar(self::$badConfigs);
            }catch (\Exception $ex){
                if($this->shm->_isLocked()){
                    $this->shm->unlockAndDettach();
                }
                throw $ex;
            }
        }

        return $recoverConfig;
    }

    protected function isBadConfig($config) {
        return $this->isInConfig($config, self::$badConfigs);
    }

    /**
     * 按权重随机數組中的一個.
     *
     * @param array $arr         备选目标
     * @param bool $unsetChoosed 被选中后，是否从备选目标中清掉该节点
     *
     * @return array
     */
    protected function getOneNode(array &$arr, $unsetChoosed = false){
        if (count($arr) == 0 ) {
            return array();
        }

        $weight = array();
        $count = 0;

        foreach( $arr as $k => $v){
            if (!isset($v['weight'])) {
                $v['weight'] = 1;
            }
            $count += (int)$v['weight'];
            $weight[$k] = $count;
        }

        $one = mt_rand(1,$count);
        foreach( $weight as $k => $v){
            if ( $one <= $v){
                $ret = $arr[$k];
                if ($unsetChoosed) {
                    unset($arr[$k]);
                }
                return $ret;
            }
        }
        return array();
    }

    protected function isInConfig($needle, $haystack) {
        foreach ($haystack as $item) {
            if ($item['host'] == $needle['host'] && $item['port'] == $needle['port']) {
                return true;
            }
        }
        return false;
    }

    public static function formatAddress($address) {
        $arr = explode(':', $address);

        if (count($arr) < 2 || count($arr) > 3) {
            throw new \Exception("地址[$address]配置格式错误，正确格式为ip:port[:weight]");
        }
        return array(
            'host'      => $arr[0],
            'port'      => $arr[1],
            'weight'    => isset($arr[2]) ? $arr[2] : 1,
        );
    }

}