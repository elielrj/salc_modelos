(function(){
  function ensureCssEscape(){
    if(!window.CSS) window.CSS={};
    if(!CSS.escape) CSS.escape = (v) => String(v).replace(/["\\]/g, '\\$&');
  }

  function initCaronaTab(){
    const pane = document.getElementById('itens-carona');
    if(!pane || pane.dataset.ready==='1') return;
    pane.dataset.ready='1';
    ensureCssEscape();

    const q = (sel) => pane.querySelector(sel);
    const qAll = (sel) => [...pane.querySelectorAll(sel)];

    const table = q('#caronaPrincipal');
    if(!table) return;
    const tBodyMain = table.tBodies[0];
    const thead = table.tHead;
    const tBodySel = q('#caronaSel tbody');
    const sumEl = q('#caronaSumTotal');
    const copyBtn = q('#caronaBtnCopy');
    const copyMsg = q('#caronaCopyMsg');
    const printBtn = q('#caronaBtnPrint');
    const selAll = q('#caronaSelAll');
    const loadingMsg = q('#caronaLoading');
    const counterEl = q('#caronaCounter');
    const txtSearch = q('#caronaTxtSearch');
    const btnClearSearch = q('#caronaBtnClearSearch');
    const filterCount = q('#caronaFilterCount');
    const compraSelect = q('#caronaSelPregao');
    const ugSelect = q('#caronaSelUgs');
    const tipoSelect = q('#caronaTipo');
    const clearBtn = q('#caronaBtnClear');
    if(!tBodyMain || !tBodySel) return;

    let allItensCache = [];
    let totalCount = 0;
    let textQuery = '';
    let compraSelecionada = '';
    let tipoAtual = '';
    const ugCache = new Map(); // chave: ug|tipo
    const selectedUGs = new Map(); // ug -> array de itens
    let ugsDisponiveis = [];

    const fmtDate = (s) => {
      if(!s) return '—';
      const d = new Date(s);
      if(Number.isNaN(d.getTime())) return s;
      const dd = String(d.getUTCDate()).padStart(2,'0');
      const mm = String(d.getUTCMonth()+1).padStart(2,'0');
      const yy = d.getUTCFullYear();
      return `${dd}/${mm}/${yy}`;
    };

    function renumberRows(){ qAll('#caronaPrincipal tbody tr').forEach((tr,i)=>{ const cell=tr.querySelector('.rownum'); if(cell) cell.textContent=String(i+1); }); }
    function recalcTotal(){ const tot=[...tBodySel.querySelectorAll('tr')].reduce((s,tr)=> s + (Number(tr.dataset.tot||0)), 0); if(sumEl) sumEl.textContent = fmtBRL(tot); }

    function buildCompraOptions(itens){
      if(!compraSelect) return;
      const counts = new Map();
      const meta = new Map();
      itens.forEach((it)=>{
        const key = [it.numeroCompra, it.anoCompra].filter(Boolean).join('/');
        if(!key) return;
        counts.set(key, (counts.get(key)||0) + 1);
        const ugCod = String(it.codigoUnidadeGerenciadora || '').trim();
        if(ugCod){
          meta.set(key, { ugCod, sigla: it.siglaUnidadeGerenciadora || '', descricao: it.nomeUnidadeGerenciadora || '' });
        }
      });
      const keys = Array.from(counts.keys()).sort((a,b)=>{
        const [ca,aa] = a.split('/').map(n=>parseInt(n||'0',10));
        const [cb,ab] = b.split('/').map(n=>parseInt(n||'0',10));
        if(aa!==ab) return aa-b;
        return ca-b;
      });
      const atual = compraSelecionada;
      let html = '<option value="">Todos</option>';
      keys.forEach((k)=>{
        const c = counts.get(k)||0;
        const sel = atual === k ? ' selected' : '';
        const ugInfo = meta.get(k);
        let ugLabel = '';
        if(ugInfo){
          const desc = ugInfo.sigla || ugInfo.descricao || '';
          ugLabel = desc ? `${ugInfo.ugCod} — ${desc}` : ugInfo.ugCod;
        }
        const label = ugLabel ? `${k} — ${ugLabel} (${c})` : `${k} (${c})`;
        html += `<option value="${k}"${sel}>${label}</option>`;
      });
      compraSelect.innerHTML = html;
      if(compraSelecionada && !counts.has(compraSelecionada)) {
        compraSelecionada = '';
      }
      compraSelect.value = compraSelecionada || '';
    }

    function setupSearchFilter(){
      if(!txtSearch) return;
      if(!txtSearch.dataset.bound){
        txtSearch.addEventListener('input', ()=>{ textQuery = txtSearch.value || ''; applyAllFilters(); });
        txtSearch.dataset.bound='1';
      }
    if(btnClearSearch && !btnClearSearch.dataset.bound){
      btnClearSearch.addEventListener('click', ()=>{ txtSearch.value=''; txtSearch.focus(); textQuery=''; applyAllFilters(); });
      btnClearSearch.dataset.bound='1';
    }
    }

    if(compraSelect && !compraSelect.dataset.bound){
      compraSelect.addEventListener('change', ()=>{
        compraSelecionada = compraSelect.value || '';
        applyAllFilters();
      });
      compraSelect.dataset.bound='1';
    }

    if(tipoSelect && !tipoSelect.dataset.bound){
      tipoSelect.addEventListener('change', async ()=>{
        tipoAtual = tipoSelect.value || '';
        if(selectedUGs.size===0){
          updateStatus();
          return;
        }
        await reloadSelectedUgs(true);
      });
      tipoSelect.dataset.bound='1';
      tipoAtual = tipoSelect.value || '';
    }

    function updateStatus(message){
      if(counterEl){
        const ugCount = selectedUGs.size;
        const itemCount = allItensCache.length;
        counterEl.textContent = ugCount ? `Selecionadas ${ugCount} UASG(s) — ${itemCount} item(ns)` : 'Nenhuma UASG selecionada.';
      }
      if(message !== undefined){
        if(loadingMsg) loadingMsg.textContent = message;
      } else if(loadingMsg) {
        if(selectedUGs.size===0) loadingMsg.textContent = 'Selecione uma UASG para iniciar a consulta.';
        else loadingMsg.textContent = `Itens carregados (${allItensCache.length}).`;
      }
    }

    function rebuildAll(){
      const combined = [];
      selectedUGs.forEach(arr => combined.push(...arr));
      allItensCache = combined;
      totalCount = combined.length;
      buildCompraOptions(combined);
      applyAllFilters();
      updateStatus();
    }

    async function fetchItemsForUG(ugCod){
      const key = `${ugCod}|${tipoAtual||'todos'}`;
      if(ugCache.has(key)) return ugCache.get(key);
      const params = new URLSearchParams({ uasg: String(ugCod) });
      if(tipoAtual) params.set('tipo', tipoAtual);
      const resp = await fetch(`api/itens.php?${params.toString()}`);
      if(!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const json = await resp.json();
      const itens = Array.isArray(json.itens) ? json.itens : [];
      ugCache.set(key, itens);
      return itens;
    }

    async function reloadSelectedUgs(showMessage){
      if(showMessage) updateStatus('Recarregando itens das UASGs selecionadas...');
      const entries = [...selectedUGs.keys()];
      selectedUGs.clear();
      for(const ugCod of entries){
        try {
          const itens = await fetchItemsForUG(ugCod);
          selectedUGs.set(ugCod, itens);
          rebuildAll();
        } catch(err){
          console.error('Erro ao recarregar UASG', ugCod, err);
        }
      }
      updateStatus();
    }

    async function handleUgSelectionChange(){
      if(!ugSelect) return;
      const selectedOptions = [...ugSelect.selectedOptions].map(opt => opt.value).filter(Boolean);
      const currentSet = new Set(selectedUGs.keys());
      // Remover UGs desmarcadas
      currentSet.forEach(ugCod => {
        if(!selectedOptions.includes(ugCod)) selectedUGs.delete(ugCod);
      });
      for(const ugCod of selectedOptions){
        if(selectedUGs.has(ugCod)) continue;
        try {
        updateStatus(`Carregando itens da UASG ${ugCod}...`);
        const itens = await fetchItemsForUG(ugCod);
        selectedUGs.set(ugCod, itens);
        rebuildAll();
      } catch(err){
          console.error('Erro ao buscar itens da UASG', ugCod, err);
        }
      }
      rebuildAll();
    }

    function renderUgsSelect(){
      if(!ugSelect) return;
      let options = '';
      ugsDisponiveis.forEach(ug => {
        const desc = ug.sigla || ug.descricao || '';
        const label = desc ? `${ug.cod} — ${desc}` : ug.cod;
        options += `<option value="${ug.cod}">${label}</option>`;
      });
      ugSelect.innerHTML = options;
      if(!ugSelect.dataset.bound){
        ugSelect.addEventListener('change', handleUgSelectionChange);
        ugSelect.dataset.bound='1';
      }
    }

    function clearSelection(){
      selectedUGs.clear();
      allItensCache = [];
      totalCount = 0;
      compraSelecionada='';
      if(ugSelect){
        [...ugSelect.options].forEach(opt => opt.selected = false);
      }
      if(compraSelect){
        compraSelect.innerHTML = '<option value="">Todos</option>';
        compraSelect.value='';
      }
      renderItens([]);
      buildCompraOptions([]);
      if(filterCount) filterCount.textContent = 'Mostrando 0 de 0 itens';
      if(tBodySel){ tBodySel.innerHTML=''; if(sumEl) sumEl.textContent='R$ 0,00'; }
      selAll && (selAll.checked=false);
      updateStatus('Selecione uma ou mais UASGs para iniciar a consulta.');
    }

    clearBtn?.addEventListener('click', clearSelection);

    function keyForSelection(data){
      const parts = (data.compra||'').split('/');
      return {
        ano: parseInt(parts[1]||'0',10),
        compra: parseInt(parts[0]||'0',10),
        item: parseInt(String(data.item||'').replace(/\D+/g,''),10)||0,
        uasg: parseInt(String(data.ug||'').replace(/\D+/g,''),10)||0
      };
    }
    function keyFromRow(tr){
      const tds = tr.querySelectorAll('td');
      const compra = (tds[0]?.innerText.trim()||'').split('/');
      const item = parseInt((tds[1]?.innerText||'').replace(/\D+/g,''),10)||0;
      const ug = parseInt((tr.dataset.uasg||'').replace(/\D+/g,''),10)||0;
      return {
        ano: parseInt(compra[1]||'0',10),
        compra: parseInt(compra[0]||'0',10),
        item,
        uasg: ug
      };
    }
    function compareKeys(a,b){ if(a.ano!==b.ano) return a.ano-b.ano; if(a.compra!==b.compra) return a.compra-b.compra; if(a.item!==b.item) return a.item-b.item; if(a.uasg!==b.uasg) return a.uasg-b.uasg; return 0; }

    function addSelectedRow(data){
      if(tBodySel.querySelector(`tr[data-rowid="${CSS.escape(data.rowid)}"]`)) return;
      const tr=document.createElement('tr');
      tr.dataset.rowid=data.rowid;
      tr.dataset.uasg=data.ug||'';
      const max=Number(data.qtd)||0;
      const vu=Number(data.vu)||0;
      const init=Math.min(1, Math.max(0,max));
      tr.innerHTML=`
        <td>${data.compra||'—'}</td>
        <td class="nowrap">${data.item||'—'}</td>
        <td class="left">${data.desc||''}</td>
        <td class="left">${data.forn||''}<div class="small">CNPJ: ${maskCNPJ(data.ni)||'—'}</div></td>
        <td class="left">${data.uglabel||'—'}</td>
        <td class="right">${max.toLocaleString('pt-BR')}</td>
        <td class="right">${fmtBRL(vu)}</td>
        <td class="right"><input type="number" min="1" max="${max}" step="1" value="${init}" class="qtdBuy"></td>
        <td class="right totCell"></td>
        <td><button class="btn-mini btnDel" title="Remover">×</button></td>`;
      const input=tr.querySelector('.qtdBuy');
      const totCell=tr.querySelector('.totCell');
      function update(){ let q=parseInt(input.value,10); if(!Number.isInteger(q)||q<1) q=1; if(q>max) q=max; input.value=q; const tot=q*vu; tr.dataset.tot=String(tot); totCell.textContent=fmtBRL(tot); recalcTotal(); }
      input.addEventListener('input', update);
      input.addEventListener('blur', update);
      tr.querySelector('.btnDel').addEventListener('click', ()=>{
        const mainCb=pane.querySelector(`#caronaPrincipal tbody input.sel[data-rowid="${CSS.escape(data.rowid)}"]`);
        if(mainCb){ mainCb.checked=false; mainCb.closest('tr')?.classList?.remove('selrow'); }
        tr.remove();
        recalcTotal();
      });
      const newKey=keyForSelection(data);
      const rows=[...tBodySel.querySelectorAll('tr')];
      let inserted=false;
      for(const r of rows){
        const keyRow=keyFromRow(r);
        if(compareKeys(newKey,keyRow)<0){ tBodySel.insertBefore(tr,r); inserted=true; break; }
      }
      if(!inserted) tBodySel.appendChild(tr);
      update();
    }

    function removeSelectedRow(rowid){ const tr=tBodySel.querySelector(`tr[data-rowid="${CSS.escape(rowid)}"]`); if(tr){ tr.remove(); recalcTotal(); } }

    function renderItens(list){
      tBodyMain.innerHTML='';
      const frag=document.createDocumentFragment();
      list.forEach((it,idx)=>{
        const compra=[it.numeroCompra,it.anoCompra].filter(Boolean).join('/');
        const itemNum=it.numeroItem||'';
        const ni=it.niFornecedor||'';
        const ugCode=String(it.codigoUnidadeGerenciadora||'').trim();
        const ugSigla=(it.siglaUnidadeGerenciadora||'').trim();
        const ugNome=(it.nomeUnidadeGerenciadora||'').trim();
        const ugLabel=[ugCode||null, ugSigla||ugNome||null].filter(Boolean).join(' — ') || ugNome || '—';
        const [sicafText,sicafClass]=sicafLabel(it.situacaoSicaf);
        const rowKey=`${ugCode||'UG'}|${compra}|${itemNum}`;
        const tr=document.createElement('tr');
        tr.dataset.vigini=it.dataVigenciaInicial||'';
        tr.dataset.ano=it.anoCompra||'';
        tr.dataset.compra=it.numeroCompra||'';
        tr.dataset.itemnum=itemNum||'';
        tr.dataset.uasg=ugCode||'';
        tr.innerHTML=`
          <td class="checkcol"><input type="checkbox" class="sel" data-rowid="${CSS.escape(rowKey)}" data-compra="${compra}" data-item="${itemNum}" data-desc="${it.descricaoItem||''}" data-forn="${it.nomeRazaoSocialFornecedor||''}" data-ni="${ni}" data-qtd="${it.quantidadeHomologadaItem||0}" data-vu="${it.valorUnitario||0}" data-ug="${ugCode}" data-uglabel="${ugLabel.replace(/"/g,'&quot;')}"></td>
          <td class="rownum">${idx+1}</td>
          <td class="left nowrap"><div><strong>${compra||'—'}</strong></div><div class="small">Item: ${itemNum||'—'}</div></td>
          <td class="left">${(it.descricaoItem||'—')}${it.codigoItem? `<div class=\"small\">Código Item: ${it.codigoItem}</div>`:''}</td>
          <td class="left"><div>${it.nomeRazaoSocialFornecedor||'—'}</div><div class="small">CNPJ: ${ni? maskCNPJ(ni) : '—'}</div></td>
          <td class="center">${(it.quantidadeHomologadaItem||0).toLocaleString('pt-BR')}</td>
          <td class="center">${fmtBRL(it.valorUnitario||0)}</td>
          <td class="center">${fmtBRL(it.valorTotal||0)}</td>
          <td class="center">${fmtDate(it.dataVigenciaInicial)} à ${fmtDate(it.dataVigenciaFinal)}</td>
          <td class="center nowrap">${it.tipoItem||'—'}</td>
          <td class="center ${sicafClass}"><i class="bi bi-hand-thumbs-up" title="${sicafText}" aria-label="${sicafText}"></i></td>
          <td class="center">${(it.quantidadeEmpenhada||0).toLocaleString('pt-BR')}</td>
          <td class="left">${ugLabel}</td>
          <td class="center nowrap">${ni? `<a href="api/certidao.php?cnpj=${ni}" target="_blank" rel="noopener" title="Certidão TCU"><i class="bi bi-book"></i></a>` : '—'}</td>`;
        frag.appendChild(tr);
      });
      tBodyMain.appendChild(frag);
      renumberRows();
    }

    function applyAllFilters(){
      let arr = allItensCache;
      if(compraSelecionada){
        arr = arr.filter((it)=> [it.numeroCompra, it.anoCompra].filter(Boolean).join('/') === compraSelecionada);
      }
      if(textQuery){
        const q = textQuery.trim().toLowerCase();
        if(q){
          arr = arr.filter((it)=>
            String(it.descricaoItem||'').toLowerCase().includes(q)
            || String(it.nomeRazaoSocialFornecedor||'').toLowerCase().includes(q)
            || String(it.siglaUnidadeGerenciadora||'').toLowerCase().includes(q)
            || String(it.nomeUnidadeGerenciadora||'').toLowerCase().includes(q)
          );
        }
      }
      renderItens(arr);
      if(filterCount) filterCount.textContent = `Mostrando ${arr.length} de ${totalCount} itens`;
    }

    function resetState(){
      compraSelecionada='';
      textQuery='';
      tipoAtual = tipoSelect ? (tipoSelect.value || '') : '';
      allItensCache = [];
      totalCount = 0;
      selectedUGs.clear();
      if(txtSearch) txtSearch.value='';
      if(tBodySel){ tBodySel.innerHTML=''; if(sumEl) sumEl.textContent='R$ 0,00'; }
      if(compraSelect){
        compraSelect.innerHTML = '<option value="">Todos</option>';
        compraSelect.value='';
      }
      if(ugSelect){
        ugSelect.innerHTML = '';
      }
      if(counterEl) counterEl.textContent='Nenhuma UASG selecionada.';
      if(loadingMsg) loadingMsg.textContent='Selecione uma ou mais UASGs para iniciar a consulta.';
    }

    function parseNum(txt){ return Number(String(txt||'').replace(/\./g,'').replace(',','.').replace(/[^\d.-]/g,''))||0; }
    function parseDateDataset(tr){ const d=new Date(tr.dataset.vigini||''); return Number.isNaN(d.getTime())?0:d.getTime(); }
    function keyForSort(tr,type,colIndex){
      switch(type){
        case 'rownum': return [...tBodyMain.children].indexOf(tr);
        case 'numero':
        case 'moeda': return parseNum(tr.children[colIndex].innerText.trim());
        case 'vigencia': return parseDateDataset(tr);
        case 'compraitem': return [
          parseInt(tr.dataset.ano||'0',10),
          parseInt(tr.dataset.compra||'0',10),
          parseInt(tr.dataset.itemnum||'0',10),
          parseInt(tr.dataset.uasg||'0',10)
        ];
        default: return tr.children[colIndex].innerText.trim().toLowerCase();
      }
    }
    function cmpSort(a,b){ if(Array.isArray(a)&&Array.isArray(b)){ for(let i=0;i<Math.max(a.length,b.length);i++){ const da=a[i]??0, db=b[i]??0; if(da<db) return -1; if(da>db) return 1; } return 0; } if(a<b) return -1; if(a>b) return 1; return 0; }
    function clearSortIcons(){ thead?.querySelectorAll('.sort-ind').forEach((sp)=>{ sp.textContent=''; }); }
    function setSortIcon(th,dir){ const ind=th.querySelector('.sort-ind'); if(ind) ind.textContent = (dir===1?'▲':'▼'); }
    let lastSortTh=null; let lastDir=1;
    thead?.addEventListener('click', (e)=>{
      const th = e.target.closest('th');
      if(!th || th.classList.contains('noclick')) return;
      const sortType = th.dataset.sort;
      if(!sortType) return;
      const idx=[...th.parentNode.children].indexOf(th);
      const rows=[...tBodyMain.querySelectorAll('tr')];
      const keyed=rows.map((tr)=>({tr, key:keyForSort(tr,sortType,idx)}));
      const dir=(lastSortTh===th && lastDir===1)? -1 : 1;
      keyed.sort((A,B)=>cmpSort(A.key,B.key)*dir);
      const frag=document.createDocumentFragment(); keyed.forEach((k)=>frag.appendChild(k.tr));
      tBodyMain.appendChild(frag);
      lastSortTh=th; lastDir=dir; clearSortIcons(); setSortIcon(th,dir); renumberRows();
    });

    table.addEventListener('change', (e)=>{
      const cb = e.target && e.target.matches('tbody input.sel') ? e.target : null;
      if(!cb) return;
      const data = {
        rowid: cb.dataset.rowid,
        compra: cb.dataset.compra,
        item: cb.dataset.item,
        desc: cb.dataset.desc,
        forn: cb.dataset.forn,
        ni: cb.dataset.ni,
        qtd: cb.dataset.qtd,
        vu: cb.dataset.vu,
        ug: cb.dataset.ug,
        uglabel: cb.dataset.uglabel
      };
      if(cb.checked){
        cb.closest('tr')?.classList?.add('selrow');
        addSelectedRow(data);
      } else {
        cb.closest('tr')?.classList?.remove('selrow');
        removeSelectedRow(data.rowid);
      }
    });

    selAll?.addEventListener('change', ()=>{
      tBodyMain.querySelectorAll('input.sel').forEach((cb)=>{
        if(cb.checked !== selAll.checked){
          cb.checked = selAll.checked;
          cb.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    });

    copyBtn?.addEventListener('click', async ()=>{
      const src = q('#caronaSel'); if(!src) return;
      const hdr=[...src.querySelectorAll('thead th')].map((th)=>th.innerText.trim());
      const rows=[...src.querySelectorAll('tbody tr')];
      const totalTxt=sumEl?.innerText?.trim() || 'R$ 0,00';
      const td=(txt,tag='td',align='left')=>`<${tag} style="border:1px solid #ccc; padding:6px; text-align:${align}; vertical-align:top;">${txt}</${tag}>`;
      let html = `<meta charset="utf-8"><table style="border-collapse:collapse; border:1px solid #ccc; font-family:Arial,Helvetica,sans-serif; font-size:13px;"><thead><tr>${hdr.map((h)=>td(h,'th')).join('')}</tr></thead><tbody>`;
      rows.forEach((tr)=>{
        const tds=tr.querySelectorAll('td');
        const compra=tds[0]?.innerText.trim()||'';
        const item=tds[1]?.innerText.trim()||'';
        const desc=tds[2]?.innerText.trim()||'';
        const fornNode=tds[3];
        const forn=fornNode?.childNodes?.[0]?.textContent?.trim()||'';
        const cnpjRaw=(fornNode?.querySelector('.small')?.innerText||'').replace(/^(?:NI|CNPJ)\s*:\s*/i,'').trim();
        const ug=tds[4]?.innerText.trim()||'';
        const qtdDisp=tds[5]?.innerText.trim()||'';
        const vUnit=tds[6]?.innerText.trim()||'';
        const qtyInput=tds[7]?.querySelector('input');
        const qty=qtyInput? qtyInput.value : '';
        const tot=tds[8]?.innerText.trim()||'';
        const fornHtml = `${forn}<div style='color:#666;font-size:12px'>CNPJ: ${cnpjRaw||'—'}</div>`;
        html += `<tr>${td(compra)}${td(item)}${td(desc)}${td(fornHtml)}${td(ug)}${td(qtdDisp,'td','right')}${td(vUnit,'td','right')}${td(qty,'td','right')}${td(tot,'td','right')}${td('')}</tr>`;
      });
      html += `</tbody><tfoot><tr>${td('TOTAL','th','right')}<td colspan="7"></td>${td(totalTxt,'th','right')}<td></td></tr></tfoot></table>`;
      try {
        if(navigator.clipboard && window.ClipboardItem){
          const data={'text/html': new Blob([html],{type:'text/html'}), 'text/plain': new Blob([html],{type:'text/plain'})};
          await navigator.clipboard.write([new ClipboardItem(data)]);
          copyMsg && (copyMsg.textContent='Tabela copiada para a área de transferência.');
        } else {
          const div=document.createElement('div');
          div.contentEditable='true';
          div.style.position='fixed';
          div.style.left='-99999px';
          div.innerHTML=html;
          document.body.appendChild(div);
          const range=document.createRange();
          range.selectNodeContents(div);
          const sel=window.getSelection();
          sel.removeAllRanges();
          sel.addRange(range);
          document.execCommand('copy');
          document.body.removeChild(div);
          copyMsg && (copyMsg.textContent='Tabela copiada para a área de transferência.');
        }
      } catch(err) {
        console.error(err);
        copyMsg && (copyMsg.textContent='Não foi possível copiar a tabela.');
      }
      setTimeout(()=>{ if(copyMsg) copyMsg.textContent=''; }, 3500);
    });

    printBtn?.addEventListener('click', ()=>{
      const src=q('#caronaSel'); if(!src) return;
      const clone=src.cloneNode(true);
      clone.querySelectorAll('td:last-child, th:last-child').forEach((n)=>n.remove());
      const win=window.open('', '_blank'); if(!win) return;
      win.document.write(`<html><head><meta charset='utf-8'><title>Itens selecionados — Carona</title><style>table{border-collapse:collapse;width:100%;font:13px Arial} th,td{border:1px solid #ccc;padding:6px;} th{text-align:left;background:#f7f7f7}</style></head><body></body></html>`);
      win.document.body.appendChild(clone);
      win.focus();
      win.print();
      setTimeout(()=>win.close(), 500);
    });

    async function loadItens(){
      resetState();
      updateStatus('Carregando lista de UASGs...');
      try {
        const ugResp = await fetch('api/ugs.php');
        const ugJson = await ugResp.json();
        const ugArr = Array.isArray(ugJson.ugs) ? ugJson.ugs : [];
        ugsDisponiveis = ugArr
          .map(u => ({
            cod: String(u.codug || '').trim(),
            sigla: String(u.sigla || '').trim(),
            descricao: String(u.cidade_estado || u.descricao || '').trim(),
          }))
          .filter(u => u.cod && u.cod !== '160517');
        ugsDisponiveis.sort((a,b)=> (parseInt(a.cod,10)||0) - (parseInt(b.cod,10)||0));
        renderUgsSelect();
        setupSearchFilter();
        updateStatus();
      } catch(err){
        console.error(err);
        updateStatus('Falha ao carregar a lista de UASGs.');
      }
    }

    loadItens();
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const trigger = document.querySelector('a[href="#itens-carona"]');
    trigger?.addEventListener('shown.bs.tab', initCaronaTab);
    const pane = document.getElementById('itens-carona');
    if(pane && pane.classList.contains('show')) initCaronaTab();
  });
})();
