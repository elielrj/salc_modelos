<?php
require_once __DIR__ . '/../lib/HttpClient.php';

class ContratosClient
{
    const BASE_URL = 'https://dadosabertos.compras.gov.br/modulo-contratos/2_consultarContratosItem';

    public static function listPage(array $params, $pagina, $cache = true, $ttl = 600, $maxRetries = 6, $baseBackoff = 1.0)
    {
        $qs = http_build_query($params + ['pagina' => $pagina], '', '&', PHP_QUERY_RFC3986);
        $full = self::BASE_URL . '?' . $qs;
        return HttpClient::getJsonWithRetry($full, $cache, $ttl, $maxRetries, $baseBackoff);
    }
}
