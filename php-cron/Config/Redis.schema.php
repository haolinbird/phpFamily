<?php

/**
 * Redis配置文件
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-06-16 10:28:30
 */

namespace Config;

class Redis
{
    /**
     * Configs of Redis.
     *
     * @var array
     */
    public $default = array(
        'db' => 0,
        'nodes' =>  "#{Res.Redis.Xiangzhe.Cache.Nodes}",
        'password' => "#{Res.Redis.Xiangzhe.Cache.Auth}"
    );

    /**
     * Configs of Redis.
     *
     * @var array
     */
    public $recommend = array(
        'db'       => "#{Res.Redis.RecommendService.db}",
        'nodes'    => "#{Res.Redis.Xiangzhe.Cache.Nodes}",
        'password' => "#{Res.Redis.Xiangzhe.Cache.Auth}"
    );
}
