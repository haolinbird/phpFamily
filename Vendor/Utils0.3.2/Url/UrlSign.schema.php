<?php
namespace Config;

class UrlSign
{

    /**
     * 配置的app.
     *
     * @var array
     */
    public static $apps = array(
        'your_app_id' => array(
            'secret_key' => 'your_app_secret_key',
            'enable' => true,
            'time_range' => 300
        )
    );

}