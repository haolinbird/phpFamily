<?php

/**
 * Elasticsearch 配置文件
 *
 * @author Hao Lin <lin.hao@xiaonianyu.com>
 * @date 2021-06-24 21:04:11
 */

namespace Config;

class Elasticsearch
{
    // Elasticsearch 服务器地址
    public static $hosts = "#{Res.Elasticsearch.Search.Hosts}";

    // 索引分片个数
    public static $shardsNumber = "#{search-service.elasticsearch.shards_number}";

    // 索引副本个数
    public static $replicasNumber = "#{search-service.elasticsearch.replicas_number}";
}
