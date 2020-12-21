<?php
require_once __DIR__.DIRECTORY_SEPARATOR . 'JMTemplate/JMTemplate.class.php';

class JMAjaxJQuery
{
    protected $selector;
    protected $ajax;
    public function __construct($ajax, $selector)
    {
        $this->ajax = $ajax;
        $this->selector = $selector;
    }
    public function __call($name, $args)
    {
        $this->ajax->jQueryCall($this->selector, $name, $args);
        return $this;
    }
}

class JMAjax
{
    protected $batches = array();
    protected $currentBatchName = 0;
    protected $jQueryObj = null;
    protected $lastJQuerySelector = '';

    /**
     * @var JMViewController
     */
    protected $viewController;

    public function __construct($viewController)
    {
        $this->viewController = $viewController;
    }

    static public function Expr($s)
    {
        return array('_rm_expr_'=>$s);
    }

    public function evalScript($s)
    {
        $this->batches[$this->currentBatchName][] = array(
                'type'=>'eval',
                'code'=>$s,
        );
        return $this;
    }

    public function jQueryCall($selector, $s, $args)
    {
        if( ! empty($selector))
        {
            if($selector == $this->lastJQuerySelector)
                $selector = '';
            else
                $this->lastJQuerySelector = $selector;
        }
        $this->batches[$this->currentBatchName][] = array(
                'type'=>'jQuery',
                'selector'=>$selector,
                'func'=>$s,
                'args'=>$args,
        );
        return $this;
    }

    public function jQuery($s = null)
    {
        if ( ! empty($s) )
            $this->jQueryObj = new JMAjaxJQuery($this, $s);
        return $this->jQueryObj;
    }

    public function alert($s)
    {
        $this->batches[$this->currentBatchName][] = array(
                'type'=>'alert',
                'message'=>$s,
        );
        return $this;
    }

    public function call($f)
    {
        $args = func_get_args();
        array_shift($args);
        $this->batches[$this->currentBatchName][] = array(
                'type'=>'call',
                'func'=>$f,
                'args'=>$args,
        );
        return $this;
    }

    public function redirect($v)
    {
        $url = $this->viewController->buildUrl($v);
        return $this->evalScript('window.location.href="' . addslashes($url) . '"');
    }

    public function data($data, $name = null)
    {
        $this->batches[$this->currentBatchName][] = array(
                'type'=>'data',
                'name'=>$name,
                'data'=>$data,
        );
        return $this;
    }
    public function confirmLink($text, $okLink, $cancelLink)
    {
        $this->call('$.JM.confirmLink', $text, $okLink, $cancelLink);
        return $this;
    }

    public function getResponseData()
    {
        return $this->batches;
    }
}


class JMViewController {
    const SESSION_SAVED_MESSAGES = 'JMViewController_SessionSavedMessages';

    /** @var JMSiteEngine */
    protected $siteEngine;

    protected $actionUrlPrefix;
    protected $actionPath;
    protected $actionPathFields;

    /** @var JMTemplateEngine */
    private $templateEngine;

    /**
     * @var JMAjax
     */
    protected $ajax;

    /**
     * @var JMHtml
     */
    protected $html;

    public $ajaxCommand;

    /**
     *
     * @var JMTipMessageManager
     */
    protected $tipMessageManager = null;

    /**
     * @param $siteEngine JMSiteEngine
     */
    public function  __construct($siteEngine)
    {
        $this->siteEngine = $siteEngine;

        $this->actionUrlPrefix = $siteEngine->getWebRoot();
        $this->actionPathFields = $siteEngine->getRoutePathFields();
        $this->actionPath = $siteEngine->getRoutePath();
    }

    public function initialize()
    {
    }

    public function importSessionSavedMessage()
    {
        if(isset($_SESSION[self::SESSION_SAVED_MESSAGES]))
        {
            $this->tipMessageManager->import($_SESSION[self::SESSION_SAVED_MESSAGES]);
            unset($_SESSION[self::SESSION_SAVED_MESSAGES]);
        }
    }

