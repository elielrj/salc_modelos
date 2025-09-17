<?php
require_once __DIR__ . '/../lib/HttpClient.php';

class ContratosListaClient
{
    const BASE_URL = 'https://dadosabertos.compras.gov.br/modulo-contratos/1_consultarContratos';

    public static function listPage(array $params, $pagina, $cache = true, $ttl = 600, $maxRetries = 6, $baseBackoff = 1.0)
    {
        $qs = http_build_query($params + ['pagina' => $pagina], '', '&', PHP_QUERY_RFC3986);
        $full = self::BASE_URL . '?' . $qs;
        return HttpClient::getJsonWithRetry($full, $cache, $ttl, $maxRetries, $baseBackoff);
    }
}
