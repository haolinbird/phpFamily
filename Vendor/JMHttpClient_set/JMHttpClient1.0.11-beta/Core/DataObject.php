<?php
/**
 * Data Object.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace JMHttpClient\Core;

use JMHttpClient\Exception\DataException;

/**
 * Data Object.
 */
class DataObject
{
    protected $url = null;
    protected $method = null;
    protected $data = array();
    protected $curlOpts = array();

    /**
     * Setter.
     *
     * @param string $key   Key.
     * @param string $value Value.
     *
     * @return void
     */
    public function __set($key, $value)
    {
        if (! property_exists($this, $key)) {
            throw new DataException("当前不支持设置{$key}属性");
        }

        if ($key == 'url') {
            $urlInfo = parse_url($value);
            if (isset($urlInfo['host'])) {
                throw new DataException("host已经在分组中确定, url中制定的host是无效的");
            }
        } else if ($key == 'curlOpts' && ! is_array($value)) {
            throw new DataException("{$key}的取值必须是一个数组");
        } else if ($key == 'method') {
            $value = strtolower($method);
            if (! in_array($value, array('get', 'post'))) {
                throw new DataException("当前只支持get/post方法");
            }
        }

        $this->$key = $value;
    }

    /**
     * Getter.
     *
     * @param string $key Key.
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }

        return null;
    }
}