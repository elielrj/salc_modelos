<?php
declare(strict_types=1);

class Ata
{
    /**
     * @param array<string,mixed> $a
     * @return array<string,mixed>
     */
    public static function fromApi(array $a): array
    {
        return [
            'numeroAtaRegistroPreco' => $a['numeroAtaRegistroPreco'] ?? ($a['numeroAta'] ?? '—'),
            'codigoUnidadeGerenciadora' => $a['codigoUnidadeGerenciadora'] ?? '',
            'nomeUnidadeGerenciadora' => $a['nomeUnidadeGerenciadora'] ?? '',
            'nomeOrgao' => $a['nomeOrgao'] ?? '',
            'codigoModalidadeCompra' => $a['codigoModalidadeCompra'] ?? '',
            'nomeModalidadeCompra' => $a['nomeModalidadeCompra'] ?? '',
            'dataAssinatura' => $a['dataAssinatura'] ?? '',
            'dataVigenciaInicial' => $a['dataVigenciaInicial'] ?? '',
            'dataVigenciaFinal' => $a['dataVigenciaFinal'] ?? '',
            'valorTotal' => (float)($a['valorTotal'] ?? 0),
            'numeroCompra' => $a['numeroCompra'] ?? '',
            'anoCompra' => $a['anoCompra'] ?? '',
            'linkAtaPNCP' => $a['linkAtaPNCP'] ?? ($a['linkAta'] ?? ''),
            'linkCompraPNCP' => $a['linkCompraPNCP'] ?? ($a['linkCompra'] ?? ''),
            'idCompra' => $a['idCompra'] ?? ($a['id'] ?? ''),
            'numeroControlePncpCompra' => $a['numeroControlePncpCompra'] ?? '',
            'numeroControlePncpAta' => $a['numeroControlePncpAta'] ?? '',
        ];
    }
}
