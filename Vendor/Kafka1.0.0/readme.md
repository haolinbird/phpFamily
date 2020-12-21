                                Kafka库使用文档

#使用说明

>该kafka实现基于https://github.com/arnaud-lb/php-rdkafka

##配置
```
#\Config\Kafka.php

namespace Config;

class Kafka
{
    // 该配置用于实例化一个RdKafka\Conf配置对象，并传递给Producer/Consumer
    // value不为\Closure 或 \TopicConf类型时, 调用 RdKafka\Conf::set($key, $value).
    // value为\Closure 或 \TopicConf类型时, 调用 RdKafka\Conf::$key($value)
    public $default = array(
        'metadata.broker.list' => 127.0.0.1:9092', // broker list.
        'group.id' => 'xxx', // Consumer group.
        'auto.commit.interval.ms' => 100,
    );
    
    // 另一个配置
    public $test = array(
        'metadata.broker.list' => 192.168.0.1:9092', // broker list.
    );
}
```
##生产者
```
$p = \Kafka\Producer::instance('default'); // \Config\Kafka::$default
$topic = $p->newTopic("mytopic");
```
##消费者
```
$c = \Kafka\Consumer::instance('default');
$topic = $c->newTopic("mytopic");
$topic->consumeStart(0, RD_KAFKA_OFFSET_END);

while (true) {
    $m = $topic->consume(0, 12 * 10000);
    echo $m->payload, PHP_EOL;
}
```
#变更日志
1. 1.0.0-beta

    获得Producer/Consumer的单例时，从\Config\Kafka读取特定的配置，使用这些配置实例化一个\RdKafka\Conf对象，并将这个对象作为Producer/Consumer的构造函数参数传入，其余用法请参考相关原生类