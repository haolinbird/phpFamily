<?php
/**
 * 一致性session.
 * 
 * @author JICHAOW <jichaow2@jumei.com>
 */

/**
 * 一致性session.
 */
class Utility_UniqueSession
{
    const SESSION_NAME = 'PHPSESSID';
    const MEMCACHE_SESSION_KEY = 'cart_session_';
    protected static $instance;
    protected $memcache;

    /**
     * 初始化.
     *
     * @param mixed $params 初始化参数.
     */
    protected function __construct($params)
    {
        $sessionName = self::SESSION_NAME;
        session_name($sessionName);
        $this->setParams($params);
        if (!session_id()) {
            session_start();
        }
    }

    /**
     * 初始化.
     *
     * @param array $params 参数.
     * 
     * @return JMSession
     */
    public static function instance(array $params = array())
    {
        if (!self::$instance) {
            self::$instance = new static($params);
        }
        // self::$instance->merge();
        // 合并信息呢.
        return self::$instance;
    }

    // /**
    //  * Get/Set name for the current session.
    //  *
    //  * @param string $name session name.
    //  * @return string
    //  */
    // public function name($name=null)
    // {
    //     if(is_null($name))return session_name();
    //     return session_name($name);
    // }

    /**
     * 取会话id.
     *
     * @param string $id Id名.
     * 
     * @return string
     */
    public function id($id = null)
    {
        if(is_null($id))return session_id();
        return session_id($id);
    }

    /**
     * 取当前uid.
     *
     * @return string
     */
    public function uid()
    {
        $uid = 0;
        if (JMRegistry::isRegistered('UID')) {
            $uid = JMRegistry::get('UID');
        }
        return $uid;
    }

    /**
     * 获取唯一key.
     *
     * @return string
     */
    public function uniqueKey()
    {
        if (($key = $this->uidKey()) == false) {
            $key = $this->sessionKey();
        }
        return $key;

    }

    /**
     * 取SESSION Key.
     *
     * @return string.
     */
    public function sessionKey()
    {
        if ( ($id = $this->id()) != null) {
            return self::MEMCACHE_SESSION_KEY.$id;
        }
        return '';
    }

    /**
     * 取uidKey.
     *
     * @return string.
     */
    public function uidKey()
    {
        if ( ($uid = $this->uid()) != null) {
            return self::MEMCACHE_SESSION_KEY.$uid;
        }
        return '';
    }

    /**
     * 合并数据.
     *
     * @return array
     */
    public function merge()
    {
        $sessionData = $this->getCacheData($this->sessionKey());
        $uidData = $this->getCacheData($this->uidKey());
        $mergeData = array();
        if ($sessionData && $uidData) {
            // 以登陆数据为准
            $mergeData = array_merge($sessionData, $uidData);
        } else if ($sessionData) {
            $mergeData = $sessionData;
        } else if ($uidData) {
            $mergeData = $uidData;
        }
        $this->save($mergeData);
        return $mergeData;
    }

    /**
     * 存储数据.
     *
     * @param mixed $data 存储.
     *
     * @return void
     */
    public function save($data)
    {
        if ($this->uniqueKey()) {
            $this->setCacheData($this->uniqueKey(), $data);
        }
    }

    /**
     * 取数据.
     *
     * @param string $name 键名.
     *
     * @return mixed 值.
     */
    public function &__get($name)
    {
        $mergeData = $this->merge();
        if (isset($mergeData[$name])) {
            return $mergeData[$name];
        }
        $null;
        return $null;
    }

    /**
     * 保存.
     *
     * @param string $name  名称.
     * @param mixed  $value 值.
     *
     * @return mixde
     */
    public function &__set($name, $value)
    {
        $mergeData = $this->merge();
        $mergeData[$name] = $value;
        $this->save($mergeData);
        return  $mergeData[$name];
    }

    /**
     * 对session的cookie进行设置.
     * 
     * Set cookie params. Refer to {@link session_set_cookie_params}.
     * 
     * All options are:
     * e.g.
     * <pre>
     * array('lifetime' =>0,
     *       'path' => '/',
     *       'domain' => '.domain.net',
     *       'secure' => false,
     *       'httponly' => false
     *      )
     * <pre>
     *
     * @param array $params 配置参数.
     * 
     * @global $serverConfig['session']
     *
     * @return void
     */
    protected function setParams(array $params = array())
    {
        $serverConfig = JMRegistry::get('serverConfig');
        $defaultOptions = array('lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false);
        if (isset($serverConfig['session']) && is_array($serverConfig['session'])) {
            $defaultOptions = array_merge($defaultOptions, $serverConfig['session']);
        }
        $options = array_merge($defaultOptions, $params);
        session_set_cookie_params($options['lifetime'], $options['path'], $options['domain'], $options['secure'], $options['httponly']);
    }

    /**
     * 从数据中删除项.
     * 
     * @param string|array $name 要删除的项，支持数组.
     *
     * @return void
     */
    public function del($name)
    {
        $mergeData = $this->merge();
        if (is_array($name)) {
            foreach ($name as $itemKey) {
                unset($mergeData[$itemKey]);
            }
        } else {
            unset($mergeData[$name]);
        }
        $this->save($mergeData);
    }

    /**
     * 判断是否有值.
     *
     * @param string $name 名称.
     * 
     * @return boolean
     */
    public function exists($name)
    {
        $mergeData = $this->merge();
        return isset($mergeData[$name]);
    }

    /**
     * 清除cache数据.
     *
     * @param string $key 对应的Key.
     *
     * @return boolean
     */
    public function delCacheData($key)
    {
        return $this->getMemcache()->del($key);
    }

    /**
     * 取缓存数据.
     *
     * @param string $key 对应的Key.
     *
     * @return array
     */
    public function getCacheData($key)
    {
        if (!$key) {
            return array();
        }
        $data = $this->getMemcache()->get($key);
        if ($data) {
            $data = unserialize($data);
        } else {
            $data = array();
        }
        return $data;
    }

    /**
     * 保存数据.
     *
     * @param string $key  使用的Key.
     * @param mixed  $data 存储的数据.
     *
     * @return boolean
     * @throws Exception If 参数错误.
     */
    public function setCacheData($key, $data)
    {
        if (!$key) {
            throw new Exception("unique session key could not be empty");
        }
        $data = serialize($data);
        return $this->getMemcache()->set($key,$data,7 * 24 * 3600);
    }

    /**
     * 取memcache.
     *
     * @return [type] [description]
     */
    public function getMemcache()
    {
        return $this->memcache = \Memcache\Pool::instance('session');
    }

}
