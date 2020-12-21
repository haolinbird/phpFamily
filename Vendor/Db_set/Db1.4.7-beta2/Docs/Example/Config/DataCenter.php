<?php

namespace Config;


class DataCenter extends \Db\DataCenterRule
{
    /**
    * 机房配置 机房名=> [读写DSN]
    * dove key #{Res.MultiDC.DataCenter}
    */
    protected $dataCenter = array(
        "dc_1" => array( "read"=>"192.168.20.73:6603:1",  "write"=>"192.168.20.73:6603"),
        "dc_2" => array( "read"=>"192.168.20.73:6613:1",  "write"=>"192.168.20.73:6613"),
    );

    /**
     *  数据库在哪些机房可读写,如果是多个机房,则会根据库分库下标对机房个数取模计算读写在哪一个机房；
     *  dove key #{Res.MultiDC.DB2DC}
     */
    protected $db2dc = array(
        "map"   => array(
            "user" => array("dc_1","dc_2"),
            "dbtest" => array("dc_1","dc_2"), //双活单中心应该写哪个机房
            "user_address" => array("dc_1","dc_2"), //双活单中心应该写哪个机房
        ),
        "default_read" => "dc_1", //如果是读,则都读本地机房
    );
    

}
