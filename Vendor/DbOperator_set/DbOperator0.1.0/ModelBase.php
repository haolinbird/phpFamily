<?php
/**
 * Model Base.
 *
 * @author dengjing<jingd3@jumei.com>
 */

namespace DbOperator;

/**
 * Model Base.
 */
class ModelBase extends \Model\DbBase
{
    /**
     * CRM库名.
     */
    // const DB_NAME_CRM = \Config\Db::DB_NAME_DEFAULT;

    /**
     * Get Instance.
     *
     * @param boolean $singleton Is Singleton.
     *
     * @return \Model\ModelBase
     */
    public static function instance($singleton = true)
    {
        return parent::instance($singleton);
    }

    /**
     * 抛出业务异常.
     *
     * @param string  $message 异常信息.
     * @param integer $code    错误代码.
     *
     * @return void
     * @throws \RpcBusinessException 业务异常.
     */
    public function rpcBusinessException($message, $code = 0)
    {
        throw new \RpcBusinessException($message, $code);
    }

    /**
     * 获取数据库连接.
     *
     * @param string  $name   连接名.
     * @param boolean $master 是否主库.
     *
     * @return \Db\Connection
     */
    public function getDb($name, $master = false)
    {
        $type = $master ? 'write' : 'read';
        return $this->db($name, $type);
    }

    /**
     * 获取当前模型数据库链接.
     *
     * @param boolean $master 是否主库.
     *
     * @return \Db\Connection
     *
     * @throws \Exception 异常.
     */
    public function getDbBase($master = false)
    {
        if (!static::DB_NAME) {
            $className = get_called_class();
            throw new \Exception($className .' DB_NAME undefinded');
        }
        return $this->getDb(static::DB_NAME, $master);
    }

    /**
     * 写入一条数据.
     *
     * @param array $params 写入的字段值数组.
     *
     * @return boolean|integer
     */
    public function insertBase(array $params)
    {
        return $this->getDb(static::DB_NAME, true)->insert(static::TABLE_NAME, $params);
    }

    /**
     * 删除数据(慎用).
     *
     * @param array $condition 删除条件.
     *
     * @return integer
     */
    public function deleteBase(array $condition)
    {
        if (empty($condition)) {
            throw new \Exception('delete sql should have condition on [' . static::TABLE_NAME  . ']');
        }
        return $this->getDbBase(true)->delete(static::TABLE_NAME, $condition);
    }

    /**
     * 批量写入数据.
     *
     * @param array $params 待写入的字段值数组.
     *
     * @return boolean|integer
     *
     * @throws \Exception 系统异常.
     */
    public function insertBatchBase(array $params)
    {
        $db = $this->getDb(static::DB_NAME, true);
        $columnsArray = array();
        $valuesArray = array();
        foreach ($params as $each) {
            if (!is_array($each) || empty($each)) {
                throw new \Exception('invalid batch insert array format : ' . var_export($params, true));
            }
            foreach ($each as $k => $v) {
                $each[$k] = $db->quote($v);
                $columnsArray[$k] = $k;
            }
            ksort($each);
            $valuesArray[] = implode(', ', $each);
        }
        ksort($columnsArray);
        $values = '(' . implode('), (', $valuesArray) . ')';
        $columns = '(`' . implode('`, `', $columnsArray) . '`)';
        $table = static::TABLE_NAME;
        $sql = "INSERT INTO {$table} {$columns} VALUES {$values}";
        return $db->exec($sql);
    }

    /**
     * 更新数据.
     *
     * @param array $params    更新字段值数组.
     * @param array $condition 更新条件.
     *
     * @return boolean|integer
     */
    public function updateBase(array $params, array $condition)
    {
        return $this->getDb(static::DB_NAME, true)->update(static::TABLE_NAME, $params, $condition);
    }

    /**
     * 检查指定条件的数据是否存在.
     *
     * @param array $condition  条件参数.
     * @param mixed $dbOperator 数据库操作对象.
     *
     * @return boolean
     */
    public function existsBase(array $condition, $dbOperator = null)
    {
        return !!$this->queryScalarBase($condition, '1', $dbOperator);
    }

    /**
     * 查询一条数据.
     *
     * @param array  $condition  查询条件.
     * @param string $columns    查询字段.
     * @param mixed  $dbOperator 数据库操作对象.
     *
     * @return array|boolean
     */
    public function queryRowBase(array $condition, $columns = '*', $dbOperator = null)
    {
        return $this->parseQueryBase($condition, $columns, 'queryRow', $dbOperator);
    }

    /**
     * 查询全部数据.
     *
     * @param array  $condition  查询条件.
     * @param string $columns    查询字段.
     * @param mixed  $dbOperator 数据库操作对象.
     *
     * @return array
     */
    public function queryAllBase(array $condition, $columns = '*', $dbOperator = null)
    {
        return $this->parseQueryBase($condition, $columns, 'queryAll', $dbOperator);
    }

