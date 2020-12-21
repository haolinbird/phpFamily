<?php
/**
 * Created by PhpStorm.
 * User: ubuntu
 * Date: 18-11-1
 * Time: 下午4:25
 */

namespace Config {

    class PHPClient
    {
        public $rpc_secret_key = '769af463a39f077a0340a189e9c1ec28';
        public $recv_time_out = 10;
        public $connection_time_out = 2;

        // 验签services
        public $AntiFraud = array(
            'uri' => array(
                1 => '127.0.0.1:10006:10',
            ),
            'user' => 'UserCenterAPI',
            'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537E}',
            'service' => 'AntiFraud',
            'dove_key' => 'AntiFraud.Service.uri'
        );
    }
}

namespace RiskAntiFraudUtil\Test {

    ini_set("display_errors", true);
    error_reporting(E_ALL);

    include "../Vendor/Bootstrap/Autoloader.php";

    \Bootstrap\Autoloader::instance()->addRoot(__DIR__ . '/')->init();

    include "../AntiFraud.php";

    echo "----get token call---- \n\n";

    $r = \RiskAntiFraudUtil\AntiFraud::instance()->getToken(
        'micro_video',
        '123',
        '127.0.0.1'
    );

    var_dump($r);

    echo "-----check sign call---------\n\n";

    $r = \RiskAntiFraudUtil\AntiFraud::instance()->checkSign(
        'micro_video',
         array('za' => "fdafda", "al" => 'kkk', 'maa' => 'nnnn'),
        '224n3417sn01112ror8174q541s58orr40qp4pn5',
        1541125684,
        1141230155
    );
    var_dump($r);
}

