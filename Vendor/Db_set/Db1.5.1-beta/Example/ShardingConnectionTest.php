<?php
require_once(__DIR__ . '/common.php');

while(1) {
    echo '*****************************************************************************************************' . PHP_EOL;
    try{
        $rule = new TrusteeshipRule(8); 
       // $db = \Db\ShardingConnection::instance()->setRule($rule);
        \Db\ShardingConnection::instance()->partitionByUID()->setRule($rule);
        $newrule = new TrusteeshipRule(10); 
        $db->setDataCenterRule($newrule);
        $tableName = $rule->getTableName('token_map');
        $res = $db->write($rule)->query("select * from $tableName limit 1");
        while($row = $res->fetch()) {
            var_dump($row[0]);
        }
    }catch (\Exception $e){
        echo $e->getMessage() . PHP_EOL;
    }
    usleep(500000);
    $db->closeAll();
}
