<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/HttpClient.php';

class ContratosClient
{
    public const BASE_URL = 'https://dadosabertos.compras.gov.br/modulo-contratos/2_consultarContratosItem';

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public static function listPage(array $params, int $pagina, bool $cache = true, int $ttl = 600, int $maxRetries = 6, float $baseBackoff = 1.0): array
    {
        $qs = http_build_query($params + ['pagina' => $pagina], '', '&', PHP_QUERY_RFC3986);
        $full = self::BASE_URL . '?' . $qs;
        return HttpClient::getJsonWithRetry($full, $cache, $ttl, $maxRetries, $baseBackoff);
    }
}
