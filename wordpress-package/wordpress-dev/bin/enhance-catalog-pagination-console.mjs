import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');

if (!source.includes('keleva-catalog-pagination-v1')) {
  source = source.replace("let summary,active,config,filter='all',creator", "let summary,active,config,filter='all',creator,catalogPage=1,catalogSearch='',catalogSearchTimer");
  const oldList = /function listProducts\(\)\{.*?\}\nfunction renderHome\(\)/s;
  const newList = `/* keleva-catalog-pagination-v1 */
function catalogRoute(){const p=new URLSearchParams({page:String(catalogPage),per_page:'24'});if(catalogSearch)p.set('search',catalogSearch);if(filter!=='all')p.set('status',filter);return '/summary?'+p.toString()}
async function reloadCatalog(){summary=await api(catalogRoute());renderHome()}
function queueCatalogSearch(){catalogSearch=String(q('#mk-search')?.value||'').trim();catalogPage=1;window.clearTimeout(catalogSearchTimer);catalogSearchTimer=window.setTimeout(()=>reloadCatalog().catch(e=>note(e.message)),220)}
function listProducts(){const list=summary.products||[],pagination=summary.pagination||{},pager=(pagination.pages||1)>1?'<nav class="mk-pagination" aria-label="Pagination catalogue"><button class="mk-secondary" id="mk-catalog-previous" '+(pagination.page<=1?'disabled':'')+'>Précédent</button><span>Page '+Number(pagination.page||1)+' / '+Number(pagination.pages||1)+'</span><button class="mk-secondary" id="mk-catalog-next" '+(pagination.page>=pagination.pages?'disabled':'')+'>Suivant</button></nav>':'';q('#mk-products').innerHTML=(list.length?list.map(p=>'<button class="mk-product" data-product="'+p.id+'">'+image(p)+'<span class="mk-product-main"><span class="mk-product-name">'+esc(p.name)+'</span><span class="mk-product-meta">'+esc(p.category||'Sans catégorie')+' · '+(p.type==='variable'?'plusieurs versions':'un seul produit')+'</span><span class="mk-product-bottom"><b class="mk-price">'+money(p.price,p.currency)+'</b>'+status(p)+'</span></span></button>').join(''):'<div class="mk-empty">Aucun produit ici. Essayez une autre recherche, ou ajoutez votre premier produit.</div>')+pager;q('#mk-products').querySelectorAll('[data-product]').forEach(el=>el.onclick=()=>openProduct(el.dataset.product));q('#mk-catalog-previous')?.addEventListener('click',()=>{catalogPage=Math.max(1,catalogPage-1);reloadCatalog().catch(e=>note(e.message))});q('#mk-catalog-next')?.addEventListener('click',()=>{catalogPage+=1;reloadCatalog().catch(e=>note(e.message))})}
function renderHome()`;
  if (!oldList.test(source)) throw new Error('Bloc catalogue introuvable.');
  source = source.replace(oldList, newList);
  source = source.replace("b.onclick=()=>{filter=b.dataset.filter;renderHome()}", "b.onclick=()=>{filter=b.dataset.filter;catalogPage=1;reloadCatalog().catch(e=>note(e.message))}");
  source = source.replace("filter='draft';renderHome();q('#mk-products').scrollIntoView({behavior:'smooth',block:'center'})", "filter='draft';catalogPage=1;reloadCatalog().then(()=>q('#mk-products').scrollIntoView({behavior:'smooth',block:'center'}))");
  source = source.replace("summary=await api('/summary')", "summary=await api(catalogRoute())");
  source = source.replace("q('#mk-search').oninput=listProducts", "q('#mk-search').oninput=queueCatalogSearch");
  await writeFile(sourcePath, source, 'utf8');
}

console.log('Pagination catalogue console : OK');
