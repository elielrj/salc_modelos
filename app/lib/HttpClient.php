<?php

class HttpClient
{
    /**
     * @param string $fullUrl
     * @param bool $useCache
     * @param int $cacheTtlSec
     * @param int $maxRetries
     * @param float $baseBackoff
     * @return array<string,mixed>
     */
    public static function getJsonWithRetry($fullUrl, $useCache = true, $cacheTtlSec = 600, $maxRetries = 6, $baseBackoff = 1.0)
    {
        $fullUrl = (string) $fullUrl;
        $useCache = (bool) $useCache;
        $cacheTtlSec = (int) $cacheTtlSec;
        $maxRetries = (int) $maxRetries;
        $baseBackoff = (float) $baseBackoff;

        $cacheKey = sys_get_temp_dir() . '/arp_cache_' . sha1($fullUrl) . '.json';
        if ($useCache && is_file($cacheKey) && (time() - filemtime($cacheKey) <= $cacheTtlSec)) {
            $json = json_decode((string) file_get_contents($cacheKey), true);
            if (is_array($json)) return $json;
        }

        $attempt = 0;
        $respHeaders = [];
        while (true) {
            $attempt++;
            $ch = curl_init($fullUrl);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT        => 45,
                CURLOPT_HTTPHEADER     => array(
                    'Accept: */*',
                    'User-Agent: salc-modelos/1.0 (+retry/backoff)'
                ),
                CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$respHeaders) {
                    $len = strlen($header);
                    $parts = explode(':', $header, 2);
                    if (count($parts) === 2) {
                        $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                    }
                    return $len;
                }
            ));
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($body !== false && $code >= 200 && $code < 300) {
                $json = json_decode($body, true);
                if (!is_array($json)) return array('__error' => 'JSON inválido', '__debug' => $body);
                if ($useCache) {
                    $tmp = $cacheKey . '.tmp';
                    @file_put_contents($tmp, $body, LOCK_EX);
                    @rename($tmp, $cacheKey);
                }
                return $json;
            }

            if ($code === 429 || ($code >= 500 && $code <= 599)) {
                $waitSec = 0;
                if (isset($respHeaders['retry-after'])) {
                    $ra = $respHeaders['retry-after'];
                    $waitSec = is_numeric($ra) ? (int)$ra : 0;
                }
                if (!$waitSec && $body && preg_match('/Try again in\s+(\d+)\s+seconds/i', (string) $body, $m)) {
                    $waitSec = (int) $m[1];
                }
                if (!$waitSec) {
                    $jitter = mt_rand(100, 600) / 1000.0;
                    $waitSec = (int) round(pow(2, min($attempt - 1, 5)) * $baseBackoff + $jitter);
                }
                if ($attempt <= $maxRetries) {
                    usleep(max(1, (int) ($waitSec * 1000000)));
                    continue;
                }
            }

            return array('__error' => "HTTP $code", '__debug' => ($body ? $body : $err));
        }
    }
}
