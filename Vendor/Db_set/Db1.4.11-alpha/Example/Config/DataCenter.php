<?php
/**
 * This file is generated automatically by ConfigurationSystem.
 * Do not change it manually in production, unless you know what you're doing and can take responsibilities for the consequences of changes you make.
 */


namespace Config;


class DataCenter extends \Db\DataCenterRule
{
    /**
    * 机房配置 机房名=> [读写DSN]
    * dove key #{Res.MultiDC.DataCenter}
    */
    protected $dataCenter = array (
  'zw' => 
  array (
    'read' => '172.20.4.48:16603:100,172.20.4.48:116603:100',
    'write' => '172.20.4.48:26603:100,172.20.4.48:226603:100',
  ),
  'yz' => 
  array (
    'read' => '172.20.4.48:36603:100,172.20.4.48:336603:100',
    'write' => '172.20.4.48:46603:100,172.20.4.48:446603:100',
  ),
);
    /*
    protected $overrideDb2dc = array(
        'read_db2dc' => array(
            'key_db' => 'yz',
        ),
    );
     */

    /**
     *  数据库在哪些机房可读写,如果是多个机房,则会根据库分库下标对机房个数取模计算读写在哪一个机房；
     *  dove key #{Res.MultiDC.DB2DC}
     */
    protected $db2dc = array (
  'default_read' => 'yz',
  'map' =>
  array (
      /*
  'key_db' => array(
      0 => 'zw',
      1 => 'yz',
  ),
      */
    'user' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'order' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'activities' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_encrypt' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_log' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_orders' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_pop_orders' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_product_sharding' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_promocards' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_shippings' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'message_box' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'payment_platform' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'payments' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'user_address' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'user_giftcard' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'user_point' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),
    'jumei_orders_activated' =>
    array (
      0 => 'zw',
      1 => 'yz',
    ),

  ),
);
    

}
