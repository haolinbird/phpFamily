#JMHttpClient组建使用说明
>该组建基于Curl实现, 用于http/https协议场景下与服务器的交互.

###版本介绍
####1.0.12
1. 支持http(s)长链接
2. 支持多个host
3. 支持为host配置使用权重
4. 支持故障服务器剔除
5. 在php-server/cli模式下支持故障服务器的检测与恢复
####1.0.12.1beta
1. 支持屏蔽故障服务器恢复（建议使用短链接）, check_falut_host为false不剔除故障服务器
####1.0.13
1. 升级故障服务器检查机制，不在fork子进程检查/恢复故障服务器，直接通过本进程处理
2. 不再支持请求失败重试(不再支持repeat_num/repeat_interval) 以及之前版本提供的check_falut_host控制是否检查故障服务器开关
####1.1.0-beta
1. 支持context/owl_context上下文传递
2. 支持mnlogger记录相关日志
####1.1.1-beta
1. Request增加两个接口sendGet/sendPost，这两个接口不依赖Config/JMHttpClient,减轻用户接入成本; 用法见 Example 2.
####配置说明
```
<?php
namespace Config;
// 1.1.0-beta 之前的版本使用 class HttpClient
// 1.1.0-beta 版本使用 class JMHttpClient(同时兼容class HttpClient)
class JMHttpClient
{
    public $default = array( // 默认主机分组
        // schema仅支持http(s)
        // host可以是ip或域名
        // 端口如不明确, 默认使用80端口
        'hosts' => array(
            'http://127.0.0.1' => 4,
            'https://192.168.0.1:8000' => 2
        ),
        // 标准的curl options配置(参考php curl手册)
        // 在不明确指定的情况下, 下列三个值是默认的配置
        'curlOptions' => array( // 默认配置；可以使用标准的curl options
            'CURLOPT_FORBID_REUSE' => false, // 默认使用长链接, 为true则使用短连接
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HEADER' => false, 
        ),
        // 已废弃; 用户自行设定的配置
        'opts' => array(
            'repeat_num' => 0, // 已废弃; 请求失败时的重试次数
            'repeat_interval' => 1, // 已废弃; 每次重试的间隔
            'check_falut_host' => false, // 已废弃 控制是否检查故障服务器开关.
        ),
    );
    
    public $hostGroup1 = array( // 主机分组
    );
}
```
####Example 1
```
// <1.1.1-beta 版本
try {
    $req = \JMHttpClient\Request::instance('default');
    $ret1 = $req->get('/product/1');

    // 第二个参数能够使用所有curl_setopt支持的配置
    $ret2 = $req->get('/product/2', array(
        'CURLOPT_HEADER' => true,
    ));

    $req1 =  \JMHttpClient\Request::instance('hostGroup1');
    // 第二个参数是post的数据, 第三个参数是curl options
    $ret3 = $req1->post('/product', array(
        'product_name' => 'xxx',
    ));
} catch (\Exception $e) {
    // 注意, 一定要捕获异常!!!
    // 用户配置错误, 没有服务器可用等情况都会抛出异常
}
```
####Example 2
// >=1.1.1-beta 版本新增的接口.
try {
     // 第2个参数能够使用所有curl_setopt支持的配置
    $ret1 = \JMHttpClient\Request::sendGet($url, $curlOpts);

    // 第3个参数能够使用所有curl_setopt支持的配置
    $ret2 = \JMHttpClient\Request::sendPost($url, $data, $curlOpts);
} catch (\JMHttpClient\Exception\RequestException $e) {
    // 注意, 一定要捕获异常!!!
    // url请求异常处理.
}