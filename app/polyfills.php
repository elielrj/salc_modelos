<?php
// Polyfills for PHP 7.4 compatibility
// Only define if the native functions are not available

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        $haystack = (string) $haystack;
        $needle   = (string) $needle;
        if ($needle === '') return true;
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        $haystack = (string) $haystack;
        $needle   = (string) $needle;
        if ($needle === '') return true;
        $nlen = strlen($needle);
        if ($nlen > strlen($haystack)) return false;
        return substr_compare($haystack, $needle, -$nlen) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        $haystack = (string) $haystack;
        $needle   = (string) $needle;
        if ($needle === '') return true;
        return strpos($haystack, $needle) !== false;
    }
}

