<?php

namespace Config;


class DataCenter extends \Db\DataCenterRule
{
    /**
    * 机房配置 机房名=> [读写DSN]
    * dove key #{Res.MultiDC.DataCenter}
    */
    protected $dataCenter = "#{Res.MultiDC.DataCenter}";

    /**
     *  数据库在哪些机房可读写,如果是多个机房,则会根据库分库下标对机房个数取模计算读写在哪一个机房；
     *  dove key #{Res.MultiDC.DB2DC}
     */
    protected $db2dc = "#{Res.MultiDC.DB2DC}";
    

}