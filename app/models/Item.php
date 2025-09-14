<?php
class Item
{
    public static function fromApi(array $it): array
    {
        $ni = isset($it['niFornecedor']) ? preg_replace('/\D+/', '', (string)$it['niFornecedor']) : '';
        return [
            'numeroCompra' => $it['numeroCompra'] ?? '',
            'anoCompra' => $it['anoCompra'] ?? '',
            'numeroItem' => $it['numeroItem'] ?? '',
            'descricaoItem' => $it['descricaoItem'] ?? '',
            'codigoItem' => $it['codigoItem'] ?? null,
            'nomeRazaoSocialFornecedor' => $it['nomeRazaoSocialFornecedor'] ?? '',
            'niFornecedor' => $ni,
            'quantidadeHomologadaItem' => (float)($it['quantidadeHomologadaItem'] ?? 0),
            'valorUnitario' => (float)($it['valorUnitario'] ?? 0),
            'valorTotal' => (float)($it['valorTotal'] ?? 0),
            'dataVigenciaInicial' => $it['dataVigenciaInicial'] ?? '',
            'dataVigenciaFinal' => $it['dataVigenciaFinal'] ?? '',
            'tipoItem' => $it['tipoItem'] ?? '',
            'situacaoSicaf' => $it['situacaoSicaf'] ?? null,
            'quantidadeEmpenhada' => (float)($it['quantidadeEmpenhada'] ?? 0),
        ];
    }
}

