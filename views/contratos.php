<h3>Contratos — Itens</h3>

<div class="small text-muted">
  Listagem automática para a UASG <strong><?= htmlspecialchars((string)UASG, ENT_QUOTES, 'UTF-8') ?></strong>,
  período: últimos 365 dias até hoje.
</div>
<div class="small" id="contratosMsg" style="margin-top:6px;"></div>

<!-- Filtros -->
<div id="cCompraFilter" class="compra-filter" aria-label="Filtro por Compra/Ano"></div>
<div id="cContratoFilter" class="compra-filter" aria-label="Filtro por Contrato"></div>
<div id="cSearchFilter" class="search-filter">
  <input type="text" id="cTxtSearch" placeholder="Filtrar por descrição ou fornecedor" aria-label="Filtrar por descrição ou fornecedor" />
  <button type="button" id="cBtnClearSearch" class="btn-mini" title="Limpar busca">×</button>
  <span id="cFilterCount" class="small" style="margin-left:8px;"></span>
</div>

<!-- Tabela principal -->
<div class="table-responsive mt-2">
  <table id="cPrincipal" class="table table-sm align-middle">
    <thead>
      <tr>
        <th class="checkcol noclick"><input type="checkbox" id="cSelAll" title="Selecionar todos"></th>
        <th class="rownum">Ord</th>
        <th>Contrato</th>
        <th>Modalidade</th>
        <th>Compra</th>
        <th>Item</th>
        <th>Descrição</th>
        <th>Fornecedor</th>
        <th class="right">Qtd</th>
        <th class="right">Valor Unit.</th>
        <th class="right">Valor Total</th>
        <th>Vigência</th>
        <th>Processo</th>
        <th>TCU</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <div class="small text-muted" id="contratosFooter"></div>
</div>

<!-- Tabela selecionados -->
<h4 class="mt-4 mb-2">Itens selecionados</h4>
<div class="mb-2">
  <button class="btn-mini" id="cBtnCopy">Copiar tabela</button>
  <span class="small" id="cCopyMsg" style="margin-left:8px;"></span>
</div>
<table id="cSel" class="table table-sm">
  <thead>
    <tr>
      <th>Compra</th>
      <th>Contrato</th>
      <th>Item</th>
      <th class="left">Descrição</th>
      <th class="left">Fornecedor</th>
      <th class="right">Qtd. disponível</th>
      <th class="right">Valor unitário</th>
      <th class="right" style="width:150px;">Qtde a comprar</th>
      <th class="right">Total da linha</th>
      <th style="width:40px;"></th>
    </tr>
  </thead>
  <tbody></tbody>
  <tfoot>
    <tr>
      <td colspan="8" class="right">TOTAL</td>
      <td class="right" id="cSumTotal">R$ 0,00</td>
      <td></td>
    </tr>
  </tfoot>
</table>
