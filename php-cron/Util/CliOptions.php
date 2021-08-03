<?php
/**
 * CliOptions
 *
 * @author Lin Hao<lin.hao@xiaonianyu.com>
 * @date 2020-12-24 10:28:30
 */

namespace Util;

class CliOptions
{
    private $options = array();
    private $values = array();

    public function addOptionValue($k, $v)
    {
        if(!isset($this->options[$k])) {
            $this->options[$k] = $v;
        } else {
            if( ! is_array($this->options[$k]) ) {
                $this->options[$k] = array($this->options[$k]);
            }
            $this->options[$k][] = $v;
        }
    }

    public function addValue($v)
    {
        $this->values[] = $v;
    }

    public function getOption($k, $def = null)
    {
        return isset($this->options[$k]) ? $this->options[$k] : $def;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function toArray()
    {
        return $this->options;
    }

    /**
     * @static
     * @return \Util\CliOptions
     */
    public static function ParseFromArgv()
    {
        global $argv, $argc;
        $options = new static;
        for($i = 1; $i < $argc; $i++)
        {
            $s = $argv[$i];
            if(substr($s, 0, 2) == '--')
            {
                $s = substr($s, 2);
                $a = explode('=', $s, 2);
                if(count($a) == 2) {
                    $options->addOptionValue($a[0], $a[1]);
                } else {
                    $options->addOptionValue($a[0], true);
                }
            } else {
                $options->addValue($s);
            }
        }
        return $options;
    }

}