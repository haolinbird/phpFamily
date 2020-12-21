<?php
namespace MNLogger;

class TraceLogger extends Base
{
    protected static $filePermission = 0777;
    protected $_logdirBaseName = 'trace2';
    protected static $configs=array();
    protected static $instance = array();

    // 因为兼容性的原因保留该参数, 但不使用.
    public static function instance($config='trace2')
    {
        $config = static::getRecommendConfig('trace2');
        return parent::instance($config);
    }

    public function RPC_SR($service, $method, $params)
    {
        $this->SERVICE_RECEIVE($service, $method);
    }

    public function RPC_SS($response_type, $response_data_size, $response_data = '')
    {
        $this->SERVICE_SEND($response_type, $response_data);
    }

    public function HTTP_SR()
    {
        $this->SERVICE_RECEIVE(
            'unknown',
            $_SERVER['REQUEST_URI'] . '(' . strtoupper($_SERVER['REQUEST_METHOD']) . ')'
        );
    }

    public function HTTP_SS($response_type, $response_data_size, $response_data = '')
    {
        $this->SERVICE_SEND($response_type, $response_data);
    }

    public function HTTP_SERVICE_SR()
    {
        $this->SERVICE_RECEIVE(
            'unknown',
            $_SERVER['REQUEST_URI'] . '(' . strtoupper($_SERVER['REQUEST_METHOD']) . ')'
        );
    }

    public function HTTP_SERVICE_SS($response_type, $response_data_size, $response_data = '')
    {
        $this->SERVICE_SEND($response_type, $response_data);
    }

    // 服务端接收（使用调用者的span).
    protected function SERVICE_RECEIVE($service, $method)
    {
        global $owl_context;

        static::reset();

        static::$log_buffer['global_span'] = array(
            'parent_id' => isset($owl_context['parent_id']) ? $owl_context['parent_id'] : '',
            'serviceId' => $service,
            'spanName' => $method,
            'ip' => $this->_ip,
            'annotations' => array(
                array(
                    'timestamp' => $this->microTimeStamp(),
                    'value' => 'sr',
                ),
            ),
            'binary_annotations' => array(),
        );

        if (isset($owl_context['trace_id'])) {
            static::$log_buffer['global_span']['trace_id'] = $owl_context['trace_id'];
        } else if (isset($_SERVER['HTTP_TRACE_ID'])) {
            static::$log_buffer['global_span']['trace_id'] = $_SERVER['HTTP_TRACE_ID'];
        } else {
            static::$log_buffer['global_span']['trace_id'] = uniqid('trace-' . microtime(true) . '-' . mt_rand(0, 10000) . '-');
        }

        if (isset($owl_context['span_id'])) {
            static::$log_buffer['global_span']['id'] = $owl_context['span_id'];
        } else if (isset($_SERVER['HTTP_REQUEST_ID'])) {
            static::$log_buffer['global_span']['id'] = $_SERVER['HTTP_REQUEST_ID'];
        } else {
            static::$log_buffer['global_span']['id'] = uniqid('span-' . microtime(true) . '-' . mt_rand(0, 10000) . '-');
        }

        if (isset($owl_context['is_sample'])) {
            static::$log_buffer['global_span']['sample'] = $owl_context['is_sample'];
        } else if (isset($_SERVER['HTTP_IS_SAMPLE'])) {
            static::$log_buffer['global_span']['sample'] = $_SERVER['HTTP_IS_SAMPLE'];
        } else if (static::$log_buffer['global_span']['parent_id'] == '') {
            // context 与 header 中都没有抽样标识的时候，才允许根据节点自身的规则计算是否抽样.
            // 只有链路中的第一个节点有资格计算抽样规则.
            static::$log_buffer['global_span']['sample'] = $this->isSample() ? 1 : 0;
        } else {
            static::$log_buffer['global_span']['sample'] = 0;
        }

        $owl_context['trace_id'] = static::$log_buffer['global_span']['trace_id'];
        $owl_context['parent_id'] = static::$log_buffer['global_span']['parent_id'];
        $owl_context['span_id'] = static::$log_buffer['global_span']['id'];
        $owl_context['is_sample'] = static::$log_buffer['global_span']['sample'];
    }

