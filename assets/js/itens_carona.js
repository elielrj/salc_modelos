// Itens vigentes — Carona (todas as UGs, exceto 160517)
(function(){
  function ensureCssEscape(){ if(!window.CSS) window.CSS={}; if(!CSS.escape) CSS.escape=(v)=>String(v).replace(/["\\]/g,'\\$&'); }
  const fmtBRL = (n) => Number(n||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});

  function init(){
    const pane=document.getElementById('itens-carona'); if(!pane || pane.dataset.ready==='1') return; pane.dataset.ready='1';
    ensureCssEscape();

    const q=(s)=>pane.querySelector(s); const qAll=(s)=>Array.from(pane.querySelectorAll(s));
    const table=q('#caronaPrincipal'); const tBody=table?.tBodies?.[0]; const thead=table?.tHead;
    const tSel=q('#caronaSel tbody'); const sumEl=q('#caronaSumTotal');
    const copyBtn=q('#caronaBtnCopy'); const copyMsg=q('#caronaCopyMsg'); const printBtn=q('#caronaBtnPrint');
    const selAll=q('#caronaSelAll');
    const loadingMsg=q('#caronaLoading'); const counterEl=q('#caronaCounter');
    const txtSearch=q('#caronaTxtSearch'); const btnClearSearch=q('#caronaBtnClearSearch'); const filterCount=q('#caronaFilterCount');
    const compraSelect=q('#caronaSelPregao'); const tipoSelect=q('#caronaTipo'); const clearBtn=q('#caronaBtnClear');
    if(!tBody || !tSel) return;

    let allItens=[]; let totalCount=0; let textQuery=''; let compraSelecionada='';
    let tipoAtual = tipoSelect ? (tipoSelect.value||'') : '';
    let ugs=[]; const ugCache=new Map(); let progress={total:0,loaded:0};

    function updateStatus(msg){
      if(counterEl){ counterEl.textContent = progress.total? `Carregadas ${progress.loaded}/${progress.total} UGs — ${allItens.length} itens` : `${allItens.length} itens`; }
      if(loadingMsg){ loadingMsg.textContent = (msg!==undefined? msg : `Itens carregados (${allItens.length}).`); }
    }

    function buildCompraOptions(itens){
      if(!compraSelect) return; const counts=new Map();
      itens.forEach(it=>{ const k=[it.numeroCompra,it.anoCompra].filter(Boolean).join('/'); if(!k) return; counts.set(k,(counts.get(k)||0)+1); });
      const keys=Array.from(counts.keys()).sort((a,b)=>{ const [ca,aa]=a.split('/').map(n=>parseInt(n||'0',10)); const [cb,ab]=b.split('/').map(n=>parseInt(n||'0',10)); if(aa!==ab) return aa-b; return ca-b; });
      let html='<option value="">Todos</option>'; keys.forEach(k=>{ html+=`<option value="${k}">${k} (${counts.get(k)})</option>`; }); compraSelect.innerHTML=html; compraSelect.value=compraSelecionada||'';
    }

    function applyFilters(){
      let arr = allItens;
      if(compraSelecionada){ arr = arr.filter(it=> [it.numeroCompra,it.anoCompra].filter(Boolean).join('/')===compraSelecionada); }
      if(textQuery){ const q=textQuery.trim().toLowerCase(); if(q){ arr = arr.filter(it=> String(it.descricaoItem||'').toLowerCase().includes(q)); } }
      render(arr); if(filterCount) filterCount.textContent = `Mostrando ${arr.length} de ${totalCount} itens`;
    }

    function render(list){
      tBody.innerHTML=''; const frag=document.createDocumentFragment();
      list.forEach((it,idx)=>{
        const compra=[it.numeroCompra,it.anoCompra].filter(Boolean).join('/');
        const itemNum=it.numeroItem||''; const ni=it.niFornecedor||'';
        const ugCode=String(it.codigoUnidadeGerenciadora||'').trim();
        const ugSigla=(it.siglaUnidadeGerenciadora||'').trim(); const ugNome=(it.nomeUnidadeGerenciadora||'').trim();
        const ugLabel=[ugCode||null, ugSigla||ugNome||null].filter(Boolean).join(' - ') || ugNome || '-';
        const rowKey=`${ugCode||'UG'}|${compra}|${itemNum}`;
        const tr=document.createElement('tr'); tr.dataset.ano=it.anoCompra||''; tr.dataset.compra=it.numeroCompra||''; tr.dataset.itemnum=itemNum||''; tr.dataset.uasg=ugCode||'';
        tr.innerHTML = `
          <td class="checkcol"><input type="checkbox" class="sel" data-rowid="${CSS.escape(rowKey)}" data-compra="${compra}" data-item="${itemNum}" data-desc="${it.descricaoItem||''}" data-forn="${it.nomeRazaoSocialFornecedor||''}" data-ni="${ni}" data-qtd="${it.quantidadeHomologadaItem||0}" data-vu="${it.valorUnitario||0}" data-ug="${ugCode}" data-uglabel="${ugLabel.replace(/\"/g,'&quot;')}"></td>
          <td class="rownum">${idx+1}</td>
          <td class="left nowrap"><div><strong>${compra||'-'}</strong></div><div class="small">Item: ${itemNum||'-'}</div></td>
          <td class="left">${(it.descricaoItem||'-')}${it.codigoItem? `<div class=\"small\">Código Item: ${it.codigoItem}</div>`:''}${it.idCompra? `<div class=\"small\">PNCP: ${it.idCompra}</div>`:''}</td>
          <td class="center">${(it.quantidadeHomologadaItem||0).toLocaleString('pt-BR')}</td>
          <td class="center">${fmtBRL(it.valorUnitario||0)}</td>`;
        frag.appendChild(tr);
      });
      tBody.appendChild(frag); renumber();
    }

    // ordenação
    function keyForSort(tr,type,colIndex){
      const parseNum=(txt)=> Number(String(txt||'').replace(/\./g,'').replace(',','.').replace(/[^\d.-]/g,''))||0;
      switch(type){
        case 'rownum': return [...tBody.children].indexOf(tr);
        case 'numero':
        case 'moeda': return parseNum(tr.children[colIndex].innerText.trim());
        case 'compraitem': return [parseInt(tr.dataset.ano||'0',10), parseInt(tr.dataset.compra||'0',10), parseInt(tr.dataset.itemnum||'0',10), parseInt(tr.dataset.uasg||'0',10)];
        default: return tr.children[colIndex].innerText.trim().toLowerCase();
      }
    }
    const cmp=(a,b)=>{ if(Array.isArray(a)&&Array.isArray(b)){ for(let i=0;i<Math.max(a.length,b.length);i++){ const da=a[i]??0, db=b[i]??0; if(da<db) return -1; if(da>db) return 1; } return 0; } if(a<b) return -1; if(a>b) return 1; return 0; };
    function clearIcons(){ thead?.querySelectorAll('.sort-ind').forEach(sp=>sp.textContent=''); }
    function setIcon(th,dir){ const ind=th.querySelector('.sort-ind'); if(ind) ind.textContent=(dir===1?'\u001e':'\u001f'); }
    let lastTh=null, lastDir=1;
    const renumber=()=> qAll('#caronaPrincipal tbody tr').forEach((tr,i)=>{ const c=tr.querySelector('.rownum'); if(c) c.textContent=String(i+1); });
    thead?.addEventListener('click',(e)=>{ const th=e.target.closest('th'); if(!th||th.classList.contains('noclick'))return; const type=th.dataset.sort; if(!type) return; const idx=[...th.parentNode.children].indexOf(th); const rows=[...tBody.querySelectorAll('tr')]; const keyed=rows.map(tr=>({tr,key:keyForSort(tr,type,idx)})); const dir=(lastTh===th&&lastDir===1)?-1:1; keyed.sort((A,B)=>cmp(A.key,B.key)*dir); const frag=document.createDocumentFragment(); keyed.forEach(k=>frag.appendChild(k.tr)); tBody.appendChild(frag); lastTh=th; lastDir=dir; clearIcons(); setIcon(th,dir); renumber(); });

    // seleção principal -> tabela selecionados
    table.addEventListener('change',(e)=>{ const cb=e.target && e.target.matches('tbody input.sel')? e.target : null; if(!cb) return; const data={ rowid:cb.dataset.rowid, compra:cb.dataset.compra, item:cb.dataset.item, desc:cb.dataset.desc, forn:cb.dataset.forn, ni:cb.dataset.ni, qtd:cb.dataset.qtd, vu:cb.dataset.vu, ug:cb.dataset.ug, uglabel:cb.dataset.uglabel }; if(cb.checked){ cb.closest('tr')?.classList?.add('selrow'); addSelectedRow(data); } else { cb.closest('tr')?.classList?.remove('selrow'); removeSelectedRow(data.rowid); } });
    selAll?.addEventListener('change',()=>{ tBody.querySelectorAll('input.sel').forEach(cb=>{ if(cb.checked!==selAll.checked){ cb.checked=selAll.checked; cb.dispatchEvent(new Event('change',{bubbles:true})); } }); });

    function keyForSelection(data){ const parts=(data.compra||'').split('/'); return { ano:parseInt(parts[1]||'0',10), compra:parseInt(parts[0]||'0',10), item:parseInt(String(data.item||'').replace(/\D+/g,''),10)||0, uasg:parseInt(String(data.ug||'').replace(/\D+/g,''),10)||0 }; }
    function keyFromRow(tr){ const tds=tr.querySelectorAll('td'); const compra=(tds[0]?.innerText.trim()||'').split('/'); const item=parseInt((tds[1]?.innerText||'').replace(/\D+/g,''),10)||0; const ug=parseInt((tr.dataset.uasg||'').replace(/\D+/g,''),10)||0; return { ano:parseInt(compra[1]||'0',10), compra:parseInt(compra[0]||'0',10), item, uasg:ug }; }
    function cmpKeys(a,b){ if(a.ano!==b.ano) return a.ano-b.ano; if(a.compra!==b.compra) return a.compra-b.compra; if(a.item!==b.item) return a.item-b.item; if(a.uasg!==b.uasg) return a.uasg-b.uasg; return 0; }

    function addSelectedRow(data){ if(tSel.querySelector(`tr[data-rowid="${CSS.escape(data.rowid)}"]`)) return; const tr=document.createElement('tr'); tr.dataset.rowid=data.rowid; tr.dataset.uasg=data.ug||''; const max=Number(data.qtd)||0; const vu=Number(data.vu)||0; const init=Math.min(1,Math.max(0,max)); tr.innerHTML=`
        <td>${data.compra||'-'}</td>
        <td class="nowrap">${data.item||'-'}</td>
        <td class="left">${data.desc||''}</td>
        <td class="left">${data.forn||''}<div class="small">CNPJ: ${data.ni||'-'}</div></td>
        <td class="left">${data.uglabel||'-'}</td>
        <td class="right">${max.toLocaleString('pt-BR')}</td>
        <td class="right">${fmtBRL(vu)}</td>
        <td class="right"><input type="number" min="1" max="${max}" step="1" value="${init}" class="qtdBuy"></td>
        <td class="right totCell"></td>
        <td><button class="btn-mini btnDel" title="Remover">✕</button></td>`;
      const input=tr.querySelector('.qtdBuy'); const totCell=tr.querySelector('.totCell');
      function update(){ let q=parseInt(input.value,10); if(!Number.isInteger(q)||q<1) q=1; if(q>max) q=max; input.value=q; const tot=q*vu; tr.dataset.tot=String(tot); totCell.textContent=fmtBRL(tot); recalc(); }
      function recalc(){ const tot=[...tSel.querySelectorAll('tr')].reduce((s,tr)=> s + (Number(tr.dataset.tot||0)), 0); if(sumEl) sumEl.textContent = fmtBRL(tot); }
      input.addEventListener('input', update); input.addEventListener('blur', update); update();
      tr.querySelector('.btnDel').addEventListener('click', ()=>{ const mainCb=pane.querySelector(`#caronaPrincipal tbody input.sel[data-rowid="${CSS.escape(data.rowid)}"]`); if(mainCb){ mainCb.checked=false; mainCb.closest('tr')?.classList?.remove('selrow'); } tr.remove(); recalc(); });
      const newKey=keyForSelection(data); const rows=[...tSel.querySelectorAll('tr')]; let inserted=false; for(const r of rows){ const keyRow=keyFromRow(r); if(cmpKeys(newKey,keyRow)<0){ tSel.insertBefore(tr,r); inserted=true; break; } } if(!inserted) tSel.appendChild(tr);
    }
    function removeSelectedRow(rowid){ const tr=tSel.querySelector(`tr[data-rowid="${CSS.escape(rowid)}"]`); if(tr){ tr.remove(); const tot=[...tSel.querySelectorAll('tr')].reduce((s,tr)=> s + (Number(tr.dataset.tot||0)), 0); if(sumEl) sumEl.textContent = fmtBRL(tot); } }

    // filtros UI
    if(txtSearch && !txtSearch.dataset.bound){ txtSearch.addEventListener('input', ()=>{ textQuery=txtSearch.value||''; applyFilters(); }); txtSearch.dataset.bound='1'; }
    if(btnClearSearch && !btnClearSearch.dataset.bound){ btnClearSearch.addEventListener('click', ()=>{ txtSearch.value=''; txtSearch.focus(); textQuery=''; applyFilters(); }); btnClearSearch.dataset.bound='1'; }
    if(compraSelect && !compraSelect.dataset.bound){ compraSelect.addEventListener('change', ()=>{ compraSelecionada=compraSelect.value||''; applyFilters(); }); compraSelect.dataset.bound='1'; }
    if(tipoSelect && !tipoSelect.dataset.bound){ tipoSelect.addEventListener('change', ()=>{ tipoAtual=tipoSelect.value||''; loadAll(true); }); tipoSelect.dataset.bound='1'; }
    clearBtn?.addEventListener('click', ()=>{ clearAll(); loadAll(false); });

    function clearAll(){ allItens=[]; totalCount=0; compraSelecionada=''; if(compraSelect){ compraSelect.innerHTML='<option value="">Todos</option>'; compraSelect.value=''; } render([]); buildCompraOptions([]); if(filterCount) filterCount.textContent='Mostrando 0 de 0 itens'; tSel.innerHTML=''; sumEl && (sumEl.textContent='R$ 0,00'); selAll && (selAll.checked=false); progress={total:0,loaded:0}; updateStatus('Pronto para recarregar.'); }

    async function fetchItemsForUG(ugCod){ const key=`${ugCod}|${tipoAtual||'todos'}`; if(ugCache.has(key)) return ugCache.get(key); const params=new URLSearchParams({ uasg:String(ugCod) }); if(tipoAtual) params.set('tipo', tipoAtual); const resp=await fetch(`api/itens.php?${params.toString()}`); if(!resp.ok) throw new Error(`HTTP ${resp.status}`); const json=await resp.json(); const itens=Array.isArray(json.itens)? json.itens : []; ugCache.set(key,itens); return itens; }

    async function loadAll(showMessage){ clearAll(); if(showMessage) updateStatus('Recarregando itens (todas as UGs exceto 160517)...');
      try{
        const ugResp=await fetch('api/ugs.php'); const ugJson=await ugResp.json(); const ugArr=Array.isArray(ugJson.ugs)? ugJson.ugs : [];
        ugs = ugArr.map(u=>({ cod:String(u.codug||'').trim(), sigla:String(u.sigla||'').trim(), descricao:String(u.cidade_estado||u.descricao||'').trim() }))
                  .filter(u=>u.cod && u.cod!=='160517')
                  .sort((a,b)=>(parseInt(a.cod,10)||0)-(parseInt(b.cod,10)||0));
        progress.total = ugs.length; progress.loaded=0; updateStatus('Iniciando carregamento...');
        for(const ug of ugs){
          try{ updateStatus(`Carregando itens da UASG ${ug.cod}...`); const itens = await fetchItemsForUG(ug.cod); allItens.push(...itens); totalCount=allItens.length; progress.loaded++; buildCompraOptions(allItens); applyFilters(); }
          catch(e){ console.error('UG falhou', ug.cod, e); progress.loaded++; updateStatus(); }
        }
        updateStatus('Concluído.');
      }catch(e){ console.error(e); updateStatus('Falha ao carregar a lista de UGs.'); }
    }

    loadAll(false);
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    const trigger=document.querySelector('a[href="#itens-carona"]');
    if(trigger){
      trigger.addEventListener('shown.bs.tab', (ev)=>{
        init();
        // Garantir que a aba de UGs não permaneça ativa/visível quando esta for aberta
        const ugsPane = document.getElementById('ugs');
        if(ugsPane){ ugsPane.classList.remove('show','active'); }
      });
    }
    const pane=document.getElementById('itens-carona');
    if(pane && pane.classList.contains('show')){
      // Se for carregada já ativa, também desativa a aba de UGs
      const ugsPane = document.getElementById('ugs');
      if(ugsPane){ ugsPane.classList.remove('show','active'); }
      init();
    }
  });
})();
