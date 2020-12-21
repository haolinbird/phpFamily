<?php
/**
 * 关系数据库数据处理模型基类。
 */
namespace Model;

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

    /**
     * 将一条记录写入数据库。如果记录已经存在则执行update,否则为insert操作。
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
