<?php
/**
 * @author: dengjing<jingd3@jumei.com>.
 *
 */
namespace Utils\Log;

/**
 * unified log handlers
 */
class Logger {

    protected static $instances;

    /**
     * Get instance of the derived class.
     *
     * @return \Utils\Log\Logger
     */
    public static function instance()
    {
        $className = get_called_class();
        if (!isset(self::$instances[$className])) {
            self::$instances[$className] = new $className;
        }
        return self::$instances[$className];
    }

    /**
     * 记录业务日志.
     *
     * @param mixed   $content      日志内容.
     * @param string  $logger       日志内容格式.
     * @param string  $rotateFormat 日志文件分片形式.
     *
     * @return void
     */
    public function log($content, $logger = 'jsonfile', $rotateFormat = 'Y-m-d')
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $trace = $traces[1];
        $namespace = explode('\\', $trace['class']);
        $suffix = end($namespace);
        $endpoint = $suffix . '_' . $trace['function'];
        $code = 0;
        $message = '';
        if ($content instanceof \Exception) {
            $return = null;
            $code = $content->getCode();
            $message = $content->getMessage();
        } else {
            $return = $content;
        }
        $config = \Log\Handler::config();
        if (empty($config)) {
            $config = (array) new \Config\Log;
        }
        if (!isset($config[$endpoint])) {
            $logDir = defined('JM_APP_NAME') ? JM_APP_NAME . DIRECTORY_SEPARATOR : '';
            $config[$endpoint] = array(
                'path' => rtrim($config['FILE_LOG_ROOT'], DIRECTORY_SEPARATOR) .  DIRECTORY_SEPARATOR . $logDir . $endpoint . DIRECTORY_SEPARATOR . $endpoint . '.log',
                'logger' => $logger,
                'rotateFormat' => $rotateFormat,
            );
            \Log\Handler::config($config);
        }
        return \Log\Handler::instance($endpoint)->log(
            array(
                'date_time' => date('Y-m-d H:i:s'),
                'class' => $trace['class'],
                'func' => $trace['function'],
                'args' => $trace['args'],
                'return' => $return,
                'code' => $code,
                'message' => $message,
            )
        );
    }

    /**
     * 可控记录业务日志.
     *
     * @param mixed  $content      日志内容.
     * @param string $logger       日志内容格式.
     * @param string $rotateFormat 日志文件分片形式.
     *
     * @return mixed
     */
    public function logNew($content, $logger = 'jsonfile', $rotateFormat = 'Y-m-d')
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $trace = $traces[1];
        $namespace = explode('\\', $trace['class']);
        $suffix = end($namespace);
        $endpoint = $suffix . '_' . $trace['function'];
        $closedLogConf = isset(\Config\Log::$closedLogConf) ? \Config\Log::$closedLogConf : array();
        if (!empty($closedLogConf['closedLogConfigNames']) && in_array($endpoint, $closedLogConf['closedLogConfigNames'])) {
            return true;
        }
        $code = 0;
        $message = '';
        if ($content instanceof \Exception) {
            $return = null;
            $code = $content->getCode();
            $message = $content->getMessage();
        } else {
            $return = $content;
        }
        $config = \Log\Handler::config();
        if (empty($config)) {
            $config = (array) new \Config\Log;
        }
        if (!isset($config[$endpoint])) {
            $logDir = defined('JM_APP_NAME') ? JM_APP_NAME . DIRECTORY_SEPARATOR : '';
            $config[$endpoint] = array(
                'path' => rtrim($config['FILE_LOG_ROOT'], DIRECTORY_SEPARATOR) .  DIRECTORY_SEPARATOR . $logDir . $endpoint . DIRECTORY_SEPARATOR . $endpoint . '.log',
                'logger' => $logger,
                'rotateFormat' => $rotateFormat,
            );
            \Log\Handler::config($config);
        }
        return \Log\Handler::instance($endpoint)->log(
            array(
                'date_time' => date('Y-m-d H:i:s'),
                'class' => $trace['class'],
                'func' => $trace['function'],
                'args' => $trace['args'],
                'return' => $return,
                'code' => $code,
                'message' => $message,
            )
        );
    }

}
