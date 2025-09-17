<?php
require_once __DIR__ . '/../lib/HttpClient.php';

class ComprasnetContratosClient
{
    const BASE_URL = 'https://contratos.comprasnet.gov.br/transparencia/contratos';

    public static function listPage(array $params, $pagina, $cache = true, $ttl = 600, $maxRetries = 4, $baseBackoff = 1.0)
    {
        // Heuristic params; the endpoint is public transparency and may accept these
        $merged = $params + [
            'pagina' => $pagina,
        ];
        $qs = http_build_query($merged, '', '&', PHP_QUERY_RFC3986);
        $full = self::BASE_URL . '?' . $qs;
        $json = HttpClient::getJsonWithRetry($full, $cache, $ttl, $maxRetries, $baseBackoff);
        return $json;
    }
}