    // 服务端发送（使用调用者的span).
    protected function SERVICE_SEND($response_type, $response_data)
    {
        if (! isset(static::$log_buffer['global_span'])) {
            return;
        }

        if (strtolower($response_type) == strtolower(static::T_EXCEPTION)) {
            // EXLogger::instance()->trace2Log('ERROR', $response_data);
            static::switchToExceptionStatus();
        }

        static::$log_buffer['global_span']['annotations'][] = array(
            'timestamp' => $this->microTimeStamp(),
            'value' => 'ss',
        );
    }

    public function RPC_CS($end_point, $service, $method, $params)
    {
        $this->REMOTECALL_CS($service, $method);
    }

    public function RPC_CR($response_type, $response_data_size, $response_data = '')
    {
        $this->REMOTECALL_CR($response_type, $response_data);
    }

    public function HTTP_CS($url, $method, $data)
    {
        $this->REMOTECALL_CS($url, $method);
    }

    public function HTTP_CR($response_type, $response_data_size, $response_data = '')
    {
        $this->REMOTECALL_CR($response_type, $response_data);
    }

    // RPC Client Send(新生成span，parent为父span的id).
    protected function REMOTECALL_CS($service, $method)
    {
        global $owl_context;

        if (! isset(static::$log_buffer['global_span'])) {
            return;
        }

        if (! isset(static::$log_buffer['spans'])) {
            static::$log_buffer['spans'] = array();
        }

        // 产生一个新的span.
        $spanId = uniqid('span-' . microtime(true) . '-' . mt_rand(0, 10000) . '-');

        static::$log_buffer['spans'][] = array(
            'trace_id' => static::$log_buffer['global_span']['trace_id'],
            'serviceId' => $service,
            'spanName' => $method,
            'ip' => $this->_ip,
            'id' => $spanId,
            'parent_id' => static::$log_buffer['global_span']['id'],
            'annotations' => array(
                array(
                    'timestamp' => $this->microTimeStamp(),
                    'value' => 'cs'
                ),
            ),
            'binary_annotations' => array(),
            'sample' => static::$log_buffer['global_span']['sample'],
        );

        // 修改rpc请求的上下文.
        $owl_context['parent_id'] = static::$log_buffer['global_span']['id'];
        $owl_context['span_id'] = $spanId;
    }

    // RPC Client Receive.
    protected function REMOTECALL_CR($response_type, $response_data)
    {
        if (! isset(static::$log_buffer['global_span'])) {
            return;
        }

        if (strtolower($response_type) == strtolower(static::T_EXCEPTION)) {
            if (is_array($response_data)) {
                $response_data = $this->serializeData($response_data);
            } else if ($response_data instanceof \Exception) {
                $response_data = $response_data->__toString();
            }

            // 如果response_data是未知类型的对象, 不做处理.
            if (is_string($response_data) && $this->binary_annotations_max_size > 0 && strlen($response_data) > $this->binary_annotations_max_size) {
                $response_data = substr($response_data, 0, $this->binary_annotations_max_size) . '...';
            }

            // EXLogger::instance()->trace2Log('ERROR', $response_data);
            static::switchToExceptionStatus();
            static::$log_buffer['spans'][count(static::$log_buffer['spans']) - 1]['binary_annotations'][] = array(
                'type' => 'exception',
                'key' => 'rpc.exception',
                'value' => $response_data,
            );
        }

        static::$log_buffer['spans'][count(static::$log_buffer['spans']) - 1]['annotations'][] = array(
            'timestamp' => $this->microTimeStamp(),
            'value' => 'cr',
        );
    }

