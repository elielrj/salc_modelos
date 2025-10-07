<?php

class Ata
{
    /**
     * @param array<string,mixed> $a
     * @return array<string,mixed>
     */
    public static function fromApi(array $a)
    {
        return [
            'numeroAtaRegistroPreco' => isset($a['numeroAtaRegistroPreco']) ? $a['numeroAtaRegistroPreco'] : (isset($a['numeroAta']) ? $a['numeroAta'] : '—'),
            'codigoUnidadeGerenciadora' => isset($a['codigoUnidadeGerenciadora']) ? $a['codigoUnidadeGerenciadora'] : '',
            'nomeUnidadeGerenciadora' => isset($a['nomeUnidadeGerenciadora']) ? $a['nomeUnidadeGerenciadora'] : '',
            'nomeOrgao' => isset($a['nomeOrgao']) ? $a['nomeOrgao'] : '',
            'codigoModalidadeCompra' => isset($a['codigoModalidadeCompra']) ? $a['codigoModalidadeCompra'] : '',
            'nomeModalidadeCompra' => isset($a['nomeModalidadeCompra']) ? $a['nomeModalidadeCompra'] : '',
            'dataAssinatura' => isset($a['dataAssinatura']) ? $a['dataAssinatura'] : '',
            'dataVigenciaInicial' => isset($a['dataVigenciaInicial']) ? $a['dataVigenciaInicial'] : '',
            'dataVigenciaFinal' => isset($a['dataVigenciaFinal']) ? $a['dataVigenciaFinal'] : '',
            'valorTotal' => (float)(isset($a['valorTotal']) ? $a['valorTotal'] : 0),
            'numeroCompra' => isset($a['numeroCompra']) ? $a['numeroCompra'] : '',
            'anoCompra' => isset($a['anoCompra']) ? $a['anoCompra'] : '',
            'linkAtaPNCP' => isset($a['linkAtaPNCP']) ? $a['linkAtaPNCP'] : (isset($a['linkAta']) ? $a['linkAta'] : ''),
            'linkCompraPNCP' => isset($a['linkCompraPNCP']) ? $a['linkCompraPNCP'] : (isset($a['linkCompra']) ? $a['linkCompra'] : ''),
            'idCompra' => isset($a['idCompra']) ? $a['idCompra'] : (isset($a['id']) ? $a['id'] : ''),
            'numeroControlePncpCompra' => isset($a['numeroControlePncpCompra']) ? $a['numeroControlePncpCompra'] : '',
            'numeroControlePncpAta' => isset($a['numeroControlePncpAta']) ? $a['numeroControlePncpAta'] : '',
        ];
    }
}
