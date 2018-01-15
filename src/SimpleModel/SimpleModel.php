<?php
namespace SimpleModel;

use Medoo\Medoo;

class SimpleModel implements ArrayAccess
{
    protected $attributes;

    protected $errorMessage;

    protected static $tablename;

    protected static $pk = 'id';

    protected $recordUpdateAt = true;

    protected $recordCreateAt = true;

    protected static $checkExists = true;

    public function __construct($attr = [])
    {

    }

    public function offsetExists($offset)
    {

    }
    public function offsetGet($offset)
    {

    }
    public function offsetSet($offset, $value)
    {

    }
    public function offsetUnset($offset)
    {
        
    }
}
