Web开发框架及项目搭建简单说明
=============================
Web项目基础框架。

1. 集成JMWebFramework框架
    在项目的composer.json中添加依赖。(可参考demo)
    <pre>
        "require": {
        "JMWebFramework": ">=0.0.1",
        }
    </pre>
1. #### Web 搭建(Example)
    __Nginx Example__   

        server {
                listen 80; 
                server_name global.jumeicd.com;
                root /home/chaos/Projects/JMWebFramework/demon/Global/Public/;
                access_log /var/log/nginx/test.access.log;
                error_log  /var/log/nginx/test.error.log;
               location / { 
                    index index.html index.php;
                    try_files $uri /index.php?_rp_=$uri&$query_string;
                }
               location ~ \.php$ {
                    fastcgi_pass   127.0.0.1:9000;
                    fastcgi_index  index.php;
                    include        fastcgi_params;
                    fastcgi_param  SCRIPT_FILENAME    $document_root$fastcgi_script_name;
                }   
    }

1. #### demo 目录结构说明参考
___Global___    
项目名，即项目代码目录。 项目相关的代码都放在此目录下(注意后边的Commons目录，也包含项目数据操作部分的代码)。
此目录下一般存放了项目的config文件。   
___ Global\Public___    
项目web根目录，只存放直接用于web访问的文件，如：静态文件，项目入口文件index.php等。   
___Global\Controller___     
控制器存放目录，控制器中的action负责对应页面的逻辑处理。     
___Global\View___   
页面模板存放目录。
___Global\Module___    
比较基本的业务数据处理模块。
___Global\Transaction___
包含几个基本业务的调用组合。  
___Global\Helper___    
公用辅助类库存放，作用和Utility有点类似。    
___Config___
(此项目暂时不使用此规范，依然使用config.inc.php) 项目的Config存放目录, 也是Vendor的配置存放的默认目录。 
1. #### Vendor
    参考: http://xwiki.int.jumei.com/bin/view/php/类库开发与集成规范

1. #### .hgignore    
添加 Vendor 、conconfig.inc.php等不允许提交到代码库的文件。
1. #### Ajax    
    Get里指定action名字： \_ajax\_=SayHello   
    例：  
    ``http://global.jumeicd.com/Example/?_ajax_=Hello``

1. #### 404  
    action_PageNotFound

1. #### Template vars

    return array('name'=>'J.D', 'age'=>231)
    
1. #### View模板  
    PHP原始语法支持。 变量的使用参考 __Template Vars__。   
1. #### FE框架整合相关说明    
控制器基类``JMViewController_WebManagementBase``提供了``registerPostInitializeHook``方法，用于controller初始化工作（模板引擎初始化、site全局变量）后的回调。             
回调时传入的参数为当前controller的实例，如下例：    
在``JMSiteEngine``运行前执行如下代码：

            //　修改view的根目录。
            JMViewController_WebManagementBase::registerPostInitializeHook(function($c){
                // 修改模板存放的目录，controller中调用模板引擎渲染模板时将从JM_VIEW_BUILD置顶的目录获取模板。
                $c->templateEngine->template->setConfig('template_path', JM_VIEW_BUILD);
            });
                
    