    public function initJavascript()
    {
        $ajaxRequestVarName = JM_AJAX_REQUEST_VAR_NAME;
        return "<script type='text/javascript'>
        $.JM.actionUrl = '{$this->actionUrlPrefix}{$this->actionPath}';
        $.JM.ajaxRequestVarName = '$ajaxRequestVarName';
        $.JM.ajaxErrorMessage = 'Network error';
        </script>";
    }

    public function initAjax($ajaxCommand)
    {
        $this->ajax = new JMAjax($this);
        $this->ajaxCommand = $ajaxCommand;
    }

    public function isInAjax()
    {
        return ! empty($this->ajaxCommand);
    }

    /**
     * @static
     * @param $siteEngine JMSiteEngine
     * @return JMViewController
     */
    static public function CreateActionHandler($siteEngine)
    {
        $className = get_called_class();
        /** @var $controller JMViewController */
        $controller = new $className($siteEngine);
        $controller->initialize();
        foreach(static::$postInitializeHooks as $callback)
        {
            call_user_func_array($callback, array($controller));
        }
        return $controller;
    }

    public function __call($name, $arguments)
    {
        $s = join('/', $this->actionPathFields);
        throw new Exception("unknown call to JMViewController: $name ($s)");
    }

    /**
     * @return JMSiteEngine
     */
    public function getSiteEngine()
    {
        return $this->siteEngine;
    }

    public function displayJson($data, $callback=null)
    {
        if (defined('PHPUNIT_MODE') && PHPUNIT_MODE) {
            $GLOBALS['phpunitAjaxResult']= $data;
            return '';
        }
        if(!empty($callback))
        {
            $contentType = 'text/javascript';
        }
        else
        {
            $contentType = 'application/json';
        }
        JMSystem::SetHeaderContentTypeCharset($contentType, 'utf-8');
        if(!empty($callback))
        {
            echo $callback.'('.json_encode($data).')';
        }
        else
        {
            echo json_encode($data);
        }
    }

    public function displayJavaScript($s)
    {
        JMSystem::SetHeaderContentTypeCharset('application/javascript', 'utf-8');
        echo $s;
    }

    public function displayAjax()
    {
        $this->displayJson($this->ajax->getResponseData());
    }

