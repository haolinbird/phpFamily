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
    protected $dataCenter = array(
            'dc1' => array('read' => '192.168.20.73:6603:100', 'write' => '192.168.20.73:6603:100'),
            'dc2' => array('read' => '192.168.20.73:6613:100', 'write' => '192.168.20.73:6613:100'),
       );

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
    protected $db2dc = array(
            "map"   => array(
               "dbtest"      => array("dc1","dc2"),
            ),
            "default_read" => "dc1",
        );


    /**
     * 优先使用的配置,如果在这里设置了对应的default_read或者db对应的idc rule，那么就不用db2dc里面的规则了（db2dc的未覆盖规则仍然会使用）.
     *
     * @param array()
     */
    protected $overrideDb2dc = array();

    /**
     * 需要将业务级别的规则与同一的规则做merge，优先以业务级别的规则为准.
     *
     * DataCenterRule constructor.
     */
    public function __construct()
    {
        if (is_array($this->overrideDb2dc) && !empty($this->overrideDb2dc['read_db2dc'])) {
            $this->db2dc['read_db2dc'] = $this->overrideDb2dc['read_db2dc'];
        }

        if (!empty($this->overrideDb2dc['map']) && is_array($this->overrideDb2dc['map'])) {
            foreach ($this->overrideDb2dc['map'] as $db => $rule) {
                $this->db2dc['map'][$db] = $rule;
            }
        }
    }

    /**
     * @param string $db db name, before sharding
     * @param integer $idx  db sharding id = id / 8
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function getDataCenterCfg($db = '', $idx = 0, $type = 'read')
    {
        //check the dataCenter configs.
        if (!isset($this->dataCenter) || !isset($this->db2dc)){
            return array();
        }

        do {
            // 尝试使用db级别的read配置
            if ('read' === $type && !empty($this->db2dc['read_db2dc'][$db])) {
                $dc = $this->db2dc['read_db2dc'][$db];
                break;
            }

            // 尝试使用全局的read配置( isset($this->db2dc['map'][$db])逻辑是为了保证与之前版本兼容)
            if ('read' === $type && isset($this->db2dc['map'][$db]) && !empty($this->db2dc['default_read'])) {
                $dc = $this->db2dc['default_read'];
                break;
            }

            // 写链接，使用db2dc规则，如果没配置，返回空
            if (!isset($this->db2dc['map'][$db])) {
                return array();
            }

            $idx = (int)str_replace('_','',$idx);
            //根据库名计算库在哪些数据中心
            $dcArr = $this->db2dc['map'][$db];
            $dcId = $idx % count($dcArr);
            $dc = $dcArr[$dcId];
        } while (0);

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
                'dsn' => "mysql:host=$arr[0];port=$arr[1];dbname=$db",
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
