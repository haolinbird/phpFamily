<?php
namespace MNLogger;

class SlowLogger extends Base
{
    protected static $filePermission = 0777;
    protected $_logdirBaseName = 'slow';
    protected static $configs=array();
    protected static $instance = array();

    // 因为兼容性的原因保留该参数, 但不使用.
    public static function instance($config='slow')
    {
        $config = static::getRecommendConfig('slow2');
        return parent::instance($config);
    }

    public function log($data)
    {
        if ($this->_on === self::OFF) {
            return;
        }

        if (! isset(static::$log_buffer['global_span'])) {
            return;
        }

        static::$log_buffer['global_span']['binary_annotations'][] = array(
            'type' => 'slow',
            'key' => 'slow',
            'value' => '',
        );

        $traceId = static::$log_buffer['global_span']['trace_id'];
        $spanId = static::$log_buffer['global_span']['id'];

        $type = 'SLOW';
        if (! is_string($data) && ! is_numeric($data)) {
            $data = $this->serializeData($data);
        }

        // 资源层日志有抽样才会记录到全链路日志,针对这个情况进行改进:只要有慢日志则记录下所有资源层的访问日志.
        if (! empty(static::$log_buffer['global_span']['binary_annotations'])) {
            $resourceLogs = array();
            foreach (static::$log_buffer['global_span']['binary_annotations'] as $logs) {
                if (in_array(strtolower($logs['type']), array('redis', 'mysql', 'rabbitmq', 'mc'))) {
                    $resourceLogs[] = $logs;
                }
            }

            if ($resourceLogs) {
                $data .= ";RES CALL:" . $this->serializeData($resourceLogs);
            }
        }

        $time = date('Y-m-d H:i:s');
        $line = "OWL\001SLOW\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001SLOW\001{$traceId}\001{$spanId}\001{$type}\001{$data}\004\n";

        $this->_logFilePath = $this->getLogFilePath();
        $this->send($this->_logFilePath, $line);
    }
}
