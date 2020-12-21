<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */

/**
 * Redis 配置
 */
namespace Config;

#是否使用连接池(如果为false，则直连，否则可能使用新版或者旧版连接池)
if(!defined('JM_PHP_CONN_POOL_ON'))define('JM_PHP_CONN_POOL_ON', true);

#是否使用旧版连接池
if(!defined('JM_PHP_CONN_POOL_OLD_ON'))define('JM_PHP_CONN_POOL_OLD_ON', false);

class Redis{
    public $partitionConfig = array (
  'default_partition' => 0,
  'partition' => 
  array (
    0 => 
    array (
      'uid_range' => 
      array (
        0 => 
        array (
          0 => 0,
          1 => 200000000,
        ),
      ),
      'sbaolock' => 
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27001',
          'master-alia' => '192.168.49.44:27001',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27002',
          'master-alia' => '192.168.49.44:27002',
        ),
      ),
      'syceedata' =>
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27001',
          'master-alia' => '192.168.49.44:27001',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27002',
          'master-alia' => '192.168.49.44:27002',
        ),
      ),
      'sbaodata' => 
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27001',
          'master-alia' => '192.168.49.44:27001',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27002',
          'master-alia' => '192.168.49.44:27002',
        ),
      ),
      'sbaotoufang' => 
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27001',
          'master-alia' => '192.168.49.44:27001',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27002',
          'master-alia' => '192.168.49.44:27002',
        ),
      ),
      'sbaoworker' => 
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27001',
          'master-alia' => '192.168.49.44:27001',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27002',
          'master-alia' => '192.168.49.44:27002',
        ),
      ),
    ),
    1 => 
    array (
      'uid_range' => 
      array (
        0 => 
        array (
          0 => 200000005,
          1 => 400000000,
        ),
      ),
      'sbaolock' => 
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27003',
          'master-alia' => '192.168.49.44:27003',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27004',
          'master-alia' => '192.168.49.44:27004',
        ),
      ),
      'syceedata' =>
      array (
        0 =>
        array (
          'master' => '192.168.49.44:27003',
          'master-alia' => '192.168.49.44:27003',
        ),
        1 =>
        array (
          'master' => '192.168.49.44:27004',
          'master-alia' => '192.168.49.44:27004',
        ),
      ),
      'sbaodata' => 
      array (
        0 =>
        array (
          'master' => '192.168.49.44:27003',
          'master-alia' => '192.168.49.44:27003',
        ),
        1 =>
        array (
          'master' => '192.168.49.44:27004',
          'master-alia' => '192.168.49.44:27004',
        ),
      ),
      'sbaotoufang' => 
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27003',
          'master-alia' => '192.168.49.44:27003',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27004',
          'master-alia' => '192.168.49.44:27004',
        ),
      ),
      'sbaoworker' => 
      array (
        0 => 
        array (
          'master' => '192.168.49.44:27003',
          'master-alia' => '192.168.49.44:27003',
        ),
        1 => 
        array (
          'master' => '192.168.49.44:27004',
          'master-alia' => '192.168.49.44:27004',
        ),
      ),
    ),
  ),
);
    /**
     * Configs of Redis.
     * @var array
     */
    public $default = array('nodes' => array(
         array('master' => "127.0.0.1:6379", 'slave' => "127.0.0.1:6379", 'master-alia'=>'127.0.0.1:6379'),
       //  array('master' => "127.0.0.1:6380"),

    ),
        // redis集群名称. 在分区配置里会用到，用来查找对应的集群节点.
      'cluster_name' => 'syceedata',
        'db' => 0,
        'password' =>'password123456'
    );

    public $fav = array('nodes' => array(
        array('master' => '192.168.17.16:6379'),
    ),
        'cluster_name' => 'orderSystem',
        'db' => 10,
        'password' =>'password123456',
        'timeout' => 3
    );

    public $favParition = array('nodes' => array(
        array('master' => '192.168.17.16:6379'),
    ),
        'cluster_name' => 'sbaolock',
        'db' => 10,
        'password' =>'password123456',
        'timeout' => 3
    );

    public $testAuth = array('nodes' => array(
        array('master' => "127.0.0.1:6379", 'slave' => "127.0.0.1:6379"),
    ),
        'cluster_name' => 'productSystem',
        'db' => 2,
        'password' =>'123456'
    );
}
