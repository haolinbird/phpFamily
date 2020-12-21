<?php
require 'common.php';
class DbBaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $name 数据库配置名称
     * @dataProvider dbList
     */
    public function testGetDb($name)
    {
        $model = \Model\DbBase::instance();
        $db = $model->db($name);
        $this->assertEquals('Db\Connection', get_class($db));
    }

    /**
     * 测试获取新model实例.
     *
     * @param bool $singleton
     * @dataProvider instTypes
     */
    public function testForceNewInstance($singleton)
    {
        $m1 = \Model\Example::instance($singleton);
        $m2 = \Model\Example::instance($singleton);
        if($singleton == false)
        {
            $this->assertEquals(false, $m1 === $m2);
        }
        else
        {
            $this->assertEquals(true, $m1 === $m2);
        }

    }

    public function instTypes()
    {
        return array(
            array(true),
            array(false)
        );
    }

    /**
     * 测试save方法。
     *
     * @dataProvider fieldValues
     */
    public function testSaveFunc(array $values)
    {
        $this->assertEquals(true, \Model\Example::instance()->saveUser($values));
        $this->assertEquals(true, \Model\Example010::instance()->saveUser($values));
    }

    /**
     * 字段内容填充测试。
     *
     * @param array $value
     * @dataProvider fieldValues
     */
    public function testGetFillFields(array $value)
    {
        $model = \Model\Example::instance();
        $model->fillFields($value);
        foreach($value as $k=>$v)
        {
            $this->assertEquals($v, $model->$k);
        }

        $model = \Model\Example010::instance();
        $model->fillFields($value);
        foreach($value as $k=>$v)
        {
            $this->assertEquals($v, $model->$k);
        }
    }

    /**
     * 数据库连接名称列表。
     *
     * @return array
     */
    public function dbList()
    {
        return array(
            array('tuanmei'),
            array('stats')
        );

    }

    /**
     * 测试字段内容。
     *
     * @reutrn array
     */
    public function fieldValues()
    {
        return array(
            array(array('uid' => 2, 'status' => 1,
                'register_time' => 1269573025, 'lastvisit_time' =>1383189405,
                'referer_id'=>0, 'reg_ip'=>'60.12.235.23',
                'last_ip'=>'60.12.235.23', 'referer_site'=>'www.jumei.com',
                'privilege_group'=>1, 'privilege_expire_time'=>0,
                'nickname'=>'valley','email'=>'valley@jumei.com'
            )),
            array(array('uid' => 1, 'status' => 1,
                'register_time' => 1269573025, 'lastvisit_time' =>1383189405,
                'referer_id'=>0, 'reg_ip'=>'60.12.235.23',
                'last_ip'=>'60.12.235.23', 'referer_site'=>'www.jumei.com',
                'privilege_group'=>1, 'privilege_expire_time'=>0,
                'nickname'=>'mikey01', 'email'=>'mikey011@jumei.com'
            ))
        );
    }
}