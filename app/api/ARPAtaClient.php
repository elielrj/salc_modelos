<?php
require_once __DIR__ . '/../lib/HttpClient.php';

class ARPAtaClient
{
    const BASE_URLS = [
        'https://dadosabertos.compras.gov.br/modulo-arp/1_consultarARP',
        'https://dadosabertos.compras.gov.br/modulo-arp/3_consultarARPAta',
        'https://dadosabertos.compras.gov.br/modulo-arp/3_consultarARPAtaRegistroPreco',
        'https://dadosabertos.compras.gov.br/modulo-arp/consultarARPAta',
        'https://dadosabertos.compras.gov.br/modulo-arp/consultarARPAtaRegistroPreco',
    ];

    public static function listPage(array $params, $pagina, $cache = true, $ttl = 600, $maxRetries = 6, $baseBackoff = 1.0)
    {
        $qs = http_build_query($params + ['pagina' => $pagina], '', '&', PHP_QUERY_RFC3986);
        $last = null;
        foreach (self::BASE_URLS as $u) {
            $full = $u . '?' . $qs;
            $resp = HttpClient::getJsonWithRetry($full, $cache, $ttl, $maxRetries, $baseBackoff);
            if (!isset($resp['__error'])) return $resp;
            $last = $resp;
        }
        return $last ? $last : ['__error' => 'Sem resposta válida'];
    }
}
