<?php
namespace SimpleModel;

use Medoo\Medoo;
use SimpleModel\Registry;

class SimpleModel implements \ArrayAccess
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
        if ($attr) {
            foreach ($attr as $k => $v) {
                $this->offsetSet($k, $v);
            }
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }
    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
    }
    public function offsetSet($offset, $value)
    {
        $this->attributes[$offset] = $value;
    }
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }
    public function toArray()
    {
        return array_map(function ($attr) {
            if ($attr instanceof SimpleModel) {
                return $attr->toArray();
            }
            return $attr;
        }, $this->attributes);
    }
    public function __toString()
    {
        return \json_encode($this->toArray());
    }
    public static function getOrm($onlyRead = false)
    {
        if ($onlyRead && Registry::exists("orm_read")) {
            return Registry::get("orm_read");
        }
        return Registry::get("orm");
    }
}
