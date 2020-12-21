数据访问层组件
============
## 安装扩展需求
* "ext-pdo_mysql":">=1.0.0",
* "ext-mysqli": ">=0.0.1"
## 发布日志

### 1.5.2-beta
* 当epg mysql连接受限时自动重新寻找可用epg节点进行向对应的sql操作(客户端无感知，对应的性能会有降低:qps降低30%左右)

### 1.5.1-beta
* 支持在字段值中使用mysql表达式, 业务需要自行保证 mysql表达式 是安全的.
* 如果希望将 字段值 视为一个mysql表达式, 那么需要为 字段名 添加 :mysql 后缀.
* 如果 字段名 符合规则: ^ *[a-zA-Z0-9_]+ *(, 那么字段名将作为一个mysql表达式运行, 如果同时希望 字段值 也作为一个mysql表达式执行, 那么请参考上一条.


```php
// 使用说明:
// 如果字段名携带:mysql后缀, 则db组件将字段值作为一个mysql表达式处理, 否则将字段值视为普通字符串.

$db = \Db\Connection::instance()->write('statistics');

// 在更新时使用mysql表达式的例子
$db->update('check_list', [
    'data_timeout:mysql' => 'md5("bbb")',
], [
    'check_list_id:mysql' => 'md5("aaa")',
]);
// OUTPUT: UPDATE  `check_list` SET data_timeout=md5("bbb") WHERE  (`check_list_id` = md5("aaa"))
echo $db->getLastSql(), PHP_EOL;

// 插入数据时使用mysql表达式的例子
$db->insert('check_list', [
    'data_timeout:mysql' => 'unix_timestamp(now())',
]);
// OUTPUT: INSERT  INTO `check_list` (`data_timeout`) VALUES (unix_timestamp(now()))
echo $db->getLastSql(), PHP_EOL;

// 普通查找(不使用mysql表达式)
$db->findAll('check_list', [
    'check_list_id>' => 1,
    'data_timeout>' => 'unix_timestamp(now())',
]);
// 注意这一条查询data_timeout字段的值是字符串
// OUTPUT: SELECT * FROM `check_list`  WHERE  (`check_list_id` > '1')  AND ( `data_timeout` > 'unix_timestamp(now())' )
echo $db->getLastSql(), PHP_EOL;

// 查找数据时使用mysql表达式的例子
$db->findAll('check_list', [
    'check_list_id>' => 1,
    'data_timeout>:mysql' => 'unix_timestamp(now())',
]);
// OUTPUT: SELECT * FROM `check_list`  WHERE  (`check_list_id` > '1')  AND ( `data_timeout` > unix_timestamp(now()) )
echo $db->getLastSql(), PHP_EOL;

// 查找数据时使用mysql表达式的例子
$db->findAll('check_list', [
    "JSON_EXTRACT(description, '$.name') = 'jingd'",
    "description->>'$.name'= 'jingd'",
    'check_list_id = 1'
]);
// SELECT * FROM `check_list`  WHERE (JSON_EXTRACT(description, '$.name') = 'jingd') AND (description->'$.name'= 'jingd') AND (`check_list_id` = 1)
echo $db->getLastSql(), PHP_EOL;

// 查找数据时使用mysql表达式的例子(字段名是 mysql表达式)
$db->findAll('check_list', [
    "JSON_EXTRACT(description, '$.name')" => 'jingd',
    "description->>'$.name'" => 'jingd',
    'check_list_id = 1'
]);
// SELECT * FROM `check_list`  WHERE  (JSON_EXTRACT(description, '$.name') = 'jingd')  AND ( description->>'$.name' = 'jingd' ) AND (`check_list_id` = 1)
echo $db->getLastSql(), PHP_EOL;

// 查找数据时使用mysql表达式的例子(字段名 和 字段值 都是mysql表达式)
$db->findAll('check_list', [
    "JSON_EXTRACT(description, '$.name'):mysql" => 'now()',
    "description->>'$.name'" => 'jingd',
    'check_list_id = 1'
]);
echo $db->getLastSql(), PHP_EOL;
```

### 1.5.0-alpha
* 支持parttionDsn配置,支持按照uid选择分区dsn配置,默认使用第一个分区.

### 1.4.10-beta
* 修复日志快关失效.
* debug日志记录下dsn以及创建连接时的调用堆栈.
* 回滚事务时再次确认是否存在事务. 

### 1.4.9-beta
* 支持全链路压测，压测环境数据写到影子库 

### 1.4.8-beta
* 捕获包括PHPServer抛出的超时异常，封装后当前使用的epg节点信息后再次抛出
* Connection, ShardingConnection类创建连接由self()改为static()以兼容业务继承该类

### 1.4.7
* 基于1.4.7-beta2创建的稳定版

### 1.4.7-beta2
* 增加epg坏节点剔除，支持自动重试与节点定时（5s）恢复.
* 重构，增强代码复用性与可读维护性
* 减除无用代码（包括老连接池代码、异步调用代码）

### 1.4.7-beta
* 增加业务可定制化多中心规则的功能，可定制default_read,以及db2db的映射规则, 定制化的read dc细化到db级别.

### 1.4.4-beta
* 增加业务可定制化多中心规则的功能，可定制default_read,以及db2db的映射规则。

### 1.2.6
* 修正exec时没有记录lastsql的问题。

### 1.2.5
* 在异常消息中添加dsn信息.

### 1.2.4
* 修正shardingdb 中使用pdo时数据库连接选项不能正确设置的问题。

### 1.2.3
* 记录连接池的错误

### 1.2.2
* 优化sharding的关闭操作。

### 1.2.1
* 修复DbSharding 查询失败时没有详细错误信息的问题。

### 1.2.0 
* 基于连接池稳定版.
* 支持客户端负责均衡(随机算法).
* 支持多种配置格式(参考Example中的数据库配置).

### 1.1.0
* 连接池首个稳定版。
* 事务支持可能还有问题，不建议在此版本中使用复杂事务。

### 1.1.1-alpha
* 在1.1.0基础上解决了事务支持的问题。

### 1.1.1
* 连接池稳定版，已经解决了事物问题。

### 1.1.2
* 修正抛异常时，如果使用的是连接池，会调用errorInfo方法而导致致命错误。

### 1.1.3
* 合并1.0.7的patch。

### 1.1.4-alpha
* 合并1.0.7的patch。

### 1.0.x
* 无连接池的稳定版本
### 1.0.7
* 修复了pdo的BUG: 当查询失败后，进程若不退出则无法释放链接。
### 1.0.8
* 回复了允许在where方法中直接拼字符串条件（安全降低): $db->select('1')->from('my_table')->where(array('field1=31'))->queryAll();
