<h3>Atas de Registro de Preços — UASG <?= htmlspecialchars((string)UASG, ENT_QUOTES, 'UTF-8') ?></h3>


<div class="table-responsive">
  <table id="tAtas" class="table table-sm align-middle">
    <thead>
      <tr>
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
