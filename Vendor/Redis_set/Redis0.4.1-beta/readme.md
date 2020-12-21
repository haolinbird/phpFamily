                                 新版redis使用文档

具体使用方法参考demon.php我在这里只说下需要说明的地方：

1.暂时配置系统没上线需要业务在入口文件中配置集群的ip和端口，
  具体哪个业务用哪些ip和端口，请朝dba王伟要。
2.迁移数据请参考SharkRedis.php
3."INFO", "KEYS", "BLPOP", "MSETNX", "BRPOP", "RPOPLPUSH", "BRPOPLPUSH", "SMOVE", "SINTER", "SINTERSTORE", "SUNION", "SUNIONSTORE", "SDIFF", "SDIFFSTORE", "ZINTER", "ZUNION",
  "FLUSHDB", "FLUSHALL", "RANDOMKEY", "SELECT", "MOVE", "RENAMENX", "DBSIZE", "BGREWRITEAOF", "SLAVEOF", "SAVE", "BGSAVE", "LASTSAVE"
  这些方法已被禁用，请业务周知
4.新的redis客户端分为存储和cache，cache采用一致性hash，存储取摸，并提供了failover（301不上），虽然支持了mget和mset方法，但请尽量少用。
5.支持了multi和exec方法，但是事务只可以用一个key
6.服务器选择，在原有支持一致性hash的基础上新加了对redis server随机选择算法的支持

关于一致性hash的特殊说明：
一致性hash的虚拟结点个数不是固定的，这样做增加或者删除结点的时候miss率会稍有增加。但是由于咱们公司的业务模式决定（经常促销，促销的量是平常的很多倍）。
这样可以避免大促时候结点过多，导致的hash路由耗时比较多。
而大促完事后，量非常少，及时miss率较高也没事。

关于随机选择算法的特殊说明：
在访问redis slave时使用，在使用一致性hash算法的时候，特定的key始终命中特定的一台redis slave server；本算法下，如果有多于1个redis slave server，那么特定的key每次访问均可能命中不同的redis slave server


*变更记录*

* 0.4.1-beta 变更
```
class Redis
{
    public $default_slave = array(
        'db' => 12,
        'consistent' => false, // 未定义或为true使用一致性hash算法，为false使用随机选择算法
        'nodes' => array(
            array('master' => '127.0.0.1:6379'),
            array('master' => '127.0.0.1:7369'),
        ),
    );
}
```

