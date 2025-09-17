<h3>Atas de Registro de Preços — UASG <?= htmlspecialchars((string)UASG, ENT_QUOTES, 'UTF-8') ?></h3>


<div class="table-responsive">
  <table id="tAtas" class="table table-sm align-middle">
    <thead>
      <tr>
        <th class="checkcol noclick"><input type="checkbox" id="atasSelAll" title="Selecionar todos"></th>
        <th class="rownum" data-sort="rownum">Ord <span class="sort-ind"></span></th>
        <th data-sort="texto">Pregão <span class="sort-ind"></span></th>
        <th data-sort="texto">Nr Ata <span class="sort-ind"></span></th>
        <th data-sort="texto">Modalidade <span class="sort-ind"></span></th>
        <th data-sort="data-br">Assinatura <span class="sort-ind"></span></th>
        <th data-sort="vigencia-br">Vigência <span class="sort-ind"></span></th>
        <th class="right" data-sort="moeda-br">Valor Total <span class="sort-ind"></span></th>
        <th class="noclick">Links</th>
      </tr>
    </thead>
    <tbody>
      <!-- Dica: para listar via servidor, crie uma view dinâmica que consuma api/atas.php e monte linhas; presente arquivo só habilita ordenação. -->
    </tbody>
  </table>
</div>

<h4 class="mt-4 mb-2">Atas selecionadas</h4>
<div class="mb-2">
  <button class="btn-mini" id="atasBtnCopy">Copiar tabela</button>
  <button class="btn-mini" id="atasBtnPrint">Imprimir PDF</button>
  <span class="small" id="atasCopyMsg" style="margin-left:8px;"></span>
</div>
<table id="tAtasSel" class="table table-sm">
  <thead>
    <tr>
      <th>Pregão</th>
      <th>Nº Ata</th>
      <th>Modalidade</th>
      <th>Vigência</th>
      <th class="right">Valor Total</th>
      <th style="width:40px;"></th>
    </tr>
  </thead>
  <tbody></tbody>
  <tfoot>
    <tr>
      <td colspan="4" class="right">TOTAL</td>
      <td class="right" id="atasSumTotal">R$ 0,00</td>
      <td></td>
    </tr>
  </tfoot>
</table>
