<?php
namespace SimpleModel;

class Registry
{
    private static $list = [];

    public static function set($key, $value, $ttl = 0)
    {
        self::$list[$key] = $value;
    }

    public static function get($key)
    {
        if (isset(self::$list[$key])) {
            return self::$list[$key];
        }
        return null;
    }

    public static function del($key)
    {
        if (isset(self::$list[$key])) {
            unset(self::$list[$key]);
        }
    }

    public static function exists($key)
    {
        return isset(self::$list[$key]);
    }
}
