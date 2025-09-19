<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/HttpClient.php';

class ComprasnetContratosClient
{
    public const BASE_URL = 'https://contratos.comprasnet.gov.br/transparencia/contratos';

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public static function listPage(array $params, int $pagina, bool $cache = true, int $ttl = 600, int $maxRetries = 4, float $baseBackoff = 1.0): array
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
