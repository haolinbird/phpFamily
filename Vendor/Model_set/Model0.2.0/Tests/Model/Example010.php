<?php
namespace Model;

class Example010 extends DbBase
{
    const DB_NAME = 'tuanmei';
    const TABLE_NAME = 'tuanmei_user';
    const PRIMARY_KEY = 'uid';
    protected static $fields = array('uid' => null, 'status' => null,
                              'register_time' => null, 'lastvisit_time' =>null,
                              'referer_id'=>null, 'reg_ip'=>null,
                              'last_ip'=>null, 'referer_site'=>null,
                              'privilege_group'=>null, 'privilege_expire_time'=>null,
                              'nickname'=>null,'email'=>null
    );

    public function saveUser($data)
    {
        $this->db(null, 'write')->beginTransaction();
        try {
            $this->save($data);
            $savedData = $this->db(null, 'write')->select('*')
                ->from($this::TABLE_NAME)
                ->where(array($this::PRIMARY_KEY => $data[$this::PRIMARY_KEY]))
                ->queryRow();
        }
        catch(\Exception $ex)
        {
            $this->db(null, 'write')->rollback();
            throw $ex;
        }
        foreach($data as $k=>$v)
        {
            if($data[$k] != $savedData[$k])
            {
                return false;
            }
        }
        $this->db(null, 'write')->rollback();
        return true;
    }
}