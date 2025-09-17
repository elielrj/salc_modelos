<h3>Listar Contratos</h3>

<?php
  $tz = new DateTimeZone(APP_TIMEZONE);
  $today = new DateTime('now', $tz);
  $year = (int) $today->format('Y');
  $vigMinStr = (new DateTime("$year-01-01", $tz))->format('d/m/Y');
  $vigMaxStr = (new DateTime("$year-12-31", $tz))->format('d/m/Y');
?>
<div class="small text-muted">
  UASG: <strong>160517</strong>. Período: <?= htmlspecialchars($vigMinStr, ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars($vigMaxStr, ENT_QUOTES, 'UTF-8') ?>. Esta lista exibe contratos (não itens) do PNCP/Compras.
  
</div>
<div class="small" id="clMsg" style="margin-top:6px;"></div>

<!-- Filtros -->
<div id="clCompraFilter" class="compra-filter" aria-label="Filtro por Compra"></div>
<div id="clContratoFilter" class="compra-filter" aria-label="Filtro por Contrato"></div>
<div id="clSearchFilter" class="search-filter">
  <input type="text" id="clTxtSearch" placeholder="Filtrar por descrição (objeto) ou fornecedor" aria-label="Filtrar por descrição (objeto) ou fornecedor" />
  <button type="button" id="clBtnClearSearch" class="btn-mini" title="Limpar busca">×</button>
  <span id="clFilterCount" class="small" style="margin-left:8px;"></span>
</div>

<div class="small" style="margin-top:6px;">
  <label><input type="checkbox" id="clToggleObj"> Mostrar objetos abaixo da tabela</label>
  <div class="small text-muted">O campo Objeto foi movido para fora da tabela e pode ser ocultado.</div>
  
</div>

<div class="table-responsive mt-2">
  <table class="table table-sm align-middle" id="clTable">
    <thead>
      <tr>
        <th class="checkcol noclick"><input type="checkbox" id="clSelAll" title="Selecionar todos"></th>
        <th class="rownum" data-sort="rownum">Ord</th>
        <th data-sort="text">Nº Contrato</th>
        <th data-sort="text">Compra</th>
        <th data-sort="text">Modalidade</th>
        <th data-sort="text">Categoria</th>
        <th data-sort="text">Fornecedor</th>
        <th data-sort="date">Vigência</th>
        <th class="right" data-sort="numero">Dias p/ vencer</th>
        <th class="right" data-sort="money">Valor Global</th>
        <th data-sort="text">Processo</th>
        <th data-sort="date">Incluído em</th>
        <th data-sort="date">Excluído em</th>
        <th data-sort="text">Excluído?</th>
        <th class="right" data-sort="money">Total</th>
        <th data-sort="text">TCU</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <div class="small text-muted" id="clFoot"></div>
  
</div>

<div id="clObjetosWrap" class="mt-2"></div>

<!-- Tabela selecionados -->
<h4 class="mt-4 mb-2">Contratos selecionados</h4>
<div class="mb-2">
  <button class="btn-mini" id="clBtnCopy">Copiar tabela</button>
  <button class="btn-mini" id="clBtnPrint">Imprimir PDF</button>
  <span class="small" id="clCopyMsg" style="margin-left:8px;"></span>
</div>
<table id="clSel" class="table table-sm">
  <thead>
    <tr>
      <th>Nº Contrato</th>
      <th>Compra</th>
      <th class="left">Fornecedor</th>
      <th class="left">Objeto</th>
      <th>Vigência</th>
      <th class="right">Valor Global</th>
      <th style="width:40px;"></th>
    </tr>
  </thead>
  <tbody></tbody>
  <tfoot>
    <tr>
      <td colspan="5" class="right">TOTAL</td>
      <td class="right" id="clSumTotal">R$ 0,00</td>
      <td></td>
    </tr>
  </tfoot>
</table>
