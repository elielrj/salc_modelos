<?php
declare(strict_types=1);

class Env
{
    private static array $vars = [];

    public static function load(string $path): void
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

    public static function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, self::$vars) ? self::$vars[$key] : $default;
    }
}
