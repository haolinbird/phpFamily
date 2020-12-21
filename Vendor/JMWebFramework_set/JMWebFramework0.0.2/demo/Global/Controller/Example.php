<?php
/**
 * 开发演示.
 *
 */

/**
 * Class Example
 */
class Controller_Example extends Controller_Base
{
    /**
     * Example 默认action
     *
     * @return mixed
     */
    public function action_Example()
    {
        // 初始化模板输出值
        $tplVars = array();
        $this->getTemplateEngine()->assign($tplVars);
        ob_start();
        $this->getTemplateEngine()->fetch("Example/Example");
        $content = ob_get_clean();

        Module_Example::Instance()->testRedisSet();
        // 返回给模板引擎
        return $tplVars;
    }

    public function action_Say()
    {
        var_dump(Module_Example::Instance()->testRedisGet());
        var_dump(Module_Example::Instance()->memCache('abb'));
        var_dump(\PHPClient\Text::inst('User')->setClass('User_User')->getUserByUid(1000));
    }

    public function  ajax_Hello()
    {
        return array('name'=>'D.K');
    }

    public function ajax_InvalidAjaxCommand()
    {
        header('content-type: application/javascript; charset=utf-8;');
        echo 'alert("JSONP非法调用！");window.location="'.$this->siteInfo['WebBaseURL'].'";';
    }
}
