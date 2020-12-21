<?php

namespace Config;


class DBMan extends DataCenterRule
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
            "user" => array("zw","mjq"),
            "dbtest" => array("zw", "mjq"),
        ),
        "default_read" => "zw",
    );

}

class DataCenterRule
{
    /**
     * config in dove
     *   array(
     *       'dc1' => array('read' => 'host1:port:weight,host2:port:weight', 'write' => 'host1:port:weight,host2:port:weight'),
     *       'dc2' => array('read' => 'host3:port:weight,host4:port:weight', 'write' => 'host3:port:weight,host4:port:weight'),
     *   )
     *
     */
    protected $dataCenter = array();

    /**
     * db.table map to datacenter
     *   array(
     *        "map"   => array(
     *           "user"      => array("zw","mjq"),
     *           "order"    => array("zw", "mjq"),
     *        ),
     *        "defalut_read" => "zw",
     *    );
     *
     *
     */
    protected $db2dc = array();

    /**
     * @param $db
     * @param $table
     */
    public function getDataCenter($db)
    {
        if (!$db) {
            return '';
        }
        list($_db, $idx) = $this->split($db);
        $cfg = $this->getDataCenterCfg($_db, $idx, 'write');
        if (is_array($cfg) && !empty($cfg)) {
            return $cfg[0]['dc'];
        }
        return '';
    }

    /**
     * @param string $db db name, before sharding
     * @param integer $idx  table sharding id
     * @param string $type
     * @return array
     * @throws \Exception
     */
    public function getDataCenterCfg($db, $idx, $type = 'read')
    {
        //check the dataCenter configs.
        if (!isset($this->dataCenter) || !isset($this->db2dc)){
            return array();
        }
        if (!isset($this->db2dc['map'][$db])) {
            return array();
        }
        //根据库名计算库在哪些数据中心
        $dcArr = $this->db2dc['map'][$db];

        //如果是配置了读本地,则只选择本地的库
        if ( 'read' === $type && !empty($this->db2dc['default_read']) ){
            $dc = $this->db2dc['default_read'];
        } else {
            $dcId = $idx % count($dcArr);
            $dc = $dcArr[$dcId];
        }
        $dsn = $this->dataCenter[$dc][$type];

        $tmp_list = explode(',', $dsn);
        $arr_list = array();
        foreach ($tmp_list as $k => $add) {
            $arr = explode(':', $add);
            if (count($arr) < 2 || count($arr) > 3)
                throw new \Exception('DataCenter Config Error, ' . $dsn, 42001);

            $arr_list[] = array(
                'host' => $arr[0],
                'port' => $arr[1],
                'weight' => isset($arr[2]) ? $arr[2] : 1,
                'dc' => $dc,
            );
        }

        return $arr_list;
    }



    /**
     *
     * @param string $str Sharding后的表名,库名
     * @return string[] 原名,Sharding下标
     */
    public function split( $str = "")
    {
        $idx = strrpos($str,'_');
        $split = array(
            substr($str, 0, $idx),
            substr($str, $idx+1),
        );
        return $split;
    }
}

//run the script as command line .
$args = getopt('d:');
if (!$args['d']){
    exit("args error". print_r($args, true));
}
$db = $args['d'];

$dbMan = new DBMan();
$dc = $dbMan->getDataCenter($db);
printf('%s',$dc);
?>