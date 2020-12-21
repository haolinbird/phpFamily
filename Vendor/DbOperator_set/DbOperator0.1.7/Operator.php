<?php
/**
 * DbOperator.
 *
 * @author dengjing<jingd3@jumei.com>
 */

namespace DbOperator;

/**
 * DbOperator.
 */
class Operator
{
    protected $join = array();
    protected $leftJoin = array();
    protected $limit = array();
    protected $group = '';
    protected $having = '';
    protected $order = '';
    protected $master = false;
    protected $alias = '';
    protected $columns = '';
    protected $where = array();
    protected $queryType;
    protected $assocKey;
    protected $table;
    protected $joinColumns = array();
    /**
     * 数据库对象.
     *
     * @var \Db\Connection
     */
    protected $db;
    protected $shardingRule;
    protected $dbConfigName;

    /**
     * 分区标识.
     *
     * @var integer
     */
    protected $partition;

    /**
     * 获取当前实例.
     *
     * @return \DbOperator\Operator
     */
    public static function instance()
    {
        return new static;
    }

    /**
     * 设置分区(标识)参数，准备根据UID来获取不同分区的链接.
     *
     * @return \DbOperator\Operator
     */
    public function partition($partition)
    {
        $this->partition = $partition;
        return $this;
    }

    /**
     * 获取分区参数.
     *
     * @return string|null
     */
    public function getPartition()
    {
        return $this->partition;
    }

    /**
     * 联表操作.
     *
     * @param string $table     联表的名称.
     * @param string $alias     别名.
     * @param string $condition 联表条件.
     * @param string $columns   关联表字段裸名, 用逗号分隔, 字段之间不能有空格.
     *
     * @return \DbOperator\Operator
     */
    public function join($table, $alias, $condition, $columns = '')
    {
        $this->join[] = array($table, $alias, $condition);
        if ($columns) {
            if ($alias) {
                $c = array();
                foreach (explode(',', $columns) as $column) {
                    $c[] = $alias . '.' . trim($column);
                }
                $this->joinColumns[] = join(',', $c);
            } else {
                $this->joinColumns[] = $columns;
            }
        }
        return $this;
    }

    /**
     * 主表指定别名.
     *
     * @param string $alias 别名.
     *
     * @return \DbOperator\Operator
     */
    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    /**
     * 左联接查询.
     *
     * @param string $table     联表名称.
     * @param string $alias     别名.
     * @param string $condition 联表条件.
     * @param string $columns   关联表字段裸名, 用逗号分隔, 字段之间不能有空格.
     *
     * @return \DbOperator\Operator
     */
    public function leftJoin($table, $alias, $condition, $columns = '')
    {
        $this->leftJoin[] = array($table, $alias, $condition);
        if ($columns) {
            if ($alias) {
                $c = array();
                foreach (explode(',', $columns) as $column) {
                    $c[] = $alias . '.' . trim($column);
                }
                $this->joinColumns[] = join(',', $c);
            } else {
                $this->joinColumns[] = $columns;
            }
        }
        return $this;
    }

    /**
     * 指定查询主库.
     *
     * @param boolean $isMaster 是否查询主库.
     *
     * @return \DbOperator\Operator
     */
    public function master($isMaster = true)
    {
        $this->master = !!$isMaster;
        return $this;
    }

    /**
     * 排序字段.
     *
     * @param string $order 排序字段.
     *
     * @return \DbOperator\Operator
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * 分组查询.
     *
     * @param string $group 分组查询字段.
     *
     * @return \DbOperator\Operator
     */
    public function group($group)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * 分组查询后的having限制.
     *
     * @param string $having 限制条件字符串xxx > 2.
     *
     * @return \DbOperator\Operator
     */
    public function having($having)
    {
        $this->having = $having;
        return $this;
    }

    /**
     * 限制条数.
     *
     * @param integer $a 当$b不为空时为LIMIT $a, $b, 当$b为空时为LIMIT $a.
     * @param integer $b 当$b不为空时为LIMIT $a, $b, 当$b为空时为LIMIT $a.
     *
     * @return \DbOperator\Operator
     */
    public function limit($a, $b = null)
    {
        $this->limit = array($a, $b);
        return $this;
    }

