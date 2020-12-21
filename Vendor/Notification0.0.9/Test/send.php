<?php
require_once('base.php');

$client = new Notification\Client();
//$uids = file('../uids.txt');
$uids = array(
    1204245,
    78160205
);

foreach ($uids as $uid) {
    $payload = new Notification\Payload();
    $payload->setTitle('为你助力，福利延长！');
    $payload->setText("为你助力");
    $payload->setSound();
    $payload->setBadge(1);
    $url = "web?u=".urlencode("http://h5.jumei.com/yiqituan/detailSecret?item_id=ht150819p1491882t2&type=global_deal");
    $payload->set('p',$url);
    $uid = trim($uid);
    $ret = $client->push($uid, $payload);
//    $ret = true;
    if ($ret) {
        echo "$uid success\n";
    } else {
        echo "$uid failed : $client->error\n";
    }
}
