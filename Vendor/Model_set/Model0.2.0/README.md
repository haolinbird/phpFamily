# 数据访问基础层封装

如需使用数据库或redis，请在项目中添加相关依赖。
例如：
<pre>
    "require":{
        "Db":">=1.0.1",
        "Redis":">="0.2.0"
    },
</pre>

## 历史

### 0.1.0

第一个带基本功能的可用版本。

1. 支持单例。
1. 将字段值填充后可以直接通过属相实例的属性访问。

### 0.2.0

在上个版本的功能上增加了以下特性：

1. Db操作类支持快速获取数据库连接对象。
    
        // 获取jumei读库(默认)连接对象。
        $this->db('jumei'); 
        //等价于
        \Db\Connection::instance()->read('jumei');
        
        // 获取jumei写库连接对象。
        $this->db('jumei',  'write');
        //等价于
        \Db\Connection::instance()->write('jumei');   
             
        // 获取Model中DB_NAME常量定义的数据读库连接对象。 
        $this->db();

1. Db操作类支持快速获取Redis连接对象。

        $this->redis('configName');
       
1. 支持获取新同一个Model的新实例

        $newInst = \Model\Example::instance(false);
        
1. 新增根据主键判断表里的记录是否存在的方法

        $this->primaryKeyExists(31)
        
1. 增加根据条件判断记录是否存在的方法。
        
        $this->exists(array('register_time'=>130021213, 'age'=>20));

