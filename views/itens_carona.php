<h3>Itens vigentes — Carona</h3>
<div class="d-flex flex-wrap align-items-center gap-3 mb-2">
  <div>
    <label for="caronaTipo" class="small">Tipo do item:</label>
    <select id="caronaTipo" class="form-select form-select-sm" style="max-width:220px;">
      <option value="">Todos</option>
      <option value="material">Material</option>
      <option value="servico">Serviço</option>
    </select>
  </div>
  <div class="small text-muted" id="caronaCounter">Carregando catálogo (exceto 160517)...</div>
  <button type="button" id="caronaBtnClear" class="btn btn-outline-secondary btn-sm">Recarregar</button>
</div>

<div class="meta" id="caronaLoading">Aguardando início do carregamento...</div>

<!-- Filtros -->
<!-- Filtro por Pregão removido a pedido: caronaCompraFilter -->
<div id="caronaSearchFilter" class="search-filter">
  <input type="text" id="caronaTxtSearch" placeholder="Filtrar por descrição" aria-label="Filtrar por descrição" />
  <button type="button" id="caronaBtnClearSearch" class="btn-mini" title="Limpar busca">×</button>
  <span id="caronaFilterCount" class="small" style="margin-left:8px;"></span>
</div>

<table id="caronaPrincipal">
  <thead>
    <tr>
      <th class="checkcol noclick"><input type="checkbox" id="caronaSelAll" title="Selecionar todos"></th>
      <th class="rownum" data-sort="rownum">Ord <span class="sort-ind"></span></th>
      <th class="center" data-sort="compraitem">Pregão <span class="sort-ind"></span></th>
      <th class="center" data-sort="texto">Descrição <span class="sort-ind"></span></th>
      <th class="right" data-sort="numero">Qtd <span class="sort-ind"></span></th>
      <th class="right" data-sort="moeda">Valor Unit. <span class="sort-ind"></span></th>
    </tr>
  </thead>
  <tbody></tbody>
  </table>

<h4 class="mt-4 mb-2">Itens selecionados</h4>
<div class="mb-2">
  <button class="btn-mini" id="caronaBtnCopy">Copiar tabela</button>
  <button class="btn-mini" id="caronaBtnPrint">Imprimir PDF</button>
  <span class="small" id="caronaCopyMsg" style="margin-left:8px;"></span>
  </div>
<table id="caronaSel">
  <thead>
    <tr>
      <th>Pregão</th>
      <th>Item</th>
      <th class="left">Descrição</th>
      <th class="left">Fornecedor</th>
      <th class="left">UG</th>
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
      <td class="right" id="caronaSumTotal">R$ 0,00</td>
      <td></td>
    </tr>
  </tfoot>
</table>

