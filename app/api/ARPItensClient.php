<?php
require_once __DIR__ . '/../lib/HttpClient.php';

class ARPItensClient
{
    const BASE_URL = 'https://dadosabertos.compras.gov.br/modulo-arp/2_consultarARPItem';

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public static function listPage(array $params, $pagina, $cache = true, $ttl = 600, $maxRetries = 6, $baseBackoff = 1.0)
    {
        $pagina = (int) $pagina;
        $cache = (bool) $cache;
        $ttl = (int) $ttl;
        $maxRetries = (int) $maxRetries;
        $baseBackoff = (float) $baseBackoff;

        $qs = http_build_query($params + array('pagina' => $pagina), '', '&', PHP_QUERY_RFC3986);
        $full = self::BASE_URL . '?' . $qs;
        return HttpClient::getJsonWithRetry($full, $cache, $ttl, $maxRetries, $baseBackoff);
    }
}
