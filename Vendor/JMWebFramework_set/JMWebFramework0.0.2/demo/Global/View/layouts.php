<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta property="qc:admins" content="56207406376255516375" />
<?php if(!empty($showIphoneAppNotice)): ?>
    <meta name="apple-itunes-app" content="app-id=518611020" />
<?php endif; ?>
<?php if (!empty($customSeoKeywords)): ?>
    <meta name="keywords" content="<?php echo Helper_Util::escape($customSeoKeywords); ?>" />
<?php endif; ?>
    <title><?php if(!empty($customPageTitle)){
        echo Helper_Util::escape($customPageTitle);
    } else {
        echo Helper_Util::escape(SITE_NAME);
    }
?></title>
<?php if (!empty($registeredCssModules)):?>
    <?php foreach ($registeredCssModules as $module) :?>
    <link rel="stylesheet" href="<?php echo Helper_Util::getCdnFilePath($module['src']); ?>" type="text/css" media="screen" charset="utf-8" />
    <?php endforeach; ?>
<?php endif; ?>
<?php if(!empty($canonicalLink)):?>
    <?php echo $canonicalLink;?>
<?php endif; ?>
<script type="text/javascript">
with(window) {
    <?php if(!empty($registeredJsGlobalVarsEncoded)) {
        foreach ($registeredJsGlobalVarsEncoded as $key => $value) {
            echo "$key=$value;\r\n";
        }
    }
    ?>
}
var screen_wide = document.documentElement.clientWidth > 1200 ? true : false ;
function getCookie(name)
{
    var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
    if(arr=document.cookie.match(reg))
        return (arr[2]);
    else
        return null;
}
<?php if (isset($isSemWinner) && !empty($isSemWinner)): ?>
var is_semwinner = true;
<?php else : ?>
var is_semwinner = false;
<?php endif; ?>

window._gaq = window._gaq || [];
_gaq.push(['_setAccount', GA_ACCOUNT]);
_gaq.push(['_addOrganic', 'baidu', 'wd']);
_gaq.push(['_addOrganic', 'image.baidu', 'word']);
_gaq.push(['_addOrganic', 'soso', 'w']);
_gaq.push(['_addOrganic', 'vnet', 'kw']);
_gaq.push(['_addOrganic', 'sogou', 'query']);
_gaq.push(['_addOrganic', 'youdao', 'q']);
_gaq.push(['_addIgnoredOrganic', 'jumei.com']);
_gaq.push(['_addIgnoredOrganic', 'www.jumei.com']);
_gaq.push(['_addIgnoredOrganic', 'jumei']);
_gaq.push(['_addIgnoredOrganic', '聚美']);
_gaq.push(['_addIgnoredOrganic', '聚美优品']);
_gaq.push(['_addIgnoredOrganic', 'tuanmei.net']);
_gaq.push(['_addIgnoredOrganic', 'www.tuanmei.net']);
_gaq.push(['_addIgnoredOrganic', 'tuanmei']);
_gaq.push(['_addIgnoredOrganic', '团美']);
_gaq.push(['_addIgnoredRef', 'jumei']);
_gaq.push(['_addIgnoredRef', 'tuanmei']);
_gaq.push(['_setDomainName', '.jumei.com']);
_gaq.push(['_setAllowHash', false]);
_gaq.push(['_trackPageview']);

<?php if (!empty($activatedAbtestCaseLabels)): ?>
    <?php foreach ($activatedAbtestCaseLabels as $item) :?>
        _gaq.push(['_setCustomVar', '<?php echo $item['SlotId'];?>', '<?php echo $item['CustomVar'];?>', '<?php echo $item['CustomVarValue'];?>', 2]);
    <?php endforeach; ?>
<?php endif; ?>
</script>
<script type="text/javascript" charset="utf-8" src="<?php echo Helper_Util::getCdnFilePath('/assets/js/jquery/jquery-1.4.2.min.js');?>"></script>
<script type="text/javascript" charset="utf-8" src="<?php echo Helper_Util::getCdnFilePath('/assets/js/jquery/jquery.all_plugins_v1.js');?>"></script>
</head>
<body>
<?php if (!empty($globalConfig['Debug']['BigMessage'])): ?>
    <div style="position: absolute; font-size: 108px; font-weight: bolder; z-index: 999999; color: red;"><?php echo $globalConfig['Debug']['BigMessage'];?></div>
<?php endif; ?>
<?php $this->includes('header'); ?>
<div id="container" style="width: auto;"><?php $this->block('content'); ?><?php $this->endblock();?></div>
<?php $this->includes('footer'); ?>
<?php if (!empty($registeredJavascriptModules)): ?>
    <?php foreach ($registeredJavascriptModules as $module): ?>
    <script type="text/javascript" charset="utf-8" src="<?php echo $module['src']?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<script type="text/javascript">
$(document).ready(function () {
    <?php
        if (!empty($registeredJavascriptModules)):
        foreach ($registeredJavascriptModules as $name => $val) :
    ?>
    <?php echo $name;?>.init();
    <?php
        endforeach;
        endif;
    ?>
    for(var i in Jumei.Core.afterInitFunctions)
        Jumei.Core.afterInitFunctions[i].call();
});

//ga
(function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = 'http://s0.jmstatic.com/templates/jumei/js/jquery/dc.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);

})();


//baidu tongji
(function() {
    var baidu_tongji = document.createElement('script'); baidu_tongji.type = 'text/javascript';
    baidu_tongji.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'hm.baidu.com/h.js?884477732c15fb2f2416fb892282394b';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(baidu_tongji, s);
})();
</script>
</body>
</html>