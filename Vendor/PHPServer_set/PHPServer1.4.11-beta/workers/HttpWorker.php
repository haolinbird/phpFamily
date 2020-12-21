<?php
/**
 * Support Http Request.
 *
 * @author xianwangs <xianwangs@jumei.com> 
 */

class HttpWorker extends PHPServerWorker
{
    public function onServe()
    {
        $bootstrap = PHPServerConfig::get('workers.'.$this->serviceName.'.bootstrap');
        require_once $bootstrap;
    }

    public function dealInput($recv_str)
    {
        return HTTP::input($recv_str);
    }

    public function dealProcess($recv_str)
    {
        try {
            HTTP::decode($recv_str);
            $this->process();
        } catch (Exception $ex) {
            $this->send(HTTP::encode(json_encode(array(
                'exception' => array(
                    'class' => get_class($ex),
                    'message' => $ex->getMessage(),
                    'code' => $ex->getCode(),
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'traceAsString' => $ex->getTraceAsString(),
                )
            ))));
        }
    }

    protected function process()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (substr($uri, strlen($uri) - 1) == '/') {
            $uri .= 'index.php';
        }

        $uri = trim(preg_replace('!\.php$!i', '', $uri), '/');
        $uris = explode('/', $uri);
        $uris[count($uris) - 1] = ucfirst($uris[count($uris) - 1]);
        $uri = implode('/', $uris);
        $class_name = '\\Handler\\' . str_replace('/', '\\', $uri);

        if (! class_exists($class_name)) {
            throw new Exception("File " . $_SERVER['REQUEST_URI'] . " not exist");
        }

        if (! method_exists($class_name, 'execute')) {
            throw new Exception('The current request cannot be processed(' . $_SERVER['REQUEST_URI'] . ')');
        }

        $ctrl = new $class_name;

        if (method_exists($ctrl, 'getHeaders')) {
            $headers = $ctrl->getHeaders();
            foreach ($headers as $header) {
                HTTP::header($header);
            }
        }

        $this->send(HTTP::encode($ctrl->execute()));
    }

    protected function send($data)
    {
        $this->sendToClient($data);
    }
}
