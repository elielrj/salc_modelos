/* Helpers comuns */
const fmtBRL = (n) => Number(n || 0).toLocaleString('pt-BR', {style:'currency', currency:'BRL'});
const parseNumBR = (txt) => Number(String(txt||'').replace(/\./g,'').replace(',','.').replace(/[^\d.-]/g,''))||0;
const maskCNPJ = (s) => { const d = String(s||'').replace(/\D/g,''); return d.length===14?`${d.slice(0,2)}.${d.slice(2,5)}.${d.slice(5,8)}/${d.slice(8,12)}-${d.slice(12)}`: (s||''); };
const fmtDateBR = (iso) => { const s=String(iso||'').slice(0,10); if(!s) return ''; const [y,m,d]=s.split('-'); return (d&&m&&y)? `${d}/${m}/${y}`: s; };

function sicafLabel(v){
  const val = typeof v==='string'? v.trim().toLowerCase(): v;
  const t = ['true','1','regular','ok','habilitado','ativo'];
  const f = ['false','0','restricao','restrição','irregular','inativo','bloqueado'];
  const isT = typeof val==='boolean'? val===true : (!isNaN(+val)? +val===1 : t.includes(val));
  const isF = typeof val==='boolean'? val===false: (!isNaN(+val)? +val===0 : f.includes(val));
  if (isT) return ['Regular','sicaf-ok'];
  if (isF) return ['Restrição','sicaf-bad'];
  return [String(v ?? '—'),'sicaf-unk'];
}

/* ===== ATAS (tabela + ordenação) */
function initAtasSort() {
  const t = document.getElementById('tAtas');
  if (!t || t.dataset.sortReady==='1') return; t.dataset.sortReady='1';
  const thead=t.tHead, tbody=t.tBodies[0]; if(!thead||!tbody) return;
  const parseBRL=(s)=>Number(String(s||'').replace(/\./g,'').replace(',','.').replace(/[^\d.-]/g,''))||0;
  const toTS=(dmy)=>{const [d,m,y]=String(dmy||'').split('/'); return (d&&m&&y)? Date.UTC(+y,(+m||1)-1,+d||1):0;};
  const firstDateTS=(txt)=>{const m=String(txt||'').match(/(\d{2}\/\d{2}\/\d{4})/); return m? toTS(m[1]) : 0;};
  function keyForRow(tr,colIndex,type){
    const raw=(tr.children[colIndex]?.innerText||'').trim();
    if(type==='moeda-br') return parseBRL(raw);
    if(type==='data-br' || type==='vigencia-br') return firstDateTS(raw);
    return raw.toLowerCase();
  }
  const cmp=(a,b)=> a<b? -1 : a>b? 1 : 0;
  let lastTh=null, lastDir=1;
  function clearIcons(){ thead.querySelectorAll('.sort-ind').forEach(s=>s.textContent=''); }
  function setIcon(th,dir){
    const ind = th.querySelector('.sort-ind');
    if (ind) ind.textContent = (dir===1 ? '▲' : '▼');
  }
  thead.addEventListener('click', (e)=>{
    const th=e.target.closest('th'); if(!th||th.classList.contains('noclick')) return;
    const type=th.dataset.sort; if(!type) return;
    const colIndex=[...th.parentNode.children].indexOf(th);
    const rows=[...tbody.querySelectorAll('tr')];
    const keyed=rows.map(tr=>({tr, key:keyForRow(tr,colIndex,type)}));
    const dir=(lastTh===th && lastDir===1)? -1 : 1;
    keyed.sort((A,B)=>cmp(A.key,B.key)*dir);
    const frag=document.createDocumentFragment(); keyed.forEach(k=>frag.appendChild(k.tr));
    tbody.appendChild(frag); lastTh=th; lastDir=dir; clearIcons(); setIcon(th,dir);
    // renumerar após ordenação
    tbody.querySelectorAll('tr').forEach((tr,i)=>{ if(tr.children[0]) tr.children[0].textContent = String(i+1); });
  });
}

function initAtasTab(){
  const pane = document.getElementById('atas'); if(!pane || pane.dataset.loaded==='1') { initAtasSort(); return; }
  const tbody = document.querySelector('#tAtas tbody'); if(!tbody) { initAtasSort(); return; }
  const loadingRow = document.createElement('tr'); loadingRow.innerHTML = `<td colspan="8">Carregando atas...</td>`; tbody.appendChild(loadingRow);
  fetch('api/atas.php')
    .then(r=>r.json())
    .then(j=>{
      tbody.innerHTML='';
      (j.atas||[]).forEach(a=>{
        const ataNum = a.numeroAtaRegistroPreco || '—';
        const uasg = a.codigoUnidadeGerenciadora || '';
        const unid = a.nomeUnidadeGerenciadora || '';
        const orgao = a.nomeOrgao || '';
        const modCod = a.codigoModalidadeCompra || '';
        const modNome = a.nomeModalidadeCompra || '';
        const assin = a.dataAssinatura || '';
        const vigIni = a.dataVigenciaInicial || '';
        const vigFim = a.dataVigenciaFinal || '';
        const valor = a.valorTotal || 0;
        const compra = [a.numeroCompra, a.anoCompra].filter(Boolean).join('/') || '—';
        const lnAta = a.linkAtaPNCP || '';
        const lnComp = a.linkCompraPNCP || '';
        const idCompra = a.idCompra || '';
        const tr=document.createElement('tr');
        tr.innerHTML = `
          <td class=\"checkcol\"><input type=\"checkbox\" class=\"sel\" data-rowid=\"${compra}|${ataNum}\" data-compra=\"${compra}\" data-ata=\"${ataNum}\" data-modalidade=\"${(modNome||'').replace(/\"/g,'\\\"')}\" data-vig=\"${(vigIni||'').split('-').reverse().join('/')} à ${(vigFim||'').split('-').reverse().join('/')}\" data-valor=\"${valor}\"></td>
          <td class="rownum"></td>
          <td><div><strong>${compra}</strong></div>${a.numeroControlePncpCompra? `<div class="small">PNCP: ${a.numeroControlePncpCompra}</div>`:''}</td>
          <td><div>${ataNum}</div>${a.numeroControlePncpAta? `<div class=\"small\">PNCP: ${a.numeroControlePncpAta}</div>`:''}</td>
          <td><div>${modNome||'—'}</div><div class="small">Código: ${modCod||'—'}</div></td>
          <td class="nowrap">${(assin||'').substring(0,10).split('-').reverse().join('/')}</td>
          <td class="nowrap">${(vigIni||'').split('-').reverse().join('/')} à ${(vigFim||'').split('-').reverse().join('/')}</td>
          <td class="right">${fmtBRL(valor)}</td>
          <td class="nowrap">${lnAta? `<a href="${lnAta}" target="_blank" rel="noopener">Ata</a>`:''}${lnComp? ` | <a href="${lnComp}" target="_blank" rel="noopener">Compra</a>`:''}${idCompra? ` | <span class="small text-muted">id: ${idCompra}</span>`:''}</td>`;
        tbody.appendChild(tr);
      });
      // numerar linhas (Ord)
      tbody.querySelectorAll('tr').forEach((tr,i)=>{ const rn=tr.querySelector('.rownum'); if(rn) rn.textContent = String(i+1); });
      pane.dataset.loaded='1';
      initAtasSort();

      // seleção de atas para tabela secundária
      const tSel = document.getElementById('tAtasSel');
      const tSelBody = tSel?.tBodies?.[0];
      const sumEl = document.getElementById('atasSumTotal');
      const selAll = document.getElementById('atasSelAll');
      const btnCopy = document.getElementById('atasBtnCopy');
      const btnPrint = document.getElementById('atasBtnPrint');
      const copyMsg = document.getElementById('atasCopyMsg');
      function recalc(){ const tot=[...tSelBody.querySelectorAll('tr')].reduce((s,tr)=> s+(Number(tr.dataset.valor||0)),0); if(sumEl) sumEl.textContent=fmtBRL(tot); }
      tbody.addEventListener('change', (e)=>{
        const cb = e.target && e.target.matches('input.sel') ? e.target : null; if(!cb) return;
        const d = { rowid: cb.dataset.rowid, compra: cb.dataset.compra, ata: cb.dataset.ata, modalidade: cb.dataset.modalidade, vig: cb.dataset.vig, valor: Number(cb.dataset.valor||0) };
        if (cb.checked){ cb.closest('tr')?.classList?.add('selrow'); const tr=document.createElement('tr'); tr.dataset.rowid=d.rowid; tr.dataset.valor=String(d.valor||0); tr.innerHTML = `<td>${d.compra}</td><td>${d.ata}</td><td>${d.modalidade}</td><td>${d.vig}</td><td class='right'>${fmtBRL(d.valor||0)}</td><td><button class='btn-mini btnDel' title='Remover'>×</button></td>`; tr.querySelector('.btnDel').addEventListener('click', ()=>{ const c=tbody.querySelector(`input.sel[data-rowid=\"${d.rowid.replace(/"/g,'\\"')}\"]`); if(c){ c.checked=false; c.closest('tr')?.classList?.remove('selrow'); } tr.remove(); recalc(); }); tSelBody.appendChild(tr); recalc(); }
        else { cb.closest('tr')?.classList?.remove('selrow'); const tr=tSelBody.querySelector(`tr[data-rowid="${d.rowid}"]`); if(tr){ tr.remove(); recalc(); } }
      });
      selAll?.addEventListener('change', ()=>{ tbody.querySelectorAll('input.sel').forEach(cb=>{ if(cb.checked!==selAll.checked){ cb.checked=selAll.checked; cb.dispatchEvent(new Event('change',{bubbles:true})); } }); });
      btnCopy?.addEventListener('click', async ()=>{ const hdr=[...tSel.querySelectorAll('thead th')].map(th=>th.innerText.trim()); const rows=[...tSel.querySelectorAll('tbody tr')]; const totalTxt=sumEl?.innerText?.trim()||'R$ 0,00'; const td=(txt,tag='td',align='left')=>`<${tag} style="border:1px solid #ccc; padding:6px; text-align:${align}; vertical-align:top;">${txt}</${tag}>`; let html=`<meta charset='utf-8'><table style="border-collapse:collapse;border:1px solid #ccc;font:13px Arial"><thead><tr>${hdr.map(h=>td(h,'th')).join('')}</tr></thead><tbody>`; rows.forEach(tr=>{ const tds=tr.querySelectorAll('td'); html+=`<tr>${td(tds[0].innerText)}${td(tds[1].innerText)}${td(tds[2].innerText)}${td(tds[3].innerText)}${td(tds[4].innerText,'td','right')}${td('')}</tr>`; }); html+=`</tbody><tfoot><tr>${td('TOTAL','th','right')}<td colspan='3'></td>${td(totalTxt,'th','right')}<td></td></tr></tfoot></table>`; try{ if(navigator.clipboard && window.ClipboardItem){ const data={'text/html': new Blob([html],{type:'text/html'}),'text/plain': new Blob([html],{type:'text/plain'})}; await navigator.clipboard.write([new ClipboardItem(data)]); copyMsg && (copyMsg.textContent='Tabela copiada.'); } else { const div=document.createElement('div'); div.contentEditable='true'; div.style.position='fixed'; div.style.left='-99999px'; div.innerHTML=html; document.body.appendChild(div); const range=document.createRange(); range.selectNodeContents(div); const sel=window.getSelection(); sel.removeAllRanges(); sel.addRange(range); document.execCommand('copy'); document.body.removeChild(div); copyMsg && (copyMsg.textContent='Tabela copiada.'); } }catch(e){ copyMsg && (copyMsg.textContent='Não foi possível copiar.'); } setTimeout(()=>{ if(copyMsg) copyMsg.textContent=''; }, 3500); });
      btnPrint?.addEventListener('click', ()=>{ const clone=tSel.cloneNode(true); clone.querySelectorAll('td:last-child, th:last-child').forEach(n=>n.remove()); const win=window.open('', '_blank'); if(!win) return; win.document.write(`<html><head><meta charset='utf-8'><title>Atas selecionadas</title><style>table{border-collapse:collapse;width:100%;font:13px Arial} th,td{border:1px solid #ccc;padding:6px;} th{text-align:left;background:#f7f7f7}</style></head><body></body></html>`); win.document.body.appendChild(clone); win.focus(); win.print(); setTimeout(()=>win.close(), 500); });
    })
    .catch(()=>{ tbody.innerHTML = '<tr><td colspan="8">Falha ao carregar atas.</td></tr>'; initAtasSort(); });
}

