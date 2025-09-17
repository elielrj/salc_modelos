<?php
class Env
{
    private static $vars = [];

    public static function load($path)
    {
        if (!is_file($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $pos = strpos($line, '=');
            if ($pos === false) continue;
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            $val = trim($val, "\"' ");
            self::$vars[$key] = $val;
        }
    }

    public static function get($key, $default = null)
    {
        return array_key_exists($key, self::$vars) ? self::$vars[$key] : $default;
    }
}