    public function REDIS_CS($end_point, $method, $query) 
    {
        $this->RES_CS(__FUNCTION__, $end_point, $method, $query);
    }

    public function REDIS_CR($response_type, $response_data_size, $response_data = '')
    {
        $this->RES_CR(__FUNCTION__, $response_type, $response_data_size, $response_data);
    }

    public function MYSQL_CS($end_point, $method, $sql, $sql_id = '')
    {
        $this->RES_CS(__FUNCTION__, $end_point, $method, $sql);
    }

    public function MYSQL_CR($response_type, $response_data_size, $response_data = '')
    {
        $this->RES_CR(__FUNCTION__, $response_type, $response_data_size, $response_data);
    }

    public function RABBITMQ_CS($end_point, $method, $data)
    {
        $this->RES_CS(__FUNCTION__, $end_point, $method, $data);
    }

    public function RABBITMQ_CR($response_type, $response_data_size, $response_data = '')
    {
        $this->RES_CR(__FUNCTION__, $response_type, $response_data_size, $response_data);
    }

    public function MC_CS($end_point, $method, $query)
    {
        $this->RES_CS(__FUNCTION__, $end_point, $method, $query);
    }

    public function MC_CR($response_type, $response_data_size, $response_data)
    {
        $this->RES_CR(__FUNCTION__, $response_type, $response_data_size, $response_data);
    }

    // 资源层CS.
    protected function RES_CS($func, $end_point, $method, $data)
    {
        if (! isset(static::$log_buffer['global_span'])) {
            return;
        }

        $data = $this->serializeData($data);
        $tmp = explode('_', $func);

        // 日志收集方反馈res访问value过大, 因此需要限制.
        $requestData = "$end_point\r\n$method\r\n$data";
        if ($this->binary_annotations_max_size > 0 && strlen($requestData) > $this->binary_annotations_max_size) {
            $requestData = rtrim(substr($requestData, 0, $this->binary_annotations_max_size), "\r\n") . '...';
        }

        static::$resCsTimestamp[strtolower($tmp[0])] = array(
            'timestamp' => $this->microTimeStamp(),
            'request_data' => $requestData,
        );
    }

    // 资源层CR.
    protected function RES_CR($func, $response_type, $response_data_size, $response_data)
    {
        if (! isset(static::$log_buffer['global_span'])) {
            return;
        }

        $tmp = explode('_', $func);
        // 没有记录下CS，这时候禁止调用CR.
        if (! isset(static::$resCsTimestamp[strtolower($tmp[0])])) {
            return;
        }

        if (strtolower($response_type) == strtolower(static::T_EXCEPTION)) {
            EXLogger::instance()->trace2Log('ERROR', $response_data);
            static::switchToExceptionStatus();
            static::$log_buffer['global_span']['binary_annotations'][] = array(
                'type' => strtolower($tmp[0]),
                'key' => "exception",
                'value' => static::$resCsTimestamp[strtolower($tmp[0])]['request_data'],
            );
            return;
        }

        static::$log_buffer['global_span']['binary_annotations'][] = array(
            'type' => strtolower($tmp[0]),
            'key' => "elapsed",
            'value' => 'elapsed:' . ($this->microTimeStamp() - static::$resCsTimestamp[strtolower($tmp[0])]['timestamp']) . "ms\r\n" . static::$resCsTimestamp[strtolower($tmp[0])]['request_data'],
        );
    }

    private function microTimeStamp()
    {
        return (int)(microtime(true)*1000);
    }

    // 添加全链路日志.
    public function log($data)
    {
        if ($this->_on === self::OFF) {
            return;
        }

        $time = date('Y-m-d H:i:s');
        $this->_logFilePath = $this->getLogFilePath();
        $line = "OWL\001TRACE2\0010002\001{$this->_app}\001{$time}.000\001{$this->_ip}\001{$data}\004\n";
        $this->send($this->_logFilePath, $line);
    }
}