/* ===== ITENS (lazy load + ordenar + seleção + copiar) */
function initItensTab() {
  const pane=document.getElementById('itens'); if(!pane || pane.dataset.ready==='1') return; pane.dataset.ready='1';
  if(!window.CSS) window.CSS={}; if(!CSS.escape) CSS.escape = v => String(v).replace(/["\\]/g, "\\$&");

  const el=s=>pane.querySelector(s); const els=s=>[...pane.querySelectorAll(s)];
  const tMain = el('#tPrincipal'); if(!tMain) return;
  const tBodyMain = tMain.tBodies[0]; const thead=tMain.tHead;
  const tBodySel = el('#tSel tbody'); const sumEl=el('#sumTotal'); const copyBtn=el('#btnCopy'); const copyMsg=el('#copyMsg'); const selAll=el('#selAll');
  const printBtn = el('#btnPrint');
  const uasgSpan = el('#uasgCurrent'); const uasgSelect = el('#selUasg'); const loadingMsg = document.getElementById('itensLoading');

  function _fmtDateBR(s){ if(!s) return '—'; const d=new Date(s); if(isNaN(d)) return s; const dd=String(d.getUTCDate()).padStart(2,'0'); const mm=String(d.getUTCMonth()+1).padStart(2,'0'); const yy=d.getUTCFullYear(); return `${dd}/${mm}/${yy}`; }

  // Build table rows from JSON
  // Keep full dataset and allow filtering by Compra/Ano and text
  let allItensCache = [];
  const compraActive = new Set();
  let textQuery = '';
  let totalCount = 0;
  function renderItens(itens){
    tBodyMain.innerHTML = '';
    const frag=document.createDocumentFragment();
    itens.forEach((it,idx)=>{
      const ord=idx+1;
      const numCompraComAno = [it.numeroCompra, it.anoCompra].filter(Boolean).join('/');
      const [sicafText,sicafClass] = sicafLabel(it.situacaoSicaf);
      const tr=document.createElement('tr');
      tr.dataset.vigini = it.dataVigenciaInicial || '';
      tr.dataset.ano = it.anoCompra || '';
      tr.dataset.compra = it.numeroCompra || '';
      tr.dataset.itemnum = it.numeroItem || '';
      const ni = it.niFornecedor||'';
      tr.innerHTML = `
        <td class="checkcol"><input type="checkbox" class="sel" data-rowid="${CSS.escape((it.numeroCompra||'nc')+'-'+(it.numeroItem||'ni'))}" data-compra="${numCompraComAno}" data-item="${it.numeroItem||''}" data-desc="${it.descricaoItem||''}" data-forn="${it.nomeRazaoSocialFornecedor||''}" data-ni="${ni}" data-qtd="${it.quantidadeHomologadaItem||0}" data-vu="${it.valorUnitario||0}"></td>
        <td class="rownum">${ord}</td>
        <td class="left nowrap"><div><strong>${numCompraComAno||'—'}</strong></div><div class="small">Item: ${it.numeroItem||'—'}</div></td>
        <td class="left">${(it.descricaoItem||'—')}${it.codigoItem? `<div class="small">Código Item: ${it.codigoItem}</div>`:''}</td>
        <td class="left"><div>${it.nomeRazaoSocialFornecedor||'—'}</div><div class="small">CNPJ: ${ni? maskCNPJ(ni) : '—'}</div></td>
        <td class="center">${(it.quantidadeHomologadaItem||0).toLocaleString('pt-BR')}</td>
        <td class="center">${fmtBRL(it.valorUnitario||0)}</td>
        <td class="center">${fmtBRL(it.valorTotal||0)}</td>
        <td class="center">${_fmtDateBR(it.dataVigenciaInicial)} à ${_fmtDateBR(it.dataVigenciaFinal)}</td>
        <td class="center nowrap">${it.tipoItem||'—'}</td>
        <td class="center ${sicafClass}"><i class="bi bi-hand-thumbs-up" title="${sicafText}" aria-label="${sicafText}"></i></td>
        <td class="center">${(it.quantidadeEmpenhada||0).toLocaleString('pt-BR')}</td>
        <td class="center nowrap">${ni? `<a href="api/certidao.php?cnpj=${ni}" target="_blank" rel="noopener" title="Certidão TCU"><i class="bi bi-book"></i></a>` : '—'}</td>`;
      frag.appendChild(tr);
    });
    tBodyMain.appendChild(frag);
    renumberRows();
  }

  function applyAllFilters(){
    let arr = allItensCache;
    if (compraActive.size>0){
      arr = arr.filter(it=> compraActive.has([it.numeroCompra, it.anoCompra].filter(Boolean).join('/')));
    }
    const q = (textQuery||'').trim().toLowerCase();
    if (q){
      arr = arr.filter(it=>
        String(it.descricaoItem||'').toLowerCase().includes(q) ||
        String(it.nomeRazaoSocialFornecedor||'').toLowerCase().includes(q)
      );
    }
    renderItens(arr);
    const cnt = el('#filterCount');
    if (cnt) cnt.textContent = `Mostrando ${arr.length} de ${totalCount} itens`;
  }

  // Build Compra/Ano filter UI
  function buildCompraFilter(itens){
    const fwrap = el('#compraFilter');
    if(!fwrap) return;
    const counts = new Map();
    itens.forEach(it=>{
      const key = [it.numeroCompra, it.anoCompra].filter(Boolean).join('/');
      if(!key) return;
      counts.set(key, (counts.get(key)||0)+1);
    });
    const keys = Array.from(counts.keys()).sort((a,b)=>{
      const [ca,aa]=a.split('/').map(n=>parseInt(n||'0',10));
      const [cb,ab]=b.split('/').map(n=>parseInt(n||'0',10));
      if(aa!==ab) return aa - ab; // ano asc
      return ca - cb; // número asc
    });
    let html = '<span class="group-title">Filtrar por Pregão:</span>';
    keys.forEach(k=>{
      const c = counts.get(k)||0;
      const id = 'fcomp_'+k.replace(/\D+/g,'_');
      html += `<label for="${id}"><input type="checkbox" class="compra-chk" id="${id}" data-key="${k}"><span>${k}</span> <span class="small">(${c})</span></label>`;
    });
    html += `<span class="actions small">| <span class="link" id="cfClear">limpar</span></span>`;
    fwrap.innerHTML = html;

    // Events
    fwrap.addEventListener('change', (e)=>{
      const cb = e.target && e.target.matches('input.compra-chk') ? e.target : null;
      if(!cb) return;
      const key = cb.dataset.key || '';
      if(!key) return;
      if(cb.checked) compraActive.add(key); else compraActive.delete(key);
      applyAllFilters();
    });
    fwrap.querySelector('#cfClear')?.addEventListener('click', ()=>{
      compraActive.clear();
      fwrap.querySelectorAll('input.compra-chk').forEach(i=> i.checked=false);
      applyAllFilters();
    });
  }

  function setupSearchFilter(){
    const i = el('#txtSearch'); if(!i) return;
    if (!i.dataset.bound){
      const handler = ()=>{ textQuery = i.value || ''; applyAllFilters(); };
      i.addEventListener('input', handler);
      i.dataset.bound='1';
    }
    const btn = el('#btnClearSearch');
    if (btn && !btn.dataset.bound){
      btn.addEventListener('click', ()=>{ i.value=''; i.focus(); textQuery=''; applyAllFilters(); });
      btn.dataset.bound='1';
    }
  }

  function renumberRows(){ els('#tPrincipal tbody tr').forEach((tr,i)=> tr.querySelector('.rownum').textContent = i+1 ); }
  function recalcTotal(){ const tot=[...tBodySel.querySelectorAll('tr')].reduce((s,tr)=> s + (Number(tr.dataset.tot||0)), 0); if(sumEl) sumEl.textContent = fmtBRL(tot); }
  function keyFromData(d){ const ca=d.compra.split('/'); return {compra: parseInt(ca[0]||'0',10), ano: parseInt(ca[1]||'0',10), item: parseInt(String(d.item||'').replace(/\D+/g,''),10)||0}; }
  function keyFromRow(tr){ const td=tr.querySelectorAll('td'); const ca=(td[0]?.innerText.trim()||'').split('/'); const it=parseInt((td[1]?.innerText||'').replace(/\D+/g,''),10)||0; return {compra: parseInt(ca[0]||'0',10), ano: parseInt(ca[1]||'0',10), item: it}; }
  function compareKeys(a,b){ if(a.ano!==b.ano) return a.ano-b.ano; if(a.compra!==b.compra) return a.compra-b.compra; if(a.item!==b.item) return a.item-b.item; return 0; }

  function addSelectedRow(data){
    if (tBodySel.querySelector(`tr[data-rowid="${CSS.escape(data.rowid)}"]`)) return;
    const tr=document.createElement('tr'); tr.dataset.rowid=data.rowid;
    const max=Number(data.qtd)||0, vu=Number(data.vu)||0, init=Math.min(1, Math.max(0,max));
    tr.innerHTML = `
      <td>${data.compra}</td>
      <td class="nowrap">${data.item}</td>
      <td class="left">${data.desc}</td>
      <td class="left">${data.forn}<div class="small">CNPJ: ${maskCNPJ(data.ni)||'—'}</div></td>
      <td class="right">${max.toLocaleString('pt-BR')}</td>
      <td class="right">${fmtBRL(vu)}</td>
      <td class="right"><input type="number" min="1" max="${max}" step="1" value="${init}" class="qtdBuy"></td>
      <td class="right totCell"></td>
      <td><button class="btn-mini btnDel" title="Remover">×</button></td>`;
    const input=tr.querySelector('.qtdBuy'), totCell=tr.querySelector('.totCell');
    function updateLine(){ let q=parseInt(input.value,10); if(!Number.isInteger(q)||q<1) q=1; if(q>max) q=max; input.value=q; const tot=q*vu; tr.dataset.tot=String(tot); totCell.textContent=fmtBRL(tot); recalcTotal(); }
    input.addEventListener('input', updateLine); input.addEventListener('blur', updateLine);
    tr.querySelector('.btnDel').addEventListener('click', ()=>{
      const mainCb=document.querySelector(`#tPrincipal tbody input.sel[data-rowid="${CSS.escape(data.rowid)}"]`);
      if (mainCb){
        mainCb.checked = false;
        mainCb.closest('tr')?.classList?.remove('selrow');
      }
      tr.remove(); recalcTotal();
    });
    const newK=keyFromData(data); const rows=[...tBodySel.querySelectorAll('tr')]; let inserted=false;
    for(const r of rows){ if (compareKeys(newK, keyFromRow(r))<0){ tBodySel.insertBefore(tr,r); inserted=true; break; } }
    if(!inserted) tBodySel.appendChild(tr); updateLine();
  }
  function removeSelectedRow(id){ const tr=tBodySel.querySelector(`tr[data-rowid="${CSS.escape(id)}"]`); if(tr){ tr.remove(); recalcTotal(); } }

  // Ordenação no cabeçalho principal
  function clearSortIcons(){ thead.querySelectorAll('.sort-ind').forEach(sp=>sp.textContent=''); }
  function setSortIcon(th,dir){
    const ind = th.querySelector('.sort-ind');
    if (ind) ind.textContent = (dir===1 ? '▲' : '▼');
  }
  function parseDateISO(s){ const d=new Date(s); return isNaN(d)?0:d.getTime(); }
  function keyForRow(tr, sortType, colIndex){
    switch(sortType){
      case 'rownum': return [...tBodyMain.children].indexOf(tr);
      case 'numero': case 'moeda': return parseNumBR(tr.children[colIndex].innerText.trim());
      case 'vigencia': return parseDateISO(tr.dataset.vigini||'');
      case 'compraitem': return [parseInt(tr.dataset.ano||'0',10), parseInt(tr.dataset.compra||'0',10), parseInt(tr.dataset.itemnum||'0',10)];
      default: return tr.children[colIndex].innerText.trim().toLowerCase();
    }
  }
  function cmp(a,b){ if(Array.isArray(a)&&Array.isArray(b)){ for(let i=0;i<Math.max(a.length,b.length);i++){ const da=a[i]??0, db=b[i]??0; if(da<db) return -1; if(da>db) return 1; } return 0;} return a<b? -1 : a>b? 1 : 0; }
  let lastSortTh=null, lastDir=1;
  thead.addEventListener('click', (e)=>{ const th=e.target.closest('th'); if(!th||th.classList.contains('noclick')) return; const sortType=th.dataset.sort; if(!sortType) return; const colIndex=[...th.parentNode.children].indexOf(th); const rows=[...tBodyMain.querySelectorAll('tr')]; const keyed=rows.map(tr=>({tr,key:keyForRow(tr,sortType,colIndex)})); const dir=(lastSortTh===th && lastDir===1)? -1:1; keyed.sort((a,b)=>cmp(a.key,b.key)*dir); const frag=document.createDocumentFragment(); keyed.forEach(k=>frag.appendChild(k.tr)); tBodyMain.appendChild(frag); lastSortTh=th; lastDir=dir; clearSortIcons(); setSortIcon(th,dir); renumberRows(); });

  // Delegação para selecionar linhas
  tMain.addEventListener('change', (e)=>{
    const cb = e.target && e.target.matches('tbody input.sel') ? e.target : null; if(!cb) return;
    const data = { rowid: cb.dataset.rowid, compra: cb.dataset.compra, item: cb.dataset.item, desc: cb.dataset.desc, forn: cb.dataset.forn, ni: cb.dataset.ni, qtd: cb.dataset.qtd, vu: cb.dataset.vu };
    if (cb.checked){
      cb.closest('tr')?.classList?.add('selrow');
      addSelectedRow(data);
    } else {
      cb.closest('tr')?.classList?.remove('selrow');
      removeSelectedRow(data.rowid);
    }
  });
  selAll?.addEventListener('change', ()=>{ els('#tPrincipal tbody input.sel').forEach(cb=>{ if(cb.checked!==selAll.checked){ cb.checked=selAll.checked; cb.dispatchEvent(new Event('change',{bubbles:true})); } }); });

  // Copiar tabela selecionada
  copyBtn?.addEventListener('click', async ()=>{
    const src=el('#tSel'); if(!src) return;
    const hdr=[...src.querySelectorAll('thead th')].map(th=>th.innerText.trim());
    const rows=[...src.querySelectorAll('tbody tr')];
    const totalTxt = el('#sumTotal')?.innerText?.trim() || 'R$ 0,00';
    const td=(txt,tag='td',align='left')=>`<${tag} style="border:1px solid #ccc; padding:6px; text-align:${align}; vertical-align:top;">${txt}</${tag}>`;
    let html = `<meta charset="utf-8"><table style="border-collapse:collapse; border:1px solid #ccc; font-family:Arial,Helvetica,sans-serif; font-size:13px;"><thead><tr>${hdr.map(h=>td(h,'th')).join('')}</tr></thead><tbody>`;
    rows.forEach(tr=>{ const tds=tr.querySelectorAll('td'); const compra=tds[0].innerText.trim(); const item=tds[1].innerText.trim(); const desc=tds[2].innerText.trim(); const forn=tds[3].childNodes[0].textContent.trim(); const idRaw=(tds[3].querySelector('.small')?.innerText||''); const idOnly=idRaw.replace(/^(?:NI|CNPJ)\s*:\s*/i,'').trim(); const cnpjFmt=idOnly? idOnly : '—'; const qtdDisp=tds[4].innerText.trim(); const vUnit=tds[5].innerText.trim(); const qBuy=tds[6].querySelector('input')?.value||''; const tot=tds[7].innerText.trim(); html += `<tr>${td(compra)}${td(item)}${td(`${desc}<div style='color:#666;font-size:12px'>CNPJ: ${cnpjFmt}</div>`)}${td(forn)}${td(qtdDisp,'td','right')}${td(vUnit,'td','right')}${td(qBuy,'td','right')}${td(tot,'td','right')}${td('')}</tr>`; });
    html += `</tbody><tfoot><tr>${td('TOTAL','th','right')}<td colspan="6"></td>${td(totalTxt,'th','right')}<td></td></tr></tfoot></table>`;
    try{
      if(navigator.clipboard && window.ClipboardItem){
        const data={'text/html': new Blob([html],{type:'text/html'}), 'text/plain': new Blob([html],{type:'text/plain'})};
        await navigator.clipboard.write([new ClipboardItem(data)]);
        copyMsg && (copyMsg.textContent='Tabela (formatada) copiada para a área de transferência.');
      } else {
        const div=document.createElement('div'); div.contentEditable='true'; div.style.position='fixed'; div.style.left='-99999px'; div.innerHTML=html; document.body.appendChild(div);
        const range=document.createRange(); range.selectNodeContents(div); const sel=window.getSelection(); sel.removeAllRanges(); sel.addRange(range); document.execCommand('copy'); document.body.removeChild(div);
        copyMsg && (copyMsg.textContent='Tabela (formatada) copiada para a área de transferência.');
      }
    }catch(e){ console.error(e); copyMsg && (copyMsg.textContent='Não foi possível copiar a tabela.'); }
    setTimeout(()=>{ if(copyMsg) copyMsg.textContent=''; }, 3500);
  });
  printBtn?.addEventListener('click', ()=>{
    const src=el('#tSel'); if(!src) return; const clone=src.cloneNode(true); clone.querySelectorAll('td:last-child, th:last-child').forEach(n=>n.remove()); const win=window.open('', '_blank'); if(!win) return; win.document.write(`<html><head><meta charset='utf-8'><title>Itens selecionados</title><style>table{border-collapse:collapse;width:100%;font:13px Arial} th,td{border:1px solid #ccc;padding:6px;} th{text-align:left;background:#f7f7f7}</style></head><body></body></html>`); win.document.body.appendChild(clone); win.focus(); win.print(); setTimeout(()=>win.close(), 500);
  });

  function loadItens(uasg){
    if (uasgSpan) uasgSpan.textContent = String(uasg||'');
    compraActive.clear(); textQuery='';
    el('#txtSearch') && (el('#txtSearch').value='');
    if (tBodySel){ tBodySel.innerHTML=''; sumEl && (sumEl.textContent='R$ 0,00'); }
    el('#compraFilter') && (el('#compraFilter').innerHTML='');
    if (loadingMsg) loadingMsg.textContent='Carregando itens...';
    fetch('api/itens.php'+(uasg?`?uasg=${encodeURIComponent(uasg)}`:''))
      .then(r=>r.json())
      .then(j=>{ const arr=j.itens||[]; allItensCache = arr.slice(); totalCount = arr.length; buildCompraFilter(arr); setupSearchFilter(); applyAllFilters(); if(loadingMsg) loadingMsg.textContent = `Carregados ${j.total||arr.length} itens.`; })
      .catch(()=>{ if(loadingMsg) loadingMsg.textContent='Falha ao carregar itens.'; });
  }

  function populateUasg(){
    if(!uasgSelect) { loadItens((uasgSpan?.textContent||'').replace(/\D+/g,'')||''); return; }
    uasgSelect.disabled = true; uasgSelect.innerHTML = '<option>Carregando UGs...</option>';
    const def = (uasgSpan?.textContent||'').replace(/\D+/g,'') || '';
    fetch('api/ugs.php')
      .then(r=>r.json())
      .then(j=>{
        const ugs = (j.ugs||[]).slice().sort((a,b)=> (parseInt(a.codug,10)||0) - (parseInt(b.codug,10)||0));
        let html='';
        ugs.forEach(u=>{ const val=String(u.codug||''); const sel = (val===def? ' selected' : ''); html += `<option value="${val}"${sel}>${val} — ${u.sigla||''}</option>`; });
        if(!html){ html = `<option value="${def}">${def}</option>`; }
        uasgSelect.innerHTML = html; uasgSelect.disabled=false;
        loadItens(uasgSelect.value||def);
      })
      .catch(()=>{ uasgSelect.innerHTML = `<option value="${def}">${def}</option>`; uasgSelect.disabled=false; loadItens(def); });
    uasgSelect.addEventListener('change', ()=>{ loadItens(uasgSelect.value); });
  }

  // Inicializar carregando UGs e itens
  populateUasg();
}

document.addEventListener('DOMContentLoaded', ()=>{
  document.querySelector('a[href="#itens"]')?.addEventListener('shown.bs.tab', initItensTab);
  document.querySelector('a[href="#atas"]')?.addEventListener('shown.bs.tab', initAtasTab);
  document.querySelector('a[href="#ugs"]')?.addEventListener('shown.bs.tab', initUGsTab);
  const contratosLink = document.querySelector('a[href="#contratos"]');
  contratosLink?.addEventListener('shown.bs.tab', initContratosTab);
  // Fallback: se o evento do Bootstrap não disparar, inicia no clique
  contratosLink?.addEventListener('click', ()=> setTimeout(initContratosTab, 0));
  // Acesso direto via hash
  if (location.hash === '#contratos') initContratosTab();
  // Listar Contratos (contratos sem itens)
  const contratosListaLink = document.querySelector('a[href="#contratos-listar"]');
  contratosListaLink?.addEventListener('shown.bs.tab', initContratosListaTab);
  contratosListaLink?.addEventListener('click', ()=> setTimeout(initContratosListaTab, 0));
  if (location.hash === '#contratos-listar') initContratosListaTab();
  // Faz uma carga inicial para evitar problemas de evento
  initContratosListaTab();
  // Se uma guia estiver ativa por padrão, inicializa imediatamente
  const itensPane = document.getElementById('itens');
  if (itensPane && itensPane.classList.contains('show')) initItensTab();
  const ugsPane = document.getElementById('ugs');
  if (ugsPane && ugsPane.classList.contains('show')) initUGsTab();
});

// ===== UGs (CSV -> tabela + ordenação simples)
function initUGsTab(){
  const pane = document.getElementById('ugs');
  if(!pane || pane.dataset.loaded==='1') return;
  const tbody = document.querySelector('#tUGs tbody'); if(!tbody) return;
  const loadingRow = document.createElement('tr'); loadingRow.innerHTML = `<td colspan="6">Carregando UGs...</td>`; tbody.appendChild(loadingRow);
  fetch('api/ugs.php')
    .then(r=>r.json())
    .then(j=>{
      tbody.innerHTML='';
      (j.ugs||[]).forEach((u)=>{
        const tr=document.createElement('tr');
        const ce = String(u.cidade_estado||'');
        let cidade = ce, uf = '';
        if (ce.includes('/')) { const parts = ce.split('/'); cidade = (parts[0]||'').trim(); uf = (parts[1]||'').trim(); }
        tr.innerHTML = `
          <td class="rownum"></td>
          <td class="right">${u.codug||''}</td>
          <td>${u.sigla||''}</td>
          <td>${u.cma||''}</td>
          <td>${cidade}</td>
          <td>${uf}</td>`;
        tbody.appendChild(tr);
      });
      // numerar linhas
      tbody.querySelectorAll('tr').forEach((tr,i)=>{ if(tr.children[0]) tr.children[0].textContent = String(i+1); });
      pane.dataset.loaded='1';
      initUGsSort();
    })
    .catch(()=>{ tbody.innerHTML = '<tr><td colspan="6">Falha ao carregar UGs.</td></tr>'; });
}

// ===== Listar Contratos (sem itens)
function initContratosListaTab(){
  const pane = document.getElementById('contratos-listar');
  if(!pane || pane.dataset.bound==='1') return;
  const msg = pane.querySelector('#clMsg');
  const tbody = pane.querySelector('#clTable tbody');
  const foot = pane.querySelector('#clFoot');
  const cFilterWrap = pane.querySelector('#clCompraFilter');
  const kFilterWrap = pane.querySelector('#clContratoFilter');
  const txtSearch = pane.querySelector('#clTxtSearch');
  const btnClearSearch = pane.querySelector('#clBtnClearSearch');
  const filterCount = pane.querySelector('#clFilterCount');
  const tSelBody = pane.querySelector('#clSel tbody');
  const selSum = pane.querySelector('#clSumTotal');
  const selCopyBtn = pane.querySelector('#clBtnCopy');
  const selPrintBtn = pane.querySelector('#clBtnPrint');
  const selCopyMsg = pane.querySelector('#clCopyMsg');
  const selAll = pane.querySelector('#clSelAll');

  function fmtDate(iso){ try{ return (iso? new Date(iso).toLocaleDateString('pt-BR') : ''); }catch(e){ return iso||''; } }
  function fmtDateTime(iso){ try{ if(!iso) return ''; const d=new Date(iso); return d.toLocaleDateString('pt-BR')+' '+d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}); }catch(e){ return iso||''; } }

  let allRows = [];
  let activeCompras = new Set();
  let activeContratos = new Set();
  let textQuery = '';
  const selectedKeys = new Set();

  function renderRows(list){
    tbody.innerHTML = '';
    const rows = list.map((rec)=>{
      const forn = `${rec.nomeRazaoSocialFornecedor||''}<div class="small text-muted">${rec.niFornecedor||''}</div>`;
      const vig = `${fmtDate(rec.dataVigenciaInicial)} a ${fmtDate(rec.dataVigenciaFinal)}`;
      // dias restantes para o fim da vigência
      let diasRest = '';
      let rowCls = '';
      try {
        if (rec.dataVigenciaFinal) {
          const hoje = new Date(); hoje.setHours(0,0,0,0);
          const fim = new Date(rec.dataVigenciaFinal); fim.setHours(0,0,0,0);
          const diff = Math.ceil((fim - hoje) / 86400000);
          if (Number.isFinite(diff)) {
            diasRest = String(diff);
            if (diff <= 30) rowCls = 'table-danger';
            else if (diff <= 90) rowCls = 'table-warning';
          }
        }
      } catch(_) { /* ignore */ }
      const vgl = fmtBRL(rec.valorGlobal||0);
      const obj = rec.objeto||'';
      const compra = rec.numeroCompra || '';
      const modalidade = rec.nomeModalidadeCompra || '';
      const categoria = rec.nomeCategoria || '';
      const processo = rec.processo || '';
      const inc = fmtDateTime(rec.dataHoraInclusao);
      const exc = fmtDateTime(rec.dataHoraExclusao);
      const excl = rec.contratoExcluido ? 'Sim' : 'Não';
      const tcu = rec.niFornecedor ? `<a href=\"api/certidao.php?cnpj=${rec.niFornecedor}\" target=\"_blank\" rel=\"noopener\" title=\"Certidão TCU\"><i class=\"bi bi-book\"></i></a>` : '—';
      const rowid = `${rec.numeroContrato||''}|${compra}`;
      return `<tr data-rowid="${rowid}"${rowCls?` class=\"${rowCls}\"`:''}>
        <td class=\"checkcol\"><input type=\"checkbox\" class=\"sel\" ${selectedKeys.has(rowid)?'checked':''} data-rowid=\"${rowid}\" data-contrato=\"${rec.numeroContrato||''}\" data-compra=\"${compra}\" data-forn=\"${(rec.nomeRazaoSocialFornecedor||'').replace(/\"/g,'\\\"')}\" data-ni=\"${rec.niFornecedor||''}\" data-obj=\"${(obj).replace(/\"/g,'\\\"')}\" data-vig=\"${vig}\" data-vg=\"${rec.valorGlobal||0}\"></td>
        <td class="rownum"></td>
        <td>${rec.numeroContrato||''}</td>
        <td>${compra}</td>
        <td>${modalidade}</td>
        <td>${categoria}</td>
        <td>${forn}</td>
        <td class="left">${obj}</td>
        <td>${vig}</td>
        <td class=\"right\">${diasRest}</td>
        <td class="right">${vgl}</td>
        <td>${processo}</td>
        <td>${inc}</td>
        <td>${exc}</td>
        <td>${excl}</td>
        <td class="right">${vgl}</td>
        <td class="center nowrap">${tcu}</td>
      </tr>`;
    }).join('');
    tbody.innerHTML = rows;
    tbody.querySelectorAll('tr').forEach((tr,i)=>{ const cell=tr.querySelector('.rownum'); if(cell) cell.textContent=String(i+1); });
  }

  function applyFilters(){
    // de-duplicar por numeroContrato|numeroCompra
    const seen = new Set();
    let list = [];
    allRows.forEach(r=>{ const key = `${r.numeroContrato||''}|${r.numeroCompra||''}`; if(seen.has(key)) return; seen.add(key); list.push(r); });
    if (activeCompras.size){ list = list.filter(r=> activeCompras.has(String(r.numeroCompra||''))); }
    if (activeContratos.size){ list = list.filter(r=> activeContratos.has(String(r.numeroContrato||''))); }
    if (textQuery){ const q = textQuery.toLowerCase(); list = list.filter(r=> String(r.objeto||'').toLowerCase().includes(q) || String(r.nomeRazaoSocialFornecedor||'').toLowerCase().includes(q)); }
    filterCount && (filterCount.textContent = `(${list.length} de ${allRows.length})`);
    renderRows(list);
  }

  function buildCompraFilter(arr){
    if (!cFilterWrap) return;
    const counts = new Map();
    arr.forEach(r=>{ const key=String(r.numeroCompra||''); if(key) counts.set(key,(counts.get(key)||0)+1); });
    const keys = Array.from(counts.keys()).sort((a,b)=>{ const na=parseInt(a,10)||0, nb=parseInt(b,10)||0; if(na!==nb) return na-nb; return String(a).localeCompare(String(b)); });
    let html = '<span class="group-title">Filtrar por Compra:</span>';
    keys.forEach(k=>{ const c=counts.get(k)||0; const id='clc_'+k.replace(/\W+/g,'_'); html += `<label for="${id}"><input type="checkbox" class="clcompra-chk" id="${id}" data-key="${k}"><span>${k}</span> <span class="small">(${c})</span></label>`; });
    html += `<span class="actions small">| <span class="link" id="clCClr">limpar</span></span>`;
    cFilterWrap.innerHTML = html;
    cFilterWrap.addEventListener('change', (e)=>{ const cb=e.target && e.target.matches('input.clcompra-chk')? e.target:null; if(!cb)return; const key=cb.dataset.key||''; if(!key)return; if(cb.checked) activeCompras.add(key); else activeCompras.delete(key); applyFilters(); });
    cFilterWrap.querySelector('#clCClr')?.addEventListener('click', ()=>{ activeCompras.clear(); cFilterWrap.querySelectorAll('input.clcompra-chk').forEach(i=> i.checked=false); applyFilters(); });
  }

  function buildContratoFilter(arr){
    if (!kFilterWrap) return;
    const counts = new Map();
    arr.forEach(r=>{ const key=String(r.numeroContrato||''); if(key) counts.set(key,(counts.get(key)||0)+1); });
    const keys = Array.from(counts.keys()).sort();
    let html = '<span class="group-title">Filtrar por Contrato:</span>';
    keys.forEach(k=>{ const c=counts.get(k)||0; const id='clk_'+k.replace(/\W+/g,'_'); html += `<label for="${id}"><input type="checkbox" class="clcontrato-chk" id="${id}" data-key="${k}"><span>${k}</span> <span class="small">(${c})</span></label>`; });
    html += `<span class="actions small">| <span class="link" id="clKClr">limpar</span></span>`;
    kFilterWrap.innerHTML = html;
    kFilterWrap.addEventListener('change', (e)=>{ const cb=e.target && e.target.matches('input.clcontrato-chk')? e.target:null; if(!cb)return; const key=cb.dataset.key||''; if(!key)return; if(cb.checked) activeContratos.add(key); else activeContratos.delete(key); applyFilters(); });
    kFilterWrap.querySelector('#clKClr')?.addEventListener('click', ()=>{ activeContratos.clear(); kFilterWrap.querySelectorAll('input.clcontrato-chk').forEach(i=> i.checked=false); applyFilters(); });
  }

  txtSearch?.addEventListener('input', ()=>{ textQuery = txtSearch.value||''; applyFilters(); });
  btnClearSearch?.addEventListener('click', ()=>{ if(txtSearch){ txtSearch.value=''; textQuery=''; applyFilters(); } });

  function recalcSelTotal(){ const tot=[...tSelBody?.querySelectorAll('tr')||[]].reduce((s,tr)=> s + (Number(tr.dataset.vg||0)), 0); if(selSum) selSum.textContent = fmtBRL(tot); }
  function addSelRow(d){ if(!tSelBody || tSelBody.querySelector(`tr[data-rowid="${CSS.escape(d.rowid)}"]`)) return; const tr=document.createElement('tr'); tr.dataset.rowid=d.rowid; tr.dataset.vg=String(d.vg||0); tr.innerHTML=`
      <td>${d.contrato||'—'}</td>
      <td>${d.compra||'—'}</td>
      <td class="left">${d.forn||''}${d.ni? `<div class="small">CNPJ: ${maskCNPJ(d.ni)}</div>`:''}</td>
      <td class="left">${d.obj||''}</td>
      <td>${d.vig||''}</td>
      <td class="right">${fmtBRL(d.vg||0)}</td>
      <td><button class="btn-mini btnDel" title="Remover">×</button></td>`;
    tr.querySelector('.btnDel').addEventListener('click', ()=>{ const cb=tbody.querySelector(`input.sel[data-rowid="${CSS.escape(d.rowid)}"]`); if(cb){ cb.checked=false; cb.closest('tr')?.classList?.remove('selrow'); selectedKeys.delete(d.rowid);} tr.remove(); recalcSelTotal(); });
    tSelBody.appendChild(tr); recalcSelTotal(); }
  function removeSelRow(rowid){ const tr=tSelBody?.querySelector(`tr[data-rowid="${CSS.escape(rowid)}"]`); if(tr){ tr.remove(); recalcSelTotal(); } }

  // seleção
  tbody.addEventListener('change', (e)=>{
    const cb = e.target && e.target.matches('input.sel') ? e.target : null; if(!cb) return;
    const d = { rowid: cb.dataset.rowid, contrato: cb.dataset.contrato, compra: cb.dataset.compra, forn: cb.dataset.forn, ni: cb.dataset.ni, obj: cb.dataset.obj, vig: cb.dataset.vig, vg: Number(cb.dataset.vg||0) };
    if (cb.checked){ cb.closest('tr')?.classList?.add('selrow'); selectedKeys.add(d.rowid); addSelRow(d); }
    else { cb.closest('tr')?.classList?.remove('selrow'); selectedKeys.delete(d.rowid); removeSelRow(d.rowid); }
  });
  selAll?.addEventListener('change', ()=>{ tbody.querySelectorAll('input.sel').forEach(cb=>{ if(cb.checked!==selAll.checked){ cb.checked=selAll.checked; cb.dispatchEvent(new Event('change',{bubbles:true})); } }); });

  function recalcSelTotal(){ const tot=[...tSelBody?.querySelectorAll('tr')||[]].reduce((s,tr)=> s + (Number(tr.dataset.vg||0)), 0); if(selSum) selSum.textContent = fmtBRL(tot); }
  function addSelRow(d){ if(!tSelBody || tSelBody.querySelector(`tr[data-rowid="${CSS.escape(d.rowid)}"]`)) return; const tr=document.createElement('tr'); tr.dataset.rowid=d.rowid; tr.dataset.vg=String(d.vg||0); tr.innerHTML=`
      <td>${d.contrato||'—'}</td>
      <td>${d.compra||'—'}</td>
      <td class="left">${d.forn||''}${d.ni? `<div class="small">CNPJ: ${maskCNPJ(d.ni)}</div>`:''}</td>
      <td class="left">${d.obj||''}</td>
      <td>${d.vig||''}</td>
      <td class="right">${fmtBRL(d.vg||0)}</td>
      <td><button class="btn-mini btnDel" title="Remover">×</button></td>`;
    tr.querySelector('.btnDel').addEventListener('click', ()=>{ const cb=tbody.querySelector(`input.sel[data-rowid="${CSS.escape(d.rowid)}"]`); if(cb){ cb.checked=false; cb.closest('tr')?.classList?.remove('selrow'); } tr.remove(); recalcSelTotal(); });
    tSelBody.appendChild(tr); recalcSelTotal(); }
  function removeSelRow(rowid){ const tr=tSelBody?.querySelector(`tr[data-rowid="${CSS.escape(rowid)}"]`); if(tr){ tr.remove(); recalcSelTotal(); } }

  // seleção
  tbody.addEventListener('change', (e)=>{
    const cb = e.target && e.target.matches('input.sel') ? e.target : null; if(!cb) return;
    const d = { rowid: cb.dataset.rowid, contrato: cb.dataset.contrato, compra: cb.dataset.compra, forn: cb.dataset.forn, ni: cb.dataset.ni, obj: cb.dataset.obj, vig: cb.dataset.vig, vg: Number(cb.dataset.vg||0) };
    if (cb.checked){ cb.closest('tr')?.classList?.add('selrow'); addSelRow(d); }
    else { cb.closest('tr')?.classList?.remove('selrow'); removeSelRow(d.rowid); }
  });
  selAll?.addEventListener('change', ()=>{ tbody.querySelectorAll('input.sel').forEach(cb=>{ if(cb.checked!==selAll.checked){ cb.checked=selAll.checked; cb.dispatchEvent(new Event('change',{bubbles:true})); } }); });

  selCopyBtn?.addEventListener('click', async ()=>{
    const src=pane.querySelector('#clSel'); if(!src) return;
    const hdr=[...src.querySelectorAll('thead th')].map(th=>th.innerText.trim());
    const rows=[...src.querySelectorAll('tbody tr')];
    const totalTxt = pane.querySelector('#clSumTotal')?.innerText?.trim() || 'R$ 0,00';
    const td=(txt,tag='td',align='left')=>`<${tag} style="border:1px solid #ccc; padding:6px; text-align:${align}; vertical-align:top;">${txt}</${tag}>`;
    let html = `<meta charset="utf-8"><table style="border-collapse:collapse; border:1px solid #ccc; font-family:Arial,Helvetica,sans-serif; font-size:13px;"><thead><tr>${hdr.map(h=>td(h,'th')).join('')}</tr></thead><tbody>`;
    rows.forEach(tr=>{ const tds=tr.querySelectorAll('td'); html += `<tr>${td(tds[0].innerText)}${td(tds[1].innerText)}${td(tds[2].innerHTML)}${td(tds[3].innerText)}${td(tds[4].innerText)}${td(tds[5].innerText,'td','right')}${td('')}</tr>`; });
    html += `</tbody><tfoot><tr>${td('TOTAL','th','right')}<td colspan="4"></td>${td(totalTxt,'th','right')}<td></td></tr></tfoot></table>`;
    try{
      if(navigator.clipboard && window.ClipboardItem){ const data={'text/html': new Blob([html],{type:'text/html'}),'text/plain': new Blob([html],{type:'text/plain'})}; await navigator.clipboard.write([new ClipboardItem(data)]); selCopyMsg && (selCopyMsg.textContent='Tabela copiada.'); }
      else { const div=document.createElement('div'); div.contentEditable='true'; div.style.position='fixed'; div.style.left='-99999px'; div.innerHTML=html; document.body.appendChild(div); const range=document.createRange(); range.selectNodeContents(div); const sel=window.getSelection(); sel.removeAllRanges(); sel.addRange(range); document.execCommand('copy'); document.body.removeChild(div); selCopyMsg && (selCopyMsg.textContent='Tabela copiada.'); }
    }catch(e){ selCopyMsg && (selCopyMsg.textContent='Não foi possível copiar.'); }
    setTimeout(()=>{ if(selCopyMsg) selCopyMsg.textContent=''; }, 3500);
  });
  selPrintBtn?.addEventListener('click', ()=>{
    const src=pane.querySelector('#clSel'); if(!src) return; const clone=src.cloneNode(true); clone.querySelectorAll('td:last-child, th:last-child').forEach(n=>n.remove()); const win=window.open('', '_blank'); if(!win) return; win.document.write(`<html><head><meta charset='utf-8'><title>Contratos selecionados</title><style>table{border-collapse:collapse;width:100%;font:13px Arial} th,td{border:1px solid #ccc;padding:6px;} th{text-align:left;background:#f7f7f7}</style></head><body></body></html>`); win.document.body.appendChild(clone); win.focus(); win.print(); setTimeout(()=>win.close(), 500);
  });

  async function load(){
    if (pane.dataset.loading==='1') return; pane.dataset.loading='1';
    msg && (msg.textContent='Carregando contratos...');
    tbody && (tbody.innerHTML='');
    try{
      const r = await fetch('api/contratos_listar.php');
      const j = await r.json();
      if (j.error){ msg && (msg.textContent = 'Falha ao consultar.'); return; }
      const arr = j.resultado || [];
      if(!arr.length){ tbody.innerHTML = '<tr><td colspan="17">Nenhum contrato encontrado.</td></tr>'; msg && (msg.textContent=''); return; }
      allRows = arr.slice();
      buildCompraFilter(allRows);
      buildContratoFilter(allRows);
      applyFilters();
      msg && (msg.textContent = `Carregados ${allRows.length} contrato(s).`);
      const f = j.filtros || {};
      const fmtBR = (s)=> s? s.split('-').reverse().join('/') : '';
      foot && (foot.textContent = `Período: ${fmtBR(f.dataVigenciaInicialMin)} a ${fmtBR(f.dataVigenciaInicialMax)}`);
    }catch(e){ console.error(e); tbody.innerHTML = '<tr><td colspan="17">Erro ao consultar.</td></tr>'; msg && (msg.textContent='Falha ao carregar.'); }
    finally{ delete pane.dataset.loading; }
  }

  pane.dataset.bound='1';
  load();
  setTimeout(()=>{ window.initContratosListaSort && window.initContratosListaSort(); }, 0);
}

// Ordenação para Listar Contratos
(function(){
  function parseMoneyBR(txt){ return Number(String(txt||'').replace(/[^0-9,-]/g,'').replace(/\./g,'').replace(',','.'))||0; }
  function keyForCell(td, type){
    const raw = (td?.innerText||'').trim();
    switch(type){
      case 'rownum': return parseInt(raw.replace(/\D+/g,''),10)||0;
      case 'money': return parseMoneyBR(raw);
      case 'date': {
        const m=raw.match(/(\d{2})\/(\d{2})\/(\d{4})/); if(!m) return raw.toLowerCase();
        return new Date(`${m[3]}-${m[2]}-${m[1]}T00:00:00`).getTime();
      }
      case 'numero': return parseInt(raw.replace(/\D+/g,''),10)||0;
      default: return raw.toLowerCase();
    }
  }
  window.initContratosListaSort = function(){
    const table = document.querySelector('#clTable'); if(!table || table.dataset.sortReady==='1') return; table.dataset.sortReady='1';
    const thead=table.tHead, tbody=table.tBodies[0]; if(!thead||!tbody) return;
    function clearIcons(){ thead.querySelectorAll('.sort-ind').forEach(s=>s.textContent=''); }
    function setIcon(th,dir){ let ind=th.querySelector('.sort-ind'); if(!ind){ ind=document.createElement('span'); ind.className='sort-ind'; th.appendChild(ind);} ind.textContent=(dir===1?'▲':'▼'); }
    let lastTh=null, lastDir=1;
    thead.addEventListener('click', (e)=>{
      const th=e.target.closest('th'); if(!th) return;
      const idx=[...th.parentNode.children].indexOf(th);
      const type=th.dataset.sort||'text';
      const rows=[...tbody.querySelectorAll('tr')];
      const keyed=rows.map(tr=>({tr,key:keyForCell(tr.children[idx],type)}));
      const dir=(lastTh===th && lastDir===1)? -1 : 1;
      keyed.sort((A,B)=> (A.key<B.key?-1:(A.key>B.key?1:0)) * dir);
      const frag=document.createDocumentFragment(); keyed.forEach(k=>frag.appendChild(k.tr));
      tbody.appendChild(frag);
      // renumerar col Ord
      tbody.querySelectorAll('tr').forEach((tr,i)=>{ const cell=tr.querySelector('.rownum'); if(cell) cell.textContent=String(i+1); });
      lastTh=th; lastDir=dir; clearIcons(); setIcon(th,dir);
    });
  }
})();

function initUGsSort(){
  const t = document.getElementById('tUGs');
  if (!t || t.dataset.sortReady==='1') return; t.dataset.sortReady='1';
  const thead=t.tHead, tbody=t.tBodies[0]; if(!thead||!tbody) return;
  function keyForRow(tr,colIndex,type){
    const raw=(tr.children[colIndex]?.innerText||'').trim();
    if(type==='rownum') return [...tbody.children].indexOf(tr);
    if(type==='numero') return parseInt(raw.replace(/\D+/g,''),10)||0;
    return raw.toLowerCase();
  }
  const cmp=(a,b)=> a<b? -1 : a>b? 1 : 0;
  let lastTh=null, lastDir=1;
  function clearIcons(){ thead.querySelectorAll('.sort-ind').forEach(s=>s.textContent=''); }
  function setIcon(th,dir){ const ind = th.querySelector('.sort-ind'); if (ind) ind.textContent = (dir===1?'▲':'▼'); }
  thead.addEventListener('click', (e)=>{
    const th=e.target.closest('th'); if(!th||th.classList.contains('noclick')) return;
    const type=th.dataset.sort; if(!type) return;
    const colIndex=[...th.parentNode.children].indexOf(th);
    const rows=[...tbody.querySelectorAll('tr')];
    const keyed=rows.map(tr=>({tr, key:keyForRow(tr,colIndex,type)}));
    const dir=(lastTh===th && lastDir===1)? -1 : 1;
    keyed.sort((A,B)=>cmp(A.key,B.key)*dir);
    const frag=document.createDocumentFragment(); keyed.forEach(k=>frag.appendChild(k.tr));
    tbody.appendChild(frag); lastTh=th; lastDir=dir; clearIcons(); setIcon(th,dir);
    // renumerar
    tbody.querySelectorAll('tr').forEach((tr,i)=>{ if(tr.children[0]) tr.children[0].textContent = String(i+1); });
  });
}

// ===== Contratos (form + listagem simples)
function initContratosTab(){
  const pane = document.getElementById('contratos');
  if(!pane || pane.dataset.bound==='1') return;
  const msg = pane.querySelector('#contratosMsg');
  const foot = pane.querySelector('#contratosFooter');
  const tBodyMain = pane.querySelector('#cPrincipal tbody');
  const tBodySel = pane.querySelector('#cSel tbody');
  const sumEl = pane.querySelector('#cSumTotal');
  const cFilterWrap = pane.querySelector('#cCompraFilter');
  const cContratoWrap = pane.querySelector('#cContratoFilter');
  const txtSearch = pane.querySelector('#cTxtSearch');
  const btnClearSearch = pane.querySelector('#cBtnClearSearch');
  const filterCount = pane.querySelector('#cFilterCount');
  const selAll = pane.querySelector('#cSelAll');
  const copyBtn = pane.querySelector('#cBtnCopy');
  const printBtn = pane.querySelector('#cBtnPrint');
  const copyMsg = pane.querySelector('#cCopyMsg');
  if(!tBodyMain || !tBodySel) return;

  let allItens = [];
  let activeCompras = new Set();
  let activeContratos = new Set();
  let textQuery = '';

  function recalcTotal(){ const tot=[...tBodySel.querySelectorAll('tr')].reduce((s,tr)=> s + (Number(tr.dataset.tot||0)), 0); if(sumEl) sumEl.textContent = fmtBRL(tot); }
  function renumberMain(){ [...tBodyMain.querySelectorAll('tr')].forEach((tr,i)=>{ const cell=tr.querySelector('.rownum'); if(cell) cell.textContent=String(i+1); }); }

  function makeRowData(rec){
    const compra = [rec.numeroCompra, rec.anoCompra].filter(Boolean).join('/');
    const item = rec.numeroItem || rec.codigoItem || '';
    const desc = rec.descricaoIitem || rec.descricaoItem || '';
    const forn = rec.nomeRazaoSocialFornecedor || '';
    const ni = rec.niFornecedor || '';
    const qtd = Number(rec.quantidadeItem||0);
    const vu = Number(rec.valorUnitarioItem||0);
    const vt = Number(rec.valorTotalItem||0);
    const key = `${compra}|${item}|${ni}`;
    const vig = `${(rec.dataVigenciaInicial||'').slice(0,10).split('-').reverse().join('/')} à ${(rec.dataVigenciaFinal||'').slice(0,10).split('-').reverse().join('/')}`;
    const modalidade = rec.nomeModalidadeCompra || '';
    const processo = rec.processo || '';
    const objeto = rec.objeto || '';
    const vglobal = Number(rec.valorGlobal||0);
    const inclusao = fmtDateBR(rec.dataHoraInclusao);
    const idCompra = rec.idCompra || '';
    return {compra,item,desc,forn,ni,qtd,vu,vt,vig,key, contrato: rec.numeroContrato||'', modalidade, processo, objeto, vglobal, inclusao, idCompra};
  }

  function addSelRow(d){
    if (tBodySel.querySelector(`tr[data-rowid="${CSS.escape(d.key)}"]`)) return;
    const tr=document.createElement('tr'); tr.dataset.rowid=d.key;
    const init=Math.min(1, Math.max(0, d.qtd));
    tr.innerHTML = `
      <td>${d.compra||'—'}</td>
      <td>${d.contrato||'—'}</td>
      <td class="nowrap">${d.item||'—'}</td>
      <td class="left">${d.desc||''}</td>
      <td class="left">${d.forn||''}<div class="small">CNPJ: ${maskCNPJ(d.ni)||'—'}</div></td>
      <td class="right">${(d.qtd||0).toLocaleString('pt-BR')}</td>
      <td class="right">${fmtBRL(d.vu||0)}</td>
      <td class="right"><input type="number" min="1" max="${d.qtd||0}" step="1" value="${init}" class="qtdBuy"></td>
      <td class="right totCell"></td>
      <td><button class="btn-mini btnDel" title="Remover">×</button></td>`;
    const input=tr.querySelector('.qtdBuy'), totCell=tr.querySelector('.totCell');
    function update(){ let q=parseInt(input.value,10); if(!Number.isInteger(q)||q<1) q=1; if(q>(d.qtd||0)) q=(d.qtd||0); input.value=q; const tot=q*(d.vu||0); tr.dataset.tot=String(tot); totCell.textContent=fmtBRL(tot); recalcTotal(); }
    input.addEventListener('input', update); input.addEventListener('blur', update); update();
    tr.querySelector('.btnDel').addEventListener('click', ()=>{
      const cb = tBodyMain.querySelector(`input.sel[data-rowid="${CSS.escape(d.key)}"]`);
      if (cb){ cb.checked=false; cb.closest('tr')?.classList?.remove('selrow'); }
      tr.remove(); recalcTotal();
    });
    tBodySel.appendChild(tr);
  }

  function removeSelRow(key){ const tr=tBodySel.querySelector(`tr[data-rowid="${CSS.escape(key)}"]`); if(tr){ tr.remove(); recalcTotal(); } }

  function renderMain(list){
    tBodyMain.innerHTML='';
    list.forEach((rec,idx)=>{
      const d = makeRowData(rec);
      const tr=document.createElement('tr'); tr.dataset.compra=d.compra; tr.dataset.item=d.item; tr.dataset.rowid=d.key;
      tr.innerHTML = `
        <td class="checkcol"><input type="checkbox" class="sel" data-rowid="${d.key}" data-compra="${d.compra}" data-contrato="${d.contrato||''}" data-item="${d.item}" data-desc="${d.desc}" data-forn="${d.forn}" data-ni="${d.ni}" data-qtd="${d.qtd}" data-vu="${d.vu}"></td>
        <td class="rownum">${idx+1}</td>
        <td>${d.contrato||'—'}</td>
        <td>${d.modalidade||'—'}</td>
        <td><div><strong>${d.compra||'—'}</strong></div><div class="small">idCompra: ${d.idCompra||'—'}</div></td>
        <td class="nowrap">${d.item||'—'}</td>
        <td>${d.desc||''}</td>
        <td>${d.forn||''}<div class="small">CNPJ: ${maskCNPJ(d.ni)||'—'}</div></td>
        <td class="right">${(d.qtd||0).toLocaleString('pt-BR')}</td>
        <td class="right">${fmtBRL(d.vu||0)}</td>
        <td class="right">${fmtBRL(d.vt||0)}</td>
        <td>${d.vig||''}</td>
        <td>${d.processo||'—'}${d.inclusao? `<div class="small">Incluído: ${d.inclusao}</div>`:''}</td>
        <td class="center nowrap">${d.ni? `<a href="api/certidao.php?cnpj=${d.ni}" target="_blank" rel="noopener" title="Certidão TCU"><i class="bi bi-book"></i></a>` : '—'}</td>
        `;
      tBodyMain.appendChild(tr);
    });
    renumberMain();
  }

  function applyFilters(){
    let list = allItens.slice();
    if (activeCompras.size){ list = list.filter(r=> activeCompras.has([r.numeroCompra,r.anoCompra].filter(Boolean).join('/')) ); }
    if (textQuery){ const q=textQuery.toLowerCase(); list = list.filter(r=>
      String(r.descricaoIitem||r.descricaoItem||'').toLowerCase().includes(q) ||
      String(r.nomeRazaoSocialFornecedor||'').toLowerCase().includes(q)
    ); }
    if (activeContratos.size){ list = list.filter(r=> activeContratos.has(String(r.numeroContrato||''))); }
    filterCount && (filterCount.textContent = `(${list.length} de ${allItens.length})`);
    renderMain(list);
  }

  function buildCompraFilter(arr){
    const counts = new Map();
    arr.forEach(it=>{ const key=[it.numeroCompra,it.anoCompra].filter(Boolean).join('/'); if(key) counts.set(key,(counts.get(key)||0)+1); });
    const keys = Array.from(counts.keys()).sort((a,b)=>{ const [ca,aa]=a.split('/').map(n=>parseInt(n||'0',10)); const [cb,ab]=b.split('/').map(n=>parseInt(n||'0',10)); if(aa!==ab) return aa-b; return ca-cb; });
    let html = '<span class="group-title">Filtrar por Compra:</span>';
    keys.forEach(k=>{ const c=counts.get(k)||0; const id='cf2_'+k.replace(/\D+/g,'_'); html += `<label for="${id}"><input type="checkbox" class="ccompra-chk" id="${id}" data-key="${k}"><span>${k}</span> <span class="small">(${c})</span></label>`; });
    html += `<span class="actions small">| <span class="link" id="cCfClear">limpar</span></span>`;
    cFilterWrap.innerHTML = html;
    cFilterWrap.addEventListener('change', (e)=>{ const cb=e.target && e.target.matches('input.ccompra-chk')? e.target:null; if(!cb)return; const key=cb.dataset.key||''; if(!key)return; if(cb.checked) activeCompras.add(key); else activeCompras.delete(key); applyFilters(); });
    cFilterWrap.querySelector('#cCfClear')?.addEventListener('click', ()=>{ activeCompras.clear(); cFilterWrap.querySelectorAll('input.ccompra-chk').forEach(i=> i.checked=false); applyFilters(); });
  }
  function buildContratoFilter(arr){
    if (!cContratoWrap) return;
    const counts = new Map();
    arr.forEach(it=>{ const key=String(it.numeroContrato||''); if(key) counts.set(key,(counts.get(key)||0)+1); });
    const keys = Array.from(counts.keys()).sort((a,b)=> a.localeCompare(b, 'pt-BR', {numeric:true, sensitivity:'base'}));
    let html = '<span class="group-title">Filtrar por Contrato:</span>';
    keys.forEach(k=>{ const c=counts.get(k)||0; const id='ct2_'+k.replace(/\W+/g,'_'); html += `<label for="${id}"><input type="checkbox" class="ccontrato-chk" id="${id}" data-key="${k}"><span>${k}</span> <span class="small">(${c})</span></label>`; });
    html += `<span class="actions small">| <span class="link" id="cCtClear">limpar</span></span>`;
    cContratoWrap.innerHTML = html;
    cContratoWrap.addEventListener('change', (e)=>{ const cb=e.target && e.target.matches('input.ccontrato-chk')? e.target:null; if(!cb)return; const key=cb.dataset.key||''; if(!key)return; if(cb.checked) activeContratos.add(key); else activeContratos.delete(key); applyFilters(); });
    cContratoWrap.querySelector('#cCtClear')?.addEventListener('click', ()=>{ activeContratos.clear(); cContratoWrap.querySelectorAll('input.ccontrato-chk').forEach(i=> i.checked=false); applyFilters(); });
  }

  // seleção
  pane.querySelector('#cPrincipal')?.addEventListener('change', (e)=>{
    const cb = e.target && e.target.matches('tbody input.sel') ? e.target : null; if(!cb) return;
    const data = { rowid: cb.dataset.rowid, compra: cb.dataset.compra, contrato: cb.dataset.contrato||'', item: cb.dataset.item, desc: cb.dataset.desc, forn: cb.dataset.forn, ni: cb.dataset.ni, qtd: cb.dataset.qtd, vu: cb.dataset.vu };
    if (cb.checked){ cb.closest('tr')?.classList?.add('selrow'); addSelRow(makeRowData({ numeroCompra:data.compra?.split('/')[0], anoCompra:data.compra?.split('/')[1], numeroContrato:data.contrato, numeroItem:data.item, descricaoItem:data.desc, nomeRazaoSocialFornecedor:data.forn, niFornecedor:data.ni, quantidadeItem:data.qtd, valorUnitarioItem:data.vu })); }
    else { cb.closest('tr')?.classList?.remove('selrow'); removeSelRow(data.rowid); }
  });
  selAll?.addEventListener('change', ()=>{ pane.querySelectorAll('#cPrincipal tbody input.sel').forEach(cb=>{ if(cb.checked!==selAll.checked){ cb.checked=selAll.checked; cb.dispatchEvent(new Event('change',{bubbles:true})); } }); });

  // busca texto
  txtSearch?.addEventListener('input', ()=>{ textQuery = txtSearch.value||''; applyFilters(); });
  btnClearSearch?.addEventListener('click', ()=>{ if(txtSearch){ txtSearch.value=''; textQuery=''; applyFilters(); } });

  // copiar selecionados
  copyBtn?.addEventListener('click', async ()=>{
    const src=pane.querySelector('#cSel'); if(!src) return;
    const hdr=[...src.querySelectorAll('thead th')].map(th=>th.innerText.trim());
    const rows=[...src.querySelectorAll('tbody tr')];
    const totalTxt = pane.querySelector('#cSumTotal')?.innerText?.trim() || 'R$ 0,00';
    const td=(txt,tag='td',align='left')=>`<${tag} style="border:1px solid #ccc; padding:6px; text-align:${align}; vertical-align:top;">${txt}</${tag}>`;
    let html = `<meta charset="utf-8"><table style="border-collapse:collapse; border:1px solid #ccc; font-family:Arial,Helvetica,sans-serif; font-size:13px;"><thead><tr>${hdr.map(h=>td(h,'th')).join('')}</tr></thead><tbody>`;
    rows.forEach(tr=>{ const tds=tr.querySelectorAll('td'); const compra=tds[0].innerText.trim(); const item=tds[1].innerText.trim(); const desc=tds[2].innerText.trim(); const forn=tds[3].childNodes[0].textContent.trim(); const idRaw=(tds[3].querySelector('.small')?.innerText||''); const idOnly=idRaw.replace(/^(?:NI|CNPJ)\s*:\s*/i,'').trim(); const cnpjFmt=idOnly? idOnly : '—'; const qtdDisp=tds[4].innerText.trim(); const vUnit=tds[5].innerText.trim(); const qBuy=tds[6].querySelector('input')?.value||''; const tot=tds[7].innerText.trim(); html += `<tr>${td(compra)}${td(item)}${td(`${desc}<div style='color:#666;font-size:12px'>CNPJ: ${cnpjFmt}</div>`)}${td(forn)}${td(qtdDisp,'td','right')}${td(vUnit,'td','right')}${td(qBuy,'td','right')}${td(tot,'td','right')}${td('')}</tr>`; });
    html += `</tbody><tfoot><tr>${td('TOTAL','th','right')}<td colspan="6"></td>${td(totalTxt,'th','right')}<td></td></tr></tfoot></table>`;
    try{
      if(navigator.clipboard && window.ClipboardItem){
        const data={'text/html': new Blob([html],{type:'text/html'}), 'text/plain': new Blob([html],{type:'text/plain'})};
        await navigator.clipboard.write([new ClipboardItem(data)]);
        copyMsg && (copyMsg.textContent='Tabela (formatada) copiada para a área de transferência.');
      } else {
        const div=document.createElement('div'); div.contentEditable='true'; div.style.position='fixed'; div.style.left='-99999px'; div.innerHTML=html; document.body.appendChild(div);
        const range=document.createRange(); range.selectNodeContents(div); const sel=window.getSelection(); sel.removeAllRanges(); sel.addRange(range); document.execCommand('copy'); document.body.removeChild(div);
        copyMsg && (copyMsg.textContent='Tabela (formatada) copiada para a área de transferência.');
      }
    }catch(e){ console.error(e); copyMsg && (copyMsg.textContent='Não foi possível copiar a tabela.'); }
    setTimeout(()=>{ if(copyMsg) copyMsg.textContent=''; }, 3500);
  });
  printBtn?.addEventListener('click', ()=>{
    const src=pane.querySelector('#cSel'); if(!src) return; const clone=src.cloneNode(true); clone.querySelectorAll('td:last-child, th:last-child').forEach(n=>n.remove()); const win=window.open('', '_blank'); if(!win) return; win.document.write(`<html><head><meta charset='utf-8'><title>Itens selecionados</title><style>table{border-collapse:collapse;width:100%;font:13px Arial} th,td{border:1px solid #ccc;padding:6px;} th{text-align:left;background:#f7f7f7}</style></head><body></body></html>`); win.document.body.appendChild(clone); win.focus(); win.print(); setTimeout(()=>win.close(), 500);
  });

  async function load(){
    if (pane.dataset.loading==='1') return; pane.dataset.loading='1';
    msg && (msg.textContent = 'Carregando itens...');
    tBodyMain.innerHTML='';
    try{
      const r = await fetch('api/contratos.php');
      const j = await r.json();
      if (j.error){ tBodyMain.innerHTML = `<tr><td colspan="10">Erro: ${String(j.error)}</td></tr>`; msg && (msg.textContent = 'Falha ao consultar.'); return; }
      allItens = j.contratos || [];
      buildCompraFilter(allItens);
      buildContratoFilter(allItens);
      applyFilters();
      msg && (msg.textContent = `Carregados ${allItens.length} registro(s).`);
      foot && (foot.textContent = `Período: ${j.filtros?.dataVigenciaInicialMin||''} a ${j.filtros?.dataVigenciaInicialMax||''}`);
    }catch(e){ tBodyMain.innerHTML = '<tr><td colspan="10">Falha ao consultar.</td></tr>'; msg && (msg.textContent = 'Erro ao consultar.'); }
    finally{ delete pane.dataset.loading; }
  }

  pane.dataset.bound='1';
  load();
}

// ===== Navegação por abas (navbar agrupada) =====
document.addEventListener('DOMContentLoaded', () => {
  try {
    const qs = (sel) => document.querySelector(sel);
    const qsa = (sel) => Array.from(document.querySelectorAll(sel));
    function activateTabByHash() {
      const hash = location.hash && document.getElementById(location.hash.slice(1)) ? location.hash : '#ugs';
      const trg = qs(`[data-bs-toggle="tab"][href="${hash}"]`) || qs(`[data-bs-toggle="tab"][data-bs-target="${hash}"]`);
      if (trg && window.bootstrap && bootstrap.Tab) {
        const t = bootstrap.Tab.getOrCreateInstance(trg);
        t.show();
      }
    }
    // Atualiza estilos ativos e hash
    qsa('[data-bs-toggle="tab"]').forEach(el => {
      el.addEventListener('shown.bs.tab', (ev) => {
        const targetHash = ev.target.getAttribute('href') || ev.target.getAttribute('data-bs-target');
        if (targetHash && location.hash !== targetHash) {
          history.replaceState(null, '', targetHash);
        }
        // Clear
        qsa('.navbar .nav-link').forEach(n => n.classList.remove('active'));
        qsa('.navbar .dropdown-item').forEach(n => n.classList.remove('active'));
        // Set current
        ev.target.classList.add('active');
        const dd = ev.target.closest('.dropdown');
        if (dd) dd.querySelector('.dropdown-toggle')?.classList?.add('active');
      }, { passive: true });
    });
    // inicializa pela hash
    activateTabByHash();
  } catch (e) {
    console.warn('Init navbar/tabs falhou:', e);
  }
});
