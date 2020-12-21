<?php
namespace Memcache;

class Connection extends \Memcache{
    const BACKUP_DATA_LOCK_TIME = 20;
    const INTERVAL_SYNC_FROM_CLUSTER = 10;
    private $keyPrefix='';
    private $smartForceRefresh=false;
    private $backupcacheKeySuffix='_backup';
    protected $dsn;
    /**
     * 备份缓存服务器实例
     * @var \Memcache\Connection
     */
    private $backupInstance;
    protected function makeRealKey($key)
    {
        if(is_array($key))
        {
            foreach($key as $k =>$kv)
            {
                if(strlen($kv) > 250)
                    trigger_error("Memcache Key [$kv] is longer than 250", E_USER_WARNING);
                $key[$k] = $this->keyPrefix.$kv;
            }
            return $key;
        }
        if(strlen($key) > 250)
            trigger_error("Memcache Key [$key] is longer than 250", E_USER_WARNING);
        return $this->keyPrefix . $key;
    }

    protected function getBackupKey($key)
    {
        return $key.$this->backupcacheKeySuffix;
    }

    public function getDsn()
    {
        return $this->config;
    }

    public function setDsn($dsn)
    {
        $this->dsn = $dsn;
    }

    /**
     * 由于采用了双层二级缓存,最长缓存时间可能是原来的两倍.当key为数组时,无二级换成特性。
     * @param string $key
     * @param int $status {@link self::getFromLocal}
     * @see Memcache::get()
     * @todo 此处 本地/集群 缓存读取协调可能还可以优化
     * @return mixed
     */
    public function get($key, &$status=0)
    {
        $value = $this->getFromLocal($key, $status);
        if($value !== false || !$this->backupInstance || is_array($key))
        {//本地缓存获取成功, 或者无备份缓存服务器实例(集群)
            return $value;
        }

        //本地缓存失效, 尝试从备份缓存服务器实例(集群)获取
        $content = $this->backupInstance->get($key, $statusBackup);
        if(false === $content)
        {// 需溯源并更新缓存
            return false;
        }

        //使用集群缓存更新本地缓存，此后大部分应用服务器本地缓存只能定期从集群服务器更新。
        //@todo 以后可以考虑将将原始ttl保存至数据中, 以实现从集群同步后依然能正常过期。
        $realKey = $this->makeRealKey($key);
        parent::set($realKey, $content, MEMCACHE_COMPRESSED, self::INTERVAL_SYNC_FROM_CLUSTER);
        parent::set($this->getBackupKey($realKey), array('refreshing'=>False, 'refreshing_begin'=>0, 'data'=>$content), MEMCACHE_COMPRESSED, 0);

        return $content;
    }

    /**
     *
     * @param string $key 当key为数组时,无二级换成特性。
     * @param int $status  本地缓存的状态: 0 主缓存正常，无需更新; 1 主、副缓存失效,应当立即更新; 2 主缓存失效、且正在更新中; 3 主缓存失效(或者前一进程更新失败),副缓存存在,但需要更新.
     * @return Ambigous <boolean, string>
     */
    public function getFromLocal($key, &$status=0)
    {
        $key = $this->makeRealKey($key);
        $callTrace = $this::getExternalCaller();
        \MNLogger\TraceLogger::instance('trace')->MC_CS($this->dsn, $callTrace['class'] . ':' . $callTrace['method'], $key);
        $mem0 = memory_get_usage();
        $value = parent::get($key);
        $mem1 = memory_get_usage();
        if (!is_array($key)) {
            if (false === $value) { //主缓存已经过期
                $this->logException();
                $backupKey = $this->getBackupKey($key);
                $mem0 = memory_get_usage();
                $backupValue = parent::get($backupKey);
                $mem1 = memory_get_usage();
                if (isset($backupValue['refreshing'])) { //副缓存已经存在
                    if ($backupValue['refreshing'] && isset($backupValue['refreshing_begin']) && microtime(true) - $backupValue['refreshing_begin'] < self::BACKUP_DATA_LOCK_TIME) { //已经有进程在刷新缓存. 并且更新持续时间在120秒内(否则认为前一进程更新缓存失败,将走更新缓存的流程),则使用副缓存数据.
                        $status = 2;
                        $value = $backupValue['data'];
                    } else { //第一个进程将缓存设置为更新中, 并返回false,使接下的的过程认为缓存已经失效,进而更新缓存. 而其它进程将继续从副缓存中读取数据.
                        $backupValue['refreshing'] = True;
                        $backupValue['refreshing_begin'] = microtime(true);
                        //副缓存的数据永不过期.
                        parent::set($backupKey, $backupValue, MEMCACHE_COMPRESSED, 0);
                        $status = 3;
                        $value = false;
                    }

                } else { //从未缓存过(本地缓存彻底失效)
                    $status = 1;
                    $value = false;
                }
            }
        }
        \MNLogger\TraceLogger::instance('trace')->MC_CR($value === false ? 'MISSED' : 'SUCCESS', $mem1-$mem0, '');
        return $value;
    }

    public function setKeyPrefix($keyPrefix) {
        $this->keyPrefix = $keyPrefix;
    }

    public function setSmartForceRefresh($enabled) {
        $this->smartForceRefresh = $enabled;
    }

