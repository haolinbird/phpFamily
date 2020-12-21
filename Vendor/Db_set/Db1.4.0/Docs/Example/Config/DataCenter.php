<?php

namespace Config;


class DataCenter extends \Db\DataCenterRule
{
    /**
    * 机房配置 机房名=> [读写DSN]
    * dove key #{Res.MultiDC.DataCenter}
    */
    protected $dataCenter = array(
        "zw" => array(
            "read"=>"192.168.20.71:9001:1,192.168.20.71:9001:2",
            "write"=>"192.168.20.71:9001:3,192.168.20.71:9001:3",
        ),
        "mjq" => array(
            "read"=>"mjq:3306:1,mjq:3306:2",
            "write"=>"mjq:3306:3,mjq:3306:4",
        ),
    );

    /**
     *  数据库在哪些机房可读写,如果是多个机房,则会根据库分库下标对机房个数取模计算读写在哪一个机房；
     *  dove key #{Res.MultiDC.DB2DC}
     */
    protected $db2dc = array(
        "map"   => array(
            "user" => array("zw"), //双活单中心应该写哪个机房
            "dbtest" => array("zw", "mjq"),
        ),
        "default_read" => "zw", //如果是读,则都读本地机房
    );
    

}