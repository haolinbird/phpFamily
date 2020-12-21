<?php
class Module_Base{
    /**
     * @return static
     */
    public static function instance(){
        return new static;
    }
}