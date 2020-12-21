<?php
namespace Db;

/**
 * Class DataCenterRule
 * @package Db
 */
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
    *    );
    *
    *
    */
    protected $db2dc = array();

    /**
     * @param string $db db name, before sharding
     * @param integer $idx  table sharding id
     * @param string $type
     * @return array
     * @throws Exception
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
                throw new Exception('DataCenter Config Error, ' . $dsn, 42001);
            
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