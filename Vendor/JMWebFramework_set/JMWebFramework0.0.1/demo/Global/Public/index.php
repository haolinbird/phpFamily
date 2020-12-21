<?php
/**
 * 海淘的路由文件.
 *
 * @author Chao Su <chaos@jumei.com>
 */

// 引入配置文件
require_once(__DIR__.'/../config.inc.php');

// 引入公用(跨项目)类库加载器.
require JM_VENDOR_DIR.'Bootstrap/Autoloader.php';
Bootstrap\Autoloader::instance()->init();

// 限流代码
require_once(__DIR__.'/../traffic/traffic.php');

// Web路由/控制器
require_once(JM_WEB_FRAMEWORK_ROOT . 'JMFrameworkWebManagement.php');

// Url >> controller 映射器
$routes = array(
    'global' => array(
         '@^reports.html$@'=> array('Example/Say',),//this is an example
    )
);
$subDomain = Utility_Util::getSubDomain();
JMRegistry::set('subDomain', $subDomain);
JMRegistry::set('SiteInfo', $siteConfig);
JMRegistry::set('cssConfig', $cssConfig);
JMRegistry::set('imgList', $imgList);
JMRegistry::set('jsList', $jsList);
JMRegistry::set('serverConfig', $serverConfig);
JMRegistry::set('CDNBaseURL', $CDNBaseURL);

// 初始化Service客户端配置
\PHPClient\JMTextRpcClient::config($serverConfig['RpcServer']);

// 初始化Memcache配置
\Memcache\Pool::config($serverConfig['MemcachedServers']);

// MNLogger客户端初始化
\MNLogger\TraceLogger::setUp(array('trace'=>$MNLogger['trace']));
\MNLogger\EXLogger::setUp(array('exception'=>$MNLogger['exception']));

\PHPClient\Text::config($PHPClient);

\Redis\RedisMultiCache::config($serverConfig['Redis']);
$siteEngine = new JMSiteEngine();
$siteEngine->setRoutePathMap($routes);
$siteEngine->setSiteName(SITE_NAME);
$siteEngine->ensureMainSiteAndLearnSubDomain();
$siteEngine->setDefaultRoutePathBaseName('Main');
$siteEngine->run();
