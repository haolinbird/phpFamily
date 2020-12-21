<?php
/**
 * Job生产者.
 *
 * @author xianwangs <xianwangs@jumei.com>
 */

namespace Kafka;

/**
 * Productor.
 */
class Producer extends \RdKafka\Producer
{
    protected static $instances = array();

    /**
     * Get producer.
     *
     * @param string $name Cfg name.
     *
     * @return \RdKafka\Producer
     */
    public static function instance($name = 'default')
    {
        static $checkEnv = true;
        if ($checkEnv) {
            $checkEnv = false;
            if (! extension_loaded('rdkafka')) {
                throw new Exception('Please open kafka support', 1);
            }
        }
        
        if (empty($name)) {
            if (! isset(static::$instances['*'])) {
                static::$instances['*'] = new static;
            }

            return static::$instances['*'];
        } else {
            if (! isset(static::$instances[$name])) {
                $kafkaConf = new \Config\Kafka();
                $conf = new \RdKafka\Conf();

                foreach ($kafkaConf->$name as $k => $v) {
                    if ($v instanceof \Closure || $v instanceof \RdKafka\TopicConf) {
                        $conf->$k($v);
                    } else {
                        $conf->set($k, $v);
                    }
                }

                static::$instances[$name] = new static($conf);
            }

            return static::$instances[$name];
        }
    }

    /**
     * Constructor.
     */
    public function __construct(\RdKafka\Conf $conf = null)
    {
        parent::__construct($conf);
    }
}