    public function displayHtml($s)
    {
        echo $s;
    }
    /**
     * @throws InvalidArgumentException
     * @param $params Array array('base url', 'k1'=>'v1'), or just a base url string then pass $moreParams
     * @param $moreParams
     * @return string
     */
    public function getWebSiteUrl($params = array(), $moreParams = array())
    {
        //for nginx https:
        //  ssl on;
        //  fastcgi_param HTTPS on;

        $protocol = "http";
        if( (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") || $_SERVER['SERVER_PORT'] == 443 )
            $protocol = "https";

        $url = "$protocol://" . $_SERVER["HTTP_HOST"];
        if( ($protocol == 'http' && $_SERVER['SERVER_PORT'] != 80)
                || ($protocol == 'https' && $_SERVER['SERVER_PORT'] != 443))
        {
            $url .= ':' . $_SERVER['SERVER_PORT'];
        }
        $url .= $this->buildUrl($params, $moreParams);
        return $url;
    }

    //dummy for JMSiteEngine
    public function beforeControllerMethod()
    {

    }

    public function redirectExit($v = array(), $code = 302)
    {
        $url = $this->buildUrl($v);

        if($this->tipMessageManager->hasMessages())
            $_SESSION[self::SESSION_SAVED_MESSAGES] = $this->tipMessageManager->export();
        JMSystem::RedirectExit($url, $code);
    }

    public function redirect($v = array(), $code = 302)
    {
        $url = $this->buildUrl($v);

        if($this->tipMessageManager->hasMessages())
            $_SESSION[self::SESSION_SAVED_MESSAGES] = $this->tipMessageManager->export();
        JMSystem::Redirect($url, $code);
    }

    /**
     * @return JMTipMessageManager
     */
    public function getTipMessageManager()
    {
        return $this->tipMessageManager;
    }

    /**
     * @return JMTemplateEngine
     */
    public function getTemplateEngine()
    {
        if(!$this->templateEngine){
            $this->tipMessageManager = JMTipMessageManager::GetDefault();

            $this->templateEngine = new JMTemplateEngine();
            $this->html = new JMHtml($this);

            $this->templateEngine->assign(array('controller'=>$this,
                'html'=>$this->html,
            ));

            $siteInfo = JMRegistry::get('SiteInfo');
            $siteInfo = $siteInfo['Site'][$this->siteEngine->getSiteName()];
            $this->templateEngine->template->setConfig('template_path', $siteInfo['TemplateRootDirPath']);

            $viewPluginPath = $siteInfo['TemplateRootDirPath'].'Plugins/';
            if(!file_exists($viewPluginPath))
            {
                $viewPluginPath = JM_APP_ROOT.'Site/Plugins/';
            }

            $this->templateEngine->template->setConfig('plugins_path', $viewPluginPath);
            $this->templateEngine->template->setConfig('plugins_template_path', $siteInfo['TemplateRootDirPath'].'Plugin/');

            $this->templateEngine->assign(array(
                'ResWebPath' =>  $siteInfo['rewWebPath'],
                'ConfigDebug' => isset($siteInfo['Debug']) ? $siteInfo['Debug'] : array(),
            ));
        }
        return $this->templateEngine;
    }

    public function renderTemplate($name)
    {
        return $this->getTemplateEngine()->fetch($this->getTemplateEngine()->template($name));
    }

    /**
     * @param array|string $params array('base url', 'k1'=>'v1'), or just a base url string then pass $moreParams
     * @param array $moreParams
     * @return string
     */
    public function buildUrl($params = array(), $moreParams = array())
    {
        if( is_string($params))
            $params = array($params) + $moreParams;

        if( ! isset($params[0]))
        {
            // [ -1 => 'A' ]
            if(isset($params[-1]))
            {
                $a = $this->actionPathFields;
                array_pop($a);
                $url = $this->actionUrlPrefix . join('/', $a) . '/' . $params[-1];
                unset($params[-1]);
            }
            // [ 1 => 'A' ]
            else if(isset($params[1]))
            {
                $url = $this->actionUrlPrefix . $this->actionPathFields[0] . '/' . $params[1];
                unset($params[1]);
            }
            // [ k1 => 'v1' ]
            else
            {
                $url = $this->actionUrlPrefix . $this->actionPath;
            }
            $urlQuery = http_build_query($params, '', '&');
            if($urlQuery)
                return $url . "?" . $urlQuery;
            return $url;
        }
        else
        {
            // ['url', k1 => 'v1' ]
            if(is_string($params[0]))
            {
                $url = $params[0];
            }
            /*
             // for other syntax ? not supported yet
            else
            {
            $pathFields = $this->actionPathFields;
            $a = $params[0];
            reset($a);
            list($offset, $actionField) = each($a);
            if($offset < 0)
                $offset = count($pathFields) - $offset;
            do
            {
            $pathFields[$offset ++] = $actionField;
            }while( ($actionField = next($a)) );

            $url = '/' . join('/', $pathFields);
            }
            */
            unset($params[0]);
            if( ! empty($params))
            {
                $url .= strpos($url, '?') === false ? '?' : '&';
                $url .= http_build_query($params, '', '&');
            }
            return $url;
        }
    }

    public function getCurrentUri($params = array())
    {
        $uri = explode('?', $_SERVER['REQUEST_URI'], 2);
        if(!isset($uri[1])) $uri[1] = '';
        $uri[1] = $uri[1] ? ($uri[1] . "&" . http_build_query($params)) : http_build_query($params);
        if($uri[1])
            return $uri[0] . '?' . $uri[1];
        else
            return $uri[0];
    }
}



abstract class JMAuthInterface
{
    /**
     * @var JMSiteEngine
     */
    protected $siteEngine;
    public function __construct($siteEngine)
    {
        $this->siteEngine = $siteEngine;
    }

    abstract public function ensureLogin();
    abstract public function getCurrentUsername();
    public function logout()
    {
        throw new Exception('not implemented');
    }
}
