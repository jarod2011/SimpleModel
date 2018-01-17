<?php
namespace SimpleModel;

class Collection implements \ArrayAccess, \Iterator
{
    protected $data;

    protected $pos;

    public function __construct($data)
    {
        $this->data = $data;
        $this->pos = 0;
    }

    public function count()
    {
        return count($this->data);
    }

    public function first()
    {
        return @$this->data[0];
    }

    public function last()
    {
        return @$this->data[$this->count() - 1];
    }

    public function all()
    {
        return $this->data;
    }

    public function toArray()
    {
        return array_map(function ($v) {
            if (! is_array($v)) {
                if (method_exists($v, "toArray")) {
                    return $v->toArray();
                } elseif (method_exists($v, "__toString")) {
                    return $v->__toString();
                } else {
                    return json_encode($v);
                }
            }
            return $v;
        }, $this->data);
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function current()
    {
        return $this->offsetGet($this->pos);
    }

    public function key()
    {
        return $this->pos;
    }

    public function next()
    {
        ++ $this->pos;
    }

    public function rewind()
    {
        $this->pos = 0;
    }

    public function valid()
    {
        return $this->offsetExists($this->pos);
    }
}
