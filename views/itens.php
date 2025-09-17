<h3>Itens vigentes — UASG <span id="uasgCurrent"><?= htmlspecialchars((string)UASG, ENT_QUOTES, 'UTF-8') ?></span></h3>
<div class="uasg-picker">
  <label for="selUasg" class="small">UG:</label>
  <select id="selUasg" class="form-select form-select-sm" style="max-width:220px;"></select>
  <span class="small text-muted" id="uasgHelp"></span>
  </div>
<div class="meta" id="itensLoading">Clique na guia para carregar os itens da API.</div>

<!-- Filtros -->
<div id="compraFilter" class="compra-filter" aria-label="Filtro por Compra/Ano"></div>
<div id="searchFilter" class="search-filter">
  <input type="text" id="txtSearch" placeholder="Filtrar por descrição ou fornecedor" aria-label="Filtrar por descrição ou fornecedor" />
  <button type="button" id="btnClearSearch" class="btn-mini" title="Limpar busca">×</button>
  <span id="filterCount" class="small" style="margin-left:8px;"></span>
  </div>

  <table id="tPrincipal">
  <thead>
    <tr>
      <th class="checkcol noclick"><input type="checkbox" id="selAll" title="Selecionar todos"></th>
      <th class="rownum" data-sort="rownum">Ord <span class="sort-ind"></span></th>
      <th class="center" data-sort="compraitem">Pregão <span class="sort-ind"></span></th>
      <th class="center" data-sort="texto">Descrição <span class="sort-ind"></span></th>
      <th class="center" data-sort="texto">Fornecedor <span class="sort-ind"></span></th>
      <th class="right" data-sort="numero">Qtd <span class="sort-ind"></span></th>
      <th class="right" data-sort="moeda">Valor Unit. <span class="sort-ind"></span></th>
      <th class="right" data-sort="moeda">Valor Total <span class="sort-ind"></span></th>
      <th data-sort="vigencia">Vigência <span class="sort-ind"></span></th>
      <th data-sort="texto">Tipo <span class="sort-ind"></span></th>
      <th data-sort="texto">SICAF <span class="sort-ind"></span></th>
      <th class="right" data-sort="numero">NE <span class="sort-ind"></span></th>
      <th class="noclick">TCU</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<h4 class="mt-4 mb-2">Itens selecionados</h4>
<div class="mb-2">
  <button class="btn-mini" id="btnCopy">Copiar tabela</button>
  <button class="btn-mini" id="btnPrint">Imprimir PDF</button>
  <span class="small" id="copyMsg" style="margin-left:8px;"></span>
  </div>
<table id="tSel">
  <thead>
    <tr>
      <th>Pregão</th>
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
      <td colspan="7" class="right">TOTAL</td>
      <td class="right" id="sumTotal">R$ 0,00</td>
      <td></td>
    </tr>
  </tfoot>
  </table>
