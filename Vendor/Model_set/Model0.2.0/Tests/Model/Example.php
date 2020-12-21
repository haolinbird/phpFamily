<?php
namespace Model;

class Example extends DbBase
{
    const DB_NAME = 'tuanmei';
    const TABLE_NAME = 'tuanmei_user';
    const PRIMARY_KEY = 'uid';
    protected static $fields = array('uid' , 'status',
                              'register_time', 'lastvisit_time',
                              'referer_id', 'reg_ip',
                              'last_ip', 'referer_site',
                              'privilege_group', 'privilege_expire_time',
                              'nickname','email'
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