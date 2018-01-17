<?php
namespace SimpleModel;

use Medoo\Medoo;
use SimpleModel\Registry;
use SimpleModel\Collection;

class SimpleModel implements \ArrayAccess
{
    const LIMIT = 20;

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

    protected static function buildPrimaryWhere($primaryValues)
    {
        $where = [];
        $pk = explode(".", static::$pk);
        foreach ($pk as $_pk) {
            if (! isset($primaryValuesprimary[$_pk]) && count($pk) > 1 || is_array($primaryValues) && ! isset($primaryValues[$_pk])) {
                throw new \Exception(get_called_class() . "not give all primary key values[{$_pk}]");
            }
            $where[$_pk] = is_array($primaryValues) ? $primaryValues[$_pk] : $primaryValues;
        }
        return $where;
    }

    private function _buildData():array
    {
        return $this->attributes;
    }

    public static function getTableName()
    {
        return static::$tablename;
    }

    public function lastError()
    {
        return $this->errorMessage;
    }

    public function save()
    {
        $useUpdate = false;
        $insert_id = 0;
        $old = false;
        try {
            $checkExists = static::$checkExists;
            if (isset($this->attributes[static::$pk])) {
                $checkExists = true;
            }
            if ($checkExists) {
                $where = static::buildPrimaryWhere($this->attributes);
                $where["LIMIT"] = 1;
                $old = static::list($where);
                $useUpdate = $old->count() > 0;
            } else {
                $useUpdate = false;
            }
        } catch (Exception $e) {
        }
        if ($this->recordUpdateAt && ! isset($this->attributes["update_at"])) {
            $this->attributes["update_at"] = date("Y-m-d H:i:s", time());
        }
        if ($useUpdate) {
            if ($this->recordCreateAt) {
                $this->attributes["create_at"] = $old->first()->create_at;
            }
            $res = static::getOrm()->update(static::getTableName(), $this->_buildData(), $where);
        } else {
            if ($this->recordCreateAt && ! isset($this->attributes["create_at"])) {
                $this->attributes["create_at"] = date("Y-m-d H:i:s", time());
            }
            $res = static::getOrm()->insert(static::getTableName(), $this->_buildData());
            $insert_id = static::getOrm()->id();
        }
        $this->errorMessage = $res->errorInfo()[1] > 0 ? $res->errorInfo()[2] : null;
        if ($this->errorMessage === null && $insert_id > 0) {
            $this->attributes[static::$pk] = $insert_id;
        }
        return $this->errorMessage === null;
    }
    public static function deleteByWhere($where, $is_and = true)
    {
        $do_where = [];
        if ($is_and) {
            $do_where["AND"] = $where;
        } else {
            $do_where["OR"] = $where;
        }
        $res = static::getOrm()->delete(self::getTableName(), $do_where);
        return $res->rowCount();
    }

    public static function updateByWhere($update, $where)
    {
        $res = static::getOrm()->update(self::getTableName(), $update, $where);
        return $res->rowCount();
    }

    public function delete()
    {
        $where = static::buildPrimaryWhere($this->attributes);
        if (count($where) > 1) {
            $where = [
                "AND" => $where
            ];
        }
        $res = static::getOrm()->delete(static::getTableName(), $where);
        $this->errorMessage = $res->errorInfo()[1] > 0 ? $res->errorInfo()[2] : null;
        return $this->errorMessage === null;
    }

    public function __isset($name): bool
    {
        return $this->offsetExists($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public static function count($where = [], $join = [], $column = [])
    {
        return $join ? static::getOrm()->count(static::getTableName(), $join, $column, $where) : static::getOrm()->count(static::getTableName(), $where);
    }

    public static function get($primary)
    {
        $where = static::buildPrimaryWhere($primary);
        $where["LIMIT"] = 1;
        $list = static::list($where);
        return $list->count() > 0 ? $list->first() : false;
    }

    public static function list()
    {
        $args = func_get_args();
        $limit = self::LIMIT;
        $offset = 0;
        $where = [];
        $tablename = static::getTableName();
        $find = "*";
        $join = false;
        if (count($args) > 0) {
            if (is_numeric($args[0])) {
                $limit = $args[0];
                if (isset($args[1]) && $args[1] >= 0) {
                    $offset = $args[1];
                }
                $where["LIMIT"] = [
                    $offset,
                    $limit
                ];
            } elseif (1 === count($args)) {
                $where = $args[0];
            } elseif (2 === count($args)) {
                $find = $args[0];
                $where = $args[1];
            } elseif (3 === count($args)) {
                $join = $args[0];
                $find = $args[1];
                $where = $args[2];
            }
        }
        $trans = function ($value) {
            return new static($value);
        };
        $data = $join ? static::getOrm()->select("{$tablename}({$tablename})", $join, $find, $where) : static::getOrm()->select(static::getTableName(), $find, $where);
        if (empty($data) && static::getOrm()->error()[1] > 0) {
            throw new Exception("SQL查询错误 : " . static::getOrm()->error()[2]);
        }
        if ("*" == $find && ! $join && ! empty($data)) {
            $data = array_map($trans, $data);
        }
        return new Collection($data);
    }

    /**
     *
     * @param
     *            ArrayAccess | array $attr
     * @return self
     */
    public function mergeAttr($attr)
    {
        foreach ($attr as $k => $v) {
            $this->offsetSet($k, $v);
        }
        return $this;
    }

    public static function listFirst()
    {
        $result = call_user_func_array("self::list", func_get_args());
        if ($result instanceof Collection && $result->count() > 0) {
            return $result->first();
        } else {
            return null;
        }
    }

    /**
     * 通过属性返回实例，若不存在则通过给定的属性做初始化
     *
     * @param array $attr
     * @return self
     */
    public static function existsOrCreate(array $attr)
    {
        $model = self::listFirst($attr);
        if (! $model instanceof BaseModel) {
            $model = new static($attr);
        }
        return $model;
    }
}
