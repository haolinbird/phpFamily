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
class DataObject extends \stdClass
{
    protected $url = null;
    protected $method = null;
    protected $data = array();
    protected $headers = array();
    protected $curlOpts = array();
    public $force = true;

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
        if ($this->force) {
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