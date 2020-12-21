<?php

namespace Config;

class MNLogger
{
    public $exception = array(
            'on' => true,
            'app' => 'IntegrateExample-service',
            'logdir' => '/tmp/logs/monitor/',
            'server' => '127.0.0.1:9000',

    );


    public $trace = array(
        'on' => true,
        'app' => 'IntegrateExample-service',
        'logdir' => '/tmp/logs/monitor/',
            'server' => '127.0.0.1:9000',
    );
}
