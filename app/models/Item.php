<?php

class Item
{
    /**
     * @param array<string,mixed> $it
     * @return array<string,mixed>
     */
    public static function fromApi(array $it)
    {
        $ni = isset($it['niFornecedor']) ? preg_replace('/\D+/', '', (string)$it['niFornecedor']) : '';
        return [
            'numeroCompra' => isset($it['numeroCompra']) ? $it['numeroCompra'] : '',
            'anoCompra' => isset($it['anoCompra']) ? $it['anoCompra'] : '',
            'numeroItem' => isset($it['numeroItem']) ? $it['numeroItem'] : '',
            'descricaoItem' => isset($it['descricaoItem']) ? $it['descricaoItem'] : '',
            'codigoItem' => isset($it['codigoItem']) ? $it['codigoItem'] : null,
            'idCompra' => isset($it['idCompra']) ? $it['idCompra'] : (isset($it['idCompraPNCP']) ? $it['idCompraPNCP'] : null),
            'nomeRazaoSocialFornecedor' => isset($it['nomeRazaoSocialFornecedor']) ? $it['nomeRazaoSocialFornecedor'] : '',
            'niFornecedor' => $ni,
            'quantidadeHomologadaItem' => (float)(isset($it['quantidadeHomologadaItem']) ? $it['quantidadeHomologadaItem'] : 0),
            'valorUnitario' => (float)(isset($it['valorUnitario']) ? $it['valorUnitario'] : 0),
            'valorTotal' => (float)(isset($it['valorTotal']) ? $it['valorTotal'] : 0),
            'dataVigenciaInicial' => isset($it['dataVigenciaInicial']) ? $it['dataVigenciaInicial'] : '',
            'dataVigenciaFinal' => isset($it['dataVigenciaFinal']) ? $it['dataVigenciaFinal'] : '',
            'tipoItem' => isset($it['tipoItem']) ? $it['tipoItem'] : '',
            'situacaoSicaf' => isset($it['situacaoSicaf']) ? $it['situacaoSicaf'] : null,
            'quantidadeEmpenhada' => (float)(isset($it['quantidadeEmpenhada']) ? $it['quantidadeEmpenhada'] : 0),
            'codigoUnidadeGerenciadora' => isset($it['codigoUnidadeGerenciadora']) ? $it['codigoUnidadeGerenciadora'] : (isset($it['codigoUnidadeGestora']) ? $it['codigoUnidadeGestora'] : null),
            'nomeUnidadeGerenciadora' => isset($it['nomeUnidadeGerenciadora']) ? $it['nomeUnidadeGerenciadora'] : (isset($it['nomeUnidadeGestora']) ? $it['nomeUnidadeGestora'] : null),
            'siglaUnidadeGerenciadora' => isset($it['siglaUnidadeGerenciadora']) ? $it['siglaUnidadeGerenciadora'] : (isset($it['siglaUnidadeGestora']) ? $it['siglaUnidadeGestora'] : null),
        ];
    }
}
