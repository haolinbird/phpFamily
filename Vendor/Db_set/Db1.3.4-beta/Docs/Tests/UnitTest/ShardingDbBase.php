<?php
namespace UnitTest;

//////////////////////////////for test ///////////////////////////////////////////////////
/**
 * Class PromoCode
 *
 * @author Haojie Huang<haojieh@jumei.com>
 */

/**
 * 优惠劵拆表类.
 */
class TrusteeshipRule extends \Db\ShardingRule
{

    public $last3;

    const CFG_PREFIX = 't_fen_';

    protected static $charMap = array(
        'A' => 0,
        'B' => 1,
        'C' => 2,
        'D' => 3,
        'E' => 4,
        'F' => 5,
        'G' => 6,
        'H' => 7,
        'J' => 8,
        'K' => 9,
        'L' => 0,
        'M' => 1,
        'N' => 2,
        'P' => 3,
        'Q' => 4,
        'R' => 5,
        'S' => 6,
        'T' => 7,
        'U' => 8,
        'V' => 9,
        'W' => 0,
        'X' => 1,
        'Y' => 2,
        'Z' => 3,
        'O' => 4,
        'I' => 5,
    );

    /**
     * 构造方法.
     *
     * @param integer $last3 PromoCard后三位.
     *
     * @throws \Exception invalid PromoCard/Uid.
     */
    public function __construct($last3)
    {
        if (ctype_alnum((string)$last3)) {
            $this->last3 = strtr(substr($last3, -3), static::$charMap);
        } else {
            throw new \Exception("invalid id/  text '$last3'");
        }
    }

    /**
     * 获取sharding后的表名或表的Index.
     *
     * @param string $table Sharding 前的表名.
     *
     * @return string.
     */
    public function getTableName($table = '')
    {
        if ($table) {
            return $table . '_' . ($this->last3 % 128);
        } else {
            return $this->last3 % 128;
        }
    }

    /**
     * 获取sharding后的数据库名.
     *
     * @param string $db 数据库前缀.
     *
     * @return string.
     */
    public function getDbName($db = '')
    {
        return $db . '_' . floor($this->last3 % 128 / 8);
    }

    /**
     * 获取sharding后的服务器配置项名/服务器名.
     *
     * @return string.
     */
    public function getCfgName()
    {
        // 表->数据库 8进制 数据库->服务器/实例 4进制
        return static::CFG_PREFIX . floor($this->last3 % 128 / 8 / 4);
    }

    /**
     * 轮询操作.
     *
     * @param mixed $func 匿名函数.
     *
     * @return type desc
     */
    public static function pollAll($func)
    {
        \Db\ShardingConnection::clearAsyncConnections();
        for ($i = 128; $i < 128 * 2; $i++ ) {
            $rule = new static($i);
            $func($rule);
        }
        return \Db\ShardingConnection::asyncFetchAll();
    }

    /**
     * 把同一个规则的数据分到同一个组里.
     *
     * @param array  $data 要分组的数据.
     * @param string $key  按key进行分组.
     *
     * @return array
     */
    public static function splitGroup(array $data, $key = '')
    {
        $groupData = array();
        if ($key) {
            foreach ($data as $cData) {
                $instance = new static($cData[$key]);
                $index = $instance->getTableName();
                $groupData[$index][] = $cData;
            }
        } else {
            foreach ($data as $cData) {
                $instance = new static($cData);
                $index = $instance->getTableName();
                $groupData[$index][] = $cData;
            }
        }
        return $groupData;
    }
    
    /**
     * 把同一个规则的数据分到同一个组里面, 用于事务.
     * 
     * @param array  $data 要分组的数据.
     * @param string $key  按key进行分组.
     *
     * @return array
     */
    public static function splitTransGroup(array $data, $key = '')
    {
        $groupData = array();
        if ($key) {
            foreach ($data as $cData) {
                $instance = new static($cData[$key]);
                $groupData[$instance->getTableName()][$instance->getTableName()][] = $cData;
            }
        } else {
            foreach ($data as $cData) {
                $instance = new static($cData);
                $groupData[$instance->getTableName()][$instance->getTableName()][] = $cData;
            }
        }
        return $groupData;
    }

}




/**
 * Base classes of models which base on database.
 *
 * @author Huang HaoJie <haojieh@jumei.com>
 */

////////////////////////////////////////////
/**
 * 关系数据库数据处理模型基类。
 */

/**
 * Base classes of models which base on database.
 */
class DbBase extends Base{
    /**
     * @var string 此Model所用数据库配置名称(参考Config\Db.php)。
     */
    const DB_NAME = null;

    /**
     * @var string 此Model 所关联的表。
     */
    const TABLE_NAME = null;

    /**
     * @var string 表主键。(暂不考虑联合主键)
     */
    const PRIMARY_KEY = null;

    /**
     * 表的字段定义。设置表的字段后可以通过魔术方法访问相关的字段值(需先通过fillFields方法设置字段的值)。
     * 目前只用到字段名称作为key, key对应的值暂时保留不用(等待以后功能的升级)。
     * 例如:
     * <pre>
     * array('id'=>null, 'name'=>null);
     * </pre>
     *
     * @var array
     */
    protected static $fields= array();

    protected $content = array();


    /**
     * magic __get method. You can access the field values, if filled,  directly.
     *
     * @param string $name
     * @return mixed
     * @throws \Model\Exception
     */
    public function __get($name)
    {
        switch ($name)
        {
            default:
                if(isset($this->content[$name]))
                {
                    return $this->content[$name];
                    continue;
                }
                throw new Exception('Try get undefined property "'.$name.'" of class '.get_called_class().'. Forgot to call fillFields ?');
                continue;
        }
    }

