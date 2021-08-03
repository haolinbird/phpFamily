<?php

/**
 * 数据库配置文件
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2021-06-16 11:28:30
 */

namespace Config;

class Db
{
    public $DEBUG = false;
    public $DEBUG_LEVEL = 0;

    /**
     * Configs of database.
     * @var array
     */
    public $read = array(
        'default' => array(
            'host'         => "#{Res.Db.Xiangzhe.Read.Host}",
            'port'         => "#{Res.Db.Xiangzhe.Read.Port}",
            'db'           => "#{Res.Db.Xiangzhe.Read.Database}",
            'user'         => "#{Res.Db.Xiangzhe.Read.User}",
            'password'     => "#{Res.Db.Xiangzhe.Read.Password}",
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 2,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false
            ),
        ),
        'xiangzhe' => array(
            'host'         => "#{Res.Db.Xiangzhe.Read.Host}",
            'port'         => "#{Res.Db.Xiangzhe.Read.Port}",
            'db'           => "#{Res.Db.Xiangzhe.Read.Database}",
            'user'         => "#{Res.Db.Xiangzhe.Read.User}",
            'password'     => "#{Res.Db.Xiangzhe.Read.Password}",
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 2,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false
            ),
        ),
        'xiaonianyu' => array(
            'host'         => "#{Res.Db.Xiaonianyu.Read.Host}",
            'port'         => "#{Res.Db.Xiaonianyu.Read.Port}",
            'db'           => "#{Res.Db.Xiaonianyu.Read.Database}",
            'user'         => "#{Res.Db.Xiaonianyu.Read.User}",
            'password'     => "#{Res.Db.Xiaonianyu.Read.Password}",
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 2,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false
            ),
        )
    );

    public $write = array(
        'default' => array(
            'host'         => "#{Res.Db.Xiangzhe.Write.Host}",
            'port'         => "#{Res.Db.Xiangzhe.Write.Port}",
            'db'           => "#{Res.Db.Xiangzhe.Write.Database}",
            'user'         => "#{Res.Db.Xiangzhe.Write.User}",
            'password'     => "#{Res.Db.Xiangzhe.Write.Password}",
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 2,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false
            ),
        ),
        'xiangzhe' => array(
            'host'         => "#{Res.Db.Xiangzhe.Write.Host}",
            'port'         => "#{Res.Db.Xiangzhe.Write.Port}",
            'db'           => "#{Res.Db.Xiangzhe.Write.Database}",
            'user'         => "#{Res.Db.Xiangzhe.Write.User}",
            'password'     => "#{Res.Db.Xiangzhe.Write.Password}",
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 2,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false
            ),
        ),
        'xiaonianyu' => array(
            'host'         => "#{Res.Db.Xiaonianyu.Write.Host}",
            'port'         => "#{Res.Db.Xiaonianyu.Write.Port}",
            'db'           => "#{Res.Db.Xiaonianyu.Write.Database}",
            'user'         => "#{Res.Db.Xiaonianyu.Write.User}",
            'password'     => "#{Res.Db.Xiaonianyu.Write.Password}",
            'confirm_link' => true,
            'options'      => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                \PDO::ATTR_TIMEOUT => 2,
                \PDO::ATTR_STRINGIFY_FETCHES => false,
                \PDO::ATTR_EMULATE_PREPARES => false
            ),
        )
    );
}
