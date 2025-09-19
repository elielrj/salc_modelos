<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/HttpClient.php';

class ARPAtaClient
{
    public const BASE_URLS = [
        'https://dadosabertos.compras.gov.br/modulo-arp/1_consultarARP',
        'https://dadosabertos.compras.gov.br/modulo-arp/3_consultarARPAta',
        'https://dadosabertos.compras.gov.br/modulo-arp/3_consultarARPAtaRegistroPreco',
        'https://dadosabertos.compras.gov.br/modulo-arp/consultarARPAta',
        'https://dadosabertos.compras.gov.br/modulo-arp/consultarARPAtaRegistroPreco',
    ];

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public static function listPage(array $params, int $pagina, bool $cache = true, int $ttl = 600, int $maxRetries = 6, float $baseBackoff = 1.0): array
    {
        $qs = http_build_query($params + ['pagina' => $pagina], '', '&', PHP_QUERY_RFC3986);
        $last = null;
        foreach (self::BASE_URLS as $u) {
            $full = $u . '?' . $qs;
            $resp = HttpClient::getJsonWithRetry($full, $cache, $ttl, $maxRetries, $baseBackoff);
            if (!isset($resp['__error'])) return $resp;
            $last = $resp;
        }
        return $last ?: ['__error' => 'Sem resposta válida'];
    }
}
