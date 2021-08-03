<?php

/**
 * PHPClient组件配置文件
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-06-16 10:28:30
 */

namespace Config;

class PHPClient
{

    public $rpc_secret_key = "769af463a39f077a0340a189e9c1ec28";

    /**
     * 搜索 Rpc 服务配置.
     *
     * @var array
     */
    public $search = array(
        'uri'    => "#{search-service.rpc.host}",
        'user'   => 'xz-php-cron',
        'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
    );

    /**
     * 商品库 Rpc 服务配置.
     *
     * @var array
     */
    public $product = array(
        'uri'    => "#{xz-product-service.Rpc.Host}",
        'user'   => 'xz-php-cron',
        'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
    );
}