    /**
     * Fill the table fields with the values. The fields that absent in the $value keys will be filled with null, while the keys which not defined in the $fields property will be ignored.
     *
     * @param array $values  e.g. array('id'=>32, 'user_name' => 'chaos' )
     * @return \Model\DbBase  the instance of the calss.
     * @throws \Model\Exception
     */
    public function fillFields(array $values)
    {
        if (!property_exists($this, 'fields') || !is_array($this::$fields))
        {
            throw new Exception('You cannot call this method, $fields is not an array or is empty in class '.get_class($this).'!');
        }
        $this->content = array();
        foreach ($values as $k => $v)
        {
            if (!array_key_exists($k, $this::$fields) && !in_array($k, $this::$fields))
            {// 兼容0.1.0d的判断
                throw new Exception('Try to fill a field "'.$k.'" that is not defined in property "fields" of Model '.get_class($this).'. Is it a typo ?');
            }
            else
            {
                $this->content[$k] = $v;
            }
        }
        return $this;
    }

    /**
     * get a instance of DbConnection of the specified connection name.
     *
     * @param string $name database configuration name that defined in Config\Db
     * @param string $type 连接类型(read|write)。 默认: read.
     * @return \Db\Connection
     * @throws \Model\Exception
     */
    public function db($name=null, $type='read')
    {
        if(!$name)
        {
            $name = $this::DB_NAME;
        }
        if(!in_array($type, array('read', 'write')))
        {
            throw new Exception('Db type "'.$type.'" is not valid!');
        }
        return \Db\Connection::instance()->$type($name);
    }

    /**
     * Get a instance of DbShardingConnection of the specified connection name.
     *
     * @param string $rule Sharding Rule is an instance of \Db\ShardingRule.
     * @param string $type 连接类型(read|write)。 默认: read.
     * @return \Db\ShardingConnection
     * @throws \Model\Exception
     */
    public function dbSharding($rule = null, $type='read')
    {
        if(!in_array($type, array('read', 'write')))
        {
            throw new Exception('Db type "'.$type.'" is not valid!');
        }

        return \Db\ShardingConnection::instance()->$type($rule);
    }

     /* 将一条记录写入数据库。如果记录已经存在则执行update,否则为insert操作。
     *
     * @param array $fieldValues field=>content array.
     * @throws \Model\Exception
     * @return bool|int
     */
    public function save(array $fieldValues = array())
    {
        if(!empty($fieldValues))
        {
            $this->fillFields($fieldValues);
        }

        if(count($this->content) < 1)
        {
            throw new Exception('Empty fields to save !');
        }
        $db = $this->db(null, 'write');
        if(!isset($this->content[$this::PRIMARY_KEY]))
        {
            return $db->insert($this::TABLE_NAME, $this->content);
        }
        else
        {
            if($this->primaryKeyExists($this->content[$this::PRIMARY_KEY]))
            {
                return $this->db(null, 'write')->update($this::TABLE_NAME, $this->content, array($this::PRIMARY_KEY => $this->content[$this::PRIMARY_KEY]));
            }
            else
            {
                $re = $db->insert($this::TABLE_NAME, $this->content);
                return $re;
            }
        }
    }

    /**
     * 判断表中某条记录是否存在.
     *
     * @param array $cond
     * @return bool
     */
    public function exists(array $cond = array())
    {
        $re =  $this->db()->select('1')->from($this::TABLE_NAME)->where($cond)->queryScalar();
        return (bool) $re;
    }

    /**
     * 根据主键判断记录是否存在。
     *
     * @param $primaryKey
     * @return bool
     */
    public function primaryKeyExists($primaryKey)
    {
        return $this->exists(array($this::PRIMARY_KEY=>$primaryKey));
    }
}

//==================================

/**
 * Base classes of models which base on database.
 */
class ShardingDbBase extends DbBase
{

    public static $db;

    /**
     * Get a instance of DbShardingConnection of the specified connecton name.
     *
     * @param string $rule Sharding Rule is an instance of \Core\Lib\OrderShardingRule.
     *
     * @return \Core\Lib\DbShardingConnection
     */
    public function getDbSharding($rule = null)
    {
        if ( ! static::$db instanceof \Db\ShardingConnection) {
            static::$db = \Db\ShardingConnection::instance();
        }
        if ($rule != null) {
            static::$db->setRule($rule);
        }

        return static::$db;
    }

}

/**
 * Base of all models.
 *
 * @author Su Chao<chaos@jumei.com>
 */


/**
 * Abstract model,included commond methods for data access and manipulations for derived classes.
 * @uses Db\Connection
 */
abstract class Base{
    /**
     *
     * Instances of the derived classes.
     * @var array
     */
    protected static $instances = array();

    /**
     * Get instance of the derived class.
     *
     * @param bool $noSingleton 是否获取单件。默认 true.
     *
     * @return static
     */
    public static function instance($singleton=true)
    {
        $className = get_called_class();
        if(!$singleton)
        {
            return new $className;
        }
        if (!isset(self::$instances[$className]))
        {
            self::$instances[$className] = new $className;
        }
        return self::$instances[$className];
    }


    /**
     * Get a redis instance.
     *
     * @param string $endpoint connection configruation name.
     * @param string $as use redis as "cache" or storage. default: storage
     * @return \Redis\RedisCache
     * @throws \Model\Exception
     */
    public function redis($endpoint = 'default', $as='storage')
    {
        if($as === 'storage')
        {
            $className = '\Redis\RedisMultiStorage';
        }
        else if($as === 'cache')
        {
            $className = '\Redis\RedisMultiCache';
        }
        else
        {
            throw new Exception('Redis instance can only be "as" "cache" or "storage".');
        }
        return $className::getInstance($endpoint);
    }
}
