<?php
namespace Model;

/**
 * Base classes of models which base on database.
 */
class DbBase extends Base{
    /**
     * holds the table's field values which can be accessed via the magic __get, and these fields should be defined in the static $fields property of the derived class.
     *
     * @var array
     */
    protected $fieldProperties = array();


    /**
     * magic __get method. You can access the field values, if filled,  directly.
     *
     * @param string $name
     * @return mixed
     * @throws \Model\Exception
     */
    public function __get($name)
    {
        switch ($name)
        {
            default:
                if(isset($this->fieldProperties[$name]))
                {
                    return $this->fieldProperties[$name];
                    continue;
                }
                throw new Exception('Try get undefined property "'.$name.'" of class '.get_called_class().'. Forgot to call fillFields ?');
                continue;
        }
    }

    /**
     * Fill the table fields with the values. The fields that absent in the $value keys will be filled with null, while the keys which not defined in the $fields property will be ignored.
     *
     * @param array $values  e.g. array('id'=>32, 'user_name' => 'chaos' )
     * @return \Model\DbBase  the instance of the calss.
     * @throws \Model\Exception
     */
    public function fillFields(array $values)
    {
        if (!property_exists($this, 'fields') || !is_array($this::$fields))
        {
            throw new Exception('You cannot call this method, $fields propery is not defined in class '.get_class($this).' or is not an array!');
        }

        $this->fieldProperties = array();

        foreach ($values as $k => $v)
        {
            if (!in_array($k, $this::$fields))
            {
                throw new Exception('Try to fill a field "'.$k.'" that is not defined in property "fields" of Model '.get_class($this).'. Is it a typo ?');
            }
            else
            {
                $this->fieldProperties[$k] = $v;
            }
        }
        return $this;
    }
}