    public function smartGet($key, $ttl, $function, $params = array()) {
        $value = $this->get($key);
        if ($value === false) {
            $value = call_user_func_array($function, $params);
            $this->set($key, $value, 0, $ttl);
        }
        return $value;
    }

    /**
     * @param $key
     * @param $value
     * @param null $flag
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $flag=null, $ttl=null) {
        if(func_num_args() < 4 && is_int($flag))
        {//兼容以前的调用方法
            $ttl = $flag;
            $flag = False;
        }
        static $randTtl;
        if($randTtl === null)
        {//根据服务器名称, 设置缓存失效时间差异(小于3秒), 尽量避免所有节点的缓存同时失效.
            $randTtl = floatval(rand(0, 5).'.'.rand(1000,9999));
        }
        if($ttl > 0)
        {
            $ttl += $randTtl;
        }
        $keyRaw = $key;
        $key = $this->makeRealKey($key);
        $done = parent::set($key, $value, $flag, $ttl);
        if($done === false)
        {
            $this->logException();
        }
        //写入副缓存
        parent::set($this->getBackupKey($key), array('refreshing'=>False, 'refreshing_begin'=>0, 'data'=>$value), MEMCACHE_COMPRESSED, 0);
        $backupDone = true;
        if($this->backupInstance)
        {//写入集群缓存
            $backupDone = $this->backupInstance->set($keyRaw, $value, $flag, $ttl);
        }
        else
        {
            $backupDone = false;
        }
        return $done || $backupDone;
    }

    public function delete($key) {
        $backupDelete = true;
        if($this->backupInstance)
        {
            $backupDelete = $this->backupInstance->delete($key);
        }
        $key = $this->makeRealKey($key);
        $del = parent::delete($key);
        if($del === false)
        {
            $this->logException();
        }
        $delLocalBackup = parent::delete($this->getBackupKey($key));
        return $del && $delLocalBackup && $backupDelete;
    }

    /**
     *
     * @param string $key
     * @param int $value
     * @return number
     * @todo 多备份实例上无原子操作
     */
    public function increase($key, $value=1) {
        if($this->backupInstance)
        {//集群操作
            $backupReturn = $this->backupInstance->increase($key, $value);
        }
        $key = $this->makeRealKey($key);
        $return = parent::increment($key, $value);
        if($return)
        {
            $newValue = parent::get($key);
            if(false !== $newValue)
            {
                $backupValue['data'] = $newValue;
                parent::set($this->getBackupKey($key), $backupValue, MEMCACHE_COMPRESSED, 0);
            }
        }
        else if($return === false)
        {
            $this->logException();
        }
        return $return;
    }

    /**
     *
     * @param string $key
     * @param int $value
     * @return number
     * @todo 多备份实例上无原子操作
     */
    public function decrease($key, $value=1) {
        if($this->backupInstance)
        {
            $backupReturn = $this->backupInstance->decrease($key, $value);
        }
        $key = $this->makeRealKey($key);
        $return = parent::decrement($key, $value);
        if($return === false)
        {
            $this->logException();
            $newValue = parent::get($key);
            if(false !== $newValue)
            {
                $backupValue['data'] = $newValue;
                parent::set($this->getBackupKey($key), $backupValue, MEMCACHE_COMPRESSED, 0);
            }
        }
        return $return;
    }

    /**
     *
     * @param self $instance
     */
    public function setBackupInstance(self $instance)
    {
        $this->backupInstance = $instance;
    }

    public function getBackupInstance()
    {
        return $this->backupInstance;
    }

    public function close(){
        parent::close();
        if($this->backupInstance)
        {
            $this->backupInstance->close();
        }
    }

    /**
     * 判断memcache错误并记录Exception至监控日志。需在\Config\MNLogger中配置exception一项。
     *
     * @uses \MNLogger\EXLogger,\Config\MNLogger
     */
    public function logException()
    {
        $error = error_get_last();
        if($error && strpos($error['message'], 'Memcache::') === 0)
        {
            \MNLogger\EXLogger::instance()->log(new Exception('Memcache client encountered an error: '.$error['message'].' in '.$error['file'].' on line '.$error['line'].'. This exception won\'t be thrown, it\'s just for MNLogger\EXLogger.' ));
        }
    }

    public static function getExternalCaller()
    {
        $trace = debug_backtrace(false);
        $caller = array('class'=>'', 'method'=>'', 'file'=>'', 'line'=>'');
        $k = 0;
        foreach($trace as $k => $line)
        {
            if(isset($line['class']) && stripos($line['class'], __NAMESPACE__) === 0)
            {
                continue;
            }
            else if(isset($line['class']))
            {
                $caller['class'] = $line['class'];
                $caller['method'] = $line['function'];
            }
            else if(isset($line['function']))
            {
                $caller['method'] = $line['function'];
            }
            else
            {
                $caller['class'] = 'main';
            }
            break;
        }
        if(empty($caller['method']))
        {
            $caller['method'] = 'main';
        }

        while (!isset($line['file']) && $k > 0) {// 可能在eval或者call_user_func里调用的。
            $line = $trace[--$k];
        }
        $caller['file'] = $line['file'];
        $caller['line'] = $line['line'];
        return $caller;
    }
}
