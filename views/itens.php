<h3>Itens vigentes — UASG <?= htmlspecialchars((string)UASG, ENT_QUOTES, 'UTF-8') ?></h3>
<div class="meta" id="itensLoading">Clique na guia para carregar os itens da API.</div>

<table id="tPrincipal">
  <thead>
    <tr>
      <th class="checkcol noclick"><input type="checkbox" id="selAll" title="Selecionar todos"></th>
      <th class="rownum" data-sort="rownum"># <span class="sort-ind"></span></th>
      <th class="center" data-sort="compraitem">Pregão <span class="sort-ind"></span></th>
      <th data-sort="texto">Descrição <span class="sort-ind"></span></th>
      <th data-sort="texto">Fornecedor <span class="sort-ind"></span></th>
      <th class="center" data-sort="numero">Qtd <span class="sort-ind"></span></th>
      <th class="center" data-sort="moeda">Valor Unit. <span class="sort-ind"></span></th>
      <th class="center" data-sort="moeda">Valor Total <span class="sort-ind"></span></th>
      <th class="center" data-sort="vigencia">Vigência <span class="sort-ind"></span></th>
      <th class="center" data-sort="texto">Tipo <span class="sort-ind"></span></th>
      <th class="center" data-sort="texto">SICAF <span class="sort-ind"></span></th>
      <th class="center" data-sort="numero">Qtd. Empenhada <span class="sort-ind"></span></th>
      <th class="center noclick">TCU</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<h4 class="mt-4 mb-2">Itens selecionados</h4>
<div class="mb-2">
  <button class="btn-mini" id="btnCopy">Copiar tabela</button>
  <span class="small" id="copyMsg" style="margin-left:8px;"></span>
  </div>
<table id="tSel">
  <thead>
    <tr>
      <th>Compra/Ano</th>
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