    /**
     * 指定查询字段.
     *
     * @param string $columns 查询字段.
     *
     * @return \DbOperator\Operator
     */
    public function select($columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * 查询条件.
     *
     * @param array $condition 查询条件.
     *
     * @return \DbOperator\Operator
     */
    public function where(array $condition)
    {
        $this->where = $condition;
        return $this;
    }

    /**
     * 当queryAll的时候指定返回值的下标字段.
     *
     * @param string $assocKey 下标字段名.
     *
     * @return \DbOperator\Operator
     */
    public function assocKey($assocKey)
    {
        $this->assocKey = $assocKey;
        return $this;
    }

    /**
     * 获取当queryAll的时候指定返回值的下标字段.
     *
     * @return string|null
     */
    public function getAssocKey()
    {
        return $this->assocKey;
    }

    /**
     * 查询类型.
     *
     * @param string $queryType 查询类型.
     *
     * @return \DbOperator\Operator
     */
    public function queryType($queryType)
    {
        $this->queryType = $queryType;
        return $this;
    }

    /**
     * 设置shardingRule(设置sharding库的规则).
     *
     * @param mixed $shardingRule 分表规则对象.
     *
     * @return \DbOperator\Operator
     */
    public function shardingRule($shardingRule)
    {
        $this->shardingRule = $shardingRule;
        return $this;
    }

    /**
     * 设置非sharding数据库对象配置名(设置了就是查询该非sharding库).
     *
     * @param string $dbName 数据库配置名.
     *
     * @return \DbOperator\Operator
     */
    public function dbConfigName($dbName)
    {
        $this->dbConfigName = $dbName;
        return $this;
    }

    /**
     * 设置数据库对象.
     *
     * @param \Db\Connection $db 数据库对象.
     *
     * @return \DbOperator\Operator
     */
    public function db(\Db\Connection $db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * 设置主表名字.
     *
     * @param string $table 表名.
     *
     * @return \DbOperator\Operator
     */
    public function from($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 操作db链接的SQL.
     *
     * @return \Db\Connection
     *
     * @throws \Exception 逻辑异常.
     */
    public function operator()
    {
        $tableName = $this->table;
        $queryAllPartition = $this->queryType == 'queryAllPartition' ? true : false;
        if ($this->dbConfigName) {
            $instance = $queryAllPartition ? \Db\Connection::instance() : ($this->partition ? \Db\Connection::instance()->partitionByUID($this->partition) : \Db\Connection::instance());
            if ($this->master) {
                $type = $queryAllPartition ? 'writeAll' : 'write';
                $db = $this->db = $instance->$type($this->dbConfigName);
            } else {
                $type = $queryAllPartition ? 'readAll' : 'read';
                $db = $this->db = $instance->$type($this->dbConfigName);
            }
        } elseif ($this->shardingRule) {
            $tableName = $this->shardingRule->getTableName($this->table);
            $this->db = $this->shardingRule->getDbSharding($this->partition);
            if ($this->master) {
                $type = $queryAllPartition ? 'writeAll' : 'write';
                $db = $this->db->$type();
            } else {
                $type = $queryAllPartition ? 'readAll' : 'read';
                $db = $this->db->$type();
            }
        } else {
            throw new \Exception("db instance invalid");
        }
        $columns = array();
        if (!empty($this->columns)) {
            $columns[] = $this->columns;
        }
        if ($queryAllPartition) {
            $result = array();
            foreach ($db as $eachDb) {
                $eachDb->select(implode(', ', array_merge($columns, $this->joinColumns)));
                if ($this->alias) {
                    $eachDb->from($tableName . ' ' . $this->alias);
                } else {
                    $eachDb->from($tableName);
                }
                foreach ($this->join as $join) {
                    list($joinTable, $alias, $joinCondition) = $join;
                    if (is_null($this->shardingRule)) {
                        $eachDb->join($joinTable . ' ' . $alias, $joinCondition);
                    } else {
                        $eachDb->join($this->shardingRule->getTableName($joinTable) . ' ' . $alias, $joinCondition);
                    }
                }
                foreach ($this->leftJoin as $leftJoin) {
                    list($leftJoinTable, $alias, $leftJoinCondition) = $leftJoin;
                    if (is_null($this->shardingRule)) {
                        $eachDb->leftJoin($leftJoinTable . ' ' . $alias, $leftJoinCondition);
                    } else {
                        $eachDb->leftJoin($this->shardingRule->getTableName($leftJoinTable) . ' ' . $alias, $leftJoinCondition);
                    }
                }
                if ($this->where) {
                    $eachDb->where($this->where);
                }
                if ($this->order) {
                    $eachDb->order($this->order);
                }
                if ($this->group) {
                    $eachDb->group($this->group);
                    if ($this->having) {
                        $eachDb->having($this->having);
                    }
                }
                if (!empty($this->limit)) {
                    list($offset, $limit) = $this->limit;
                    $eachDb->limit($offset, $limit);
                }
                $data = $eachDb->queryAll();
                if (!empty($data)) {
                    $result = array_merge($result, $data);
                }
            }
            return $result;
        } else {
            $db->select(implode(', ', array_merge($columns, $this->joinColumns)));
            if ($this->alias) {
                $db->from($tableName . ' ' . $this->alias);
            } else {
                $db->from($tableName);
            }
            foreach ($this->join as $join) {
                list($joinTable, $alias, $joinCondition) = $join;
                if (is_null($this->shardingRule)) {
                    $db->join($joinTable . ' ' . $alias, $joinCondition);
                } else {
                    $db->join($this->shardingRule->getTableName($joinTable) . ' ' . $alias, $joinCondition);
                }
            }
            foreach ($this->leftJoin as $leftJoin) {
                list($leftJoinTable, $alias, $leftJoinCondition) = $leftJoin;
                if (is_null($this->shardingRule)) {
                    $db->leftJoin($leftJoinTable . ' ' . $alias, $leftJoinCondition);
                } else {
                    $db->leftJoin($this->shardingRule->getTableName($leftJoinTable) . ' ' . $alias, $leftJoinCondition);
                }
            }
            if ($this->where) {
                $db->where($this->where);
            }
            if ($this->order) {
                $db->order($this->order);
            }
            if ($this->group) {
                $db->group($this->group);
                if ($this->having) {
                    $db->having($this->having);
                }
            }
            if (!empty($this->limit)) {
                list($offset, $limit) = $this->limit;
                $db->limit($offset, $limit);
            }
            if ($this->queryType) {
                if ($this->queryType == 'queryAll') {
                    $result = $db->queryAll(null, $this->assocKey);
                } else {
                    $result = $db->{$this->queryType}();
                }
                return $result;
            }
            return $db;
        }
    }
}
