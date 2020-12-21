<?php
namespace Db;

class Util
{
    // 针对conditon ["JSON_EXTRACT(attr, '$.name') = 'jingd'", "attr->>'$.name'= 'jingd'"]; 中的每一个项目执行检查, 判断表达式左侧是 table.field 还是一个mysql函数调用
    public static function isMysqlFuncCall($item)
    {
        // 认为业务已经处理了字段名含义冲突的问题, 不需要重复处理
        if (preg_match('! *`!', $item)) {
            return true;
        }

        // 函数调用判断
        if (preg_match('!^ *[a-z0-9_]+ *\(!i', $item)) {
            return true;
        }

        // json路径访问判断
        if (preg_match('!^ *(?:`{0,1}[a-z0-9_]+`{0,1} *\. *){0,1}`{0,1}[a-z0-9_]+`{0,1} *->!i', $item)) {
            return true;
        }

        return false;
    }

    // 检查特定字段的值是否是mysql表达式
    public static function fieldValueIsMysqlExpress($column)
    {
        if (substr($column, -6) == ':mysql') {
            return array(
                substr($column, 0, strlen($column) - 6),
                true,
            );
        }

        return array(
            $column,
            false,
        );
    }
}