    /**
     * 查询一个字段.
     *
     * @param array  $condition  查询条件数组.
     * @param string $columns    查询字段.
     * @param mixed  $dbOperator 数据库操作对象.
     *
     * @return string|integer|null
     */
    public function queryScalarBase(array $condition, $columns = '*', $dbOperator = null)
    {
        return $this->parseQueryBase($condition, $columns, 'queryScalar', $dbOperator);
    }

    /**
     * 查询一个字段.
     *
     * @param array  $condition  查询条件数组.
     * @param string $columns    查询字段.
     * @param mixed  $dbOperator 数据库操作对象.
     *
     * @return string|integer|null
     */
    public function querySimpleBase(array $condition, $columns = '*', $dbOperator = null)
    {
        return $this->queryScalarBase($condition, $columns, $dbOperator);
    }

    /**
     * 查询指定字段.
     *
     * @param array  $condition  查询条件.
     * @param string $columns    查询字段.
     * @param mixed  $dbOperator 数据库操作对象.
     *
     * @return array
     */
    public function queryColumnBase(array $condition, $columns = '*', $dbOperator = null)
    {
        return $this->parseQueryBase($condition, $columns, 'queryColumn', $dbOperator);
    }

    /**
     * 解析查询语句.
     *
     * @param array  $condition  查询条件.
     * @param string $columns    查询字段.
     * @param string $queryType  查询类型.
     * @param mixed  $dbOperator 数据库操作对象.
     *
     * @return mixed
     */
    protected function parseQueryBase(array $condition, $columns, $queryType, $dbOperator)
    {
        if ($dbOperator instanceof Operator) {
            $result = $dbOperator->dbConfigName(static::DB_NAME)->select($columns)->from(static::TABLE_NAME)->where($condition)->queryType($queryType)->operator();
        } else {
            $master = $dbOperator === true ? true : false;
            $result = $this->getDb(static::DB_NAME, $master)->select($columns)->from(static::TABLE_NAME)->where($condition)->{$queryType}();
        }
        return $result;
    }

    /**
     * 查询分页数据.
     *
     * @param array   $condition  查询条件.
     * @param string  $columns    查询字段.
     * @param integer $page       页码.
     * @param integer $pageSize   每页条数.
     * @param mixed   $dbOperator 数据库操作对象.
     *
     * @return array
     */
    public function queryPageBase(array $condition, $columns = '*', $page = 1, $pageSize = 10, $dbOperator = null)
    {
        if (empty($dbOperator)) {
            $dbOperator = new Operator();
        } elseif ($dbOperator === true) {
            $dbOperator = new Operator();
            $dbOperator->master();
        }
        // 查询总数的时候不需要order by.
        $countOp = clone $dbOperator;
        $countOp->order('');
        $count = $this->parseQueryBase($condition, 'COUNT(1)', 'queryScalar', $countOp);
        $offset = ($page - 1) * $pageSize;
        $dbOperator->limit($offset, $pageSize);
        $rows = $this->parseQueryBase($condition, $columns, 'queryAll', $dbOperator);
        $result = array(
            'count' => (int)$count,
            'total_page' => ceil($count / $pageSize),
            'current_page' => (int)$page,
            'page_size' => (int)$pageSize,
            'rows' => $rows,
        );
        return $result;
    }

    /**
     * 查询分页数据.
     *
     * @param array   $condition  查询条件.
     * @param string  $columns    查询字段.
     * @param integer $page       页码.
     * @param integer $pageSize   每页条数.
     * @param mixed   $dbOperator 数据库操作对象.
     *
     * @return array
     */
    public function queryPageGropuBase(array $condition, $columns = '*', $page = 1, $pageSize = 10, $dbOperator = null, $group)
    {
        if (empty($dbOperator)) {
            $dbOperator = new Operator();
        } elseif ($dbOperator === true) {
            $dbOperator = new Operator();
            $dbOperator->master();
        }
        // 查询总数的时候不需要order by.
        $countOp = clone $dbOperator;
        $dbOperator->group($group);
        $countOp->order('');
        $count = $this->parseQueryBase($condition, "COUNT(distinct $group)", 'queryScalar', $countOp);
        $offset = ($page - 1) * $pageSize;
        $dbOperator->limit($offset, $pageSize);
        $rows = $this->parseQueryBase($condition, $columns, 'queryAll', $dbOperator);
        $result = array(
            'count' => (int)$count,
            'total_page' => ceil($count / $pageSize),
            'current_page' => (int)$page,
            'page_size' => (int)$pageSize,
            'rows' => $rows,
        );
        return $result;
    }
}
