<?php
// 将开发环境，与业务相关的配置写到这里

// About cartShow config.
$siteConfig['Site']['Main']['TopLevelDomainName'] = 'jumei.com';
$siteConfig['Site']['Main']['WebDomainName'] = 'www.jumei.com';
$siteConfig['Site']['Main']['WebBaseURL'] = 'http://www.jumei.com/';

$siteConfig['Site']['Mall']['WebDomainName'] = 'mall.jumei.com';
$siteConfig['Site']['Mall']['ShowInTopNav'] = true;
$siteConfig['Site']['Mall']['WebBaseURL'] = 'http://mall.jumei.com/';

$siteConfig['Site']['Global']['WebDomainName'] = 'www.jumeiglobalrd.com';
$siteConfig['Site']['Global']['WebBaseURL'] = 'http://www.jumeiglobalrd.com/';


$siteConfig['Site']['Cart']['WebDomainName'] = 'cart.jumei.com';
$siteConfig['Site']['Cart']['WebBaseURL'] = 'http://cart.jumei.com';

// 当前站点域名.
$siteConfig['Site']['Current'] = $siteConfig['Site']['Global'];

$siteConfig['IsInDevelopment'] = true;


// Redis 连接配置
$serverConfig['Redis']['default'] = array('nodes' => array(
        array('master' => "127.0.0.1:6379"),
        ),
        'password' => null,
        'db' => 2
);

// Memcached连接配置
$serverConfig['MemcachedServers']['default'] = array(
    array(
        'host' => '192.168.25.9',
        'port' => 6660
    ),
    array(
        'host'=>'192.168.25.9',
        'port'=>6661,
        'type'=>'backup'
    )
);

$MNLogger['exception'] = array(
    'on' => true,
    'app' => 'KoubeiService',
    'logdir' => '/tmp/logs/monitor/'
);


$MNLogger['trace'] = array(
    'on' => true,
    'app' => 'KoubeiService',
    'logdir' => '/tmp/logs/monitor/'
);

// Webservice(PHPServer) 服务器端连接配置
$serverConfig['RpcServer'] = array(
    'rpc_secret_key' => '769af463a39f077a0340a189e9c1ec28',
    'trace_log_path' => '/tmp/logs/monitor/',
        'User' => array(
                'uri' => 'tcp://127.0.0.1:2201',
                'user' => 'Optool',
                'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
        ),
        'Item' => array(
                'uri' => 'tcp://127.0.0.1:2201',
                'user' => 'Optool',
                'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
        ),
        'Order' => array(
                'uri' => 'tcp://127.0.0.1:2201',
                'user' => 'Optool',
                'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
        ),
        'Dealman' => array(
                'uri' => 'tcp://127.0.0.1:2201',
                'user' => 'Optool',
                'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
        ),
        'Cart' => array(
                'uri' => 'tcp://127.0.0.1:2201',
                'user' => 'Optool',
                'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
        ),
        'Global' => array(
            'uri' => 'tcp://192.168.25.22:2201',
            'user' => 'Optool',
            'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
        ),

);

/**
 * CDN 相关配置
 */
$CDNBaseURL = array(
    'product'       => 'http://images2.jumei.com/dev_test/test_product/', // DO NOT forget the last slash
    'deal_product'  => 'http://images2.jumei.com/dev_test/test_deal_product/', // DO NOT forget the last slash
    'deal_content'  => 'http://images2.jumei.com/dev_test/test_deal_content/', // DO NOT forget the last slash
    'mobile'        => 'http://p0.jmstatic.com/dev_test/mobile/', // DO NOT forget the last slash
    'brand'         => 'http://images2.jumei.com/brand/logo/', // DO NOT forget the last slash
    'pop'           => 'http://images2.jumei.com/dev_test/pop_feature/', // DO NOT forget the last slash
);

$PHPClient = array(
    'rpc_secret_key' => '769af463a39f077a0340a189e9c1ec28',
    'monitor_log_dir' => '/tmp/monitor-log',
    'trace_log_path' => '/tmp/monitor-log',
    'recv_time_out' => 5,
    'User' => array(
        'uri' => 'tcp://192.168.20.95:2201',
        'user' => 'Optool',
        'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
        //'compressor' => 'GZ',
    )
);
