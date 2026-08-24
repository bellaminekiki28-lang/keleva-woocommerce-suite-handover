import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');

source = source.replace(/\n\/\* keleva-category-tools-v1 \*\/[\s\S]*?(?=<\/script>)/, '\n');
if (!source.includes('keleva-category-tools-v2')) {
  const addon = `
/* keleva-category-tools-v2 */
const categoryToolsStyle=document.createElement('style');categoryToolsStyle.textContent='.mk-category-tools{display:flex;gap:7px;justify-content:flex-end;margin:-4px 0 12px}.mk-category-tools button{min-width:44px;min-height:40px}.mk-category-cover{display:grid;grid-template-columns:84px minmax(0,1fr);gap:13px;align-items:center;margin:0 0 16px;padding:12px;border:1px solid var(--line);border-radius:12px;background:var(--paper)}.mk-category-cover img,.mk-category-cover__empty{width:84px;height:64px;object-fit:cover;background:var(--media);border-radius:8px}.mk-category-cover__empty{display:grid;place-items:center;color:var(--muted);font-size:11px;text-align:center}.mk-category-cover input{width:100%;font-size:12px}@media(max-width:700px){.mk-category-tools{margin:6px 0 14px}.mk-category-cover{grid-template-columns:70px minmax(0,1fr)}.mk-category-cover img,.mk-category-cover__empty{width:70px;height:58px}}';document.head.appendChild(categoryToolsStyle);
async function uploadCategoryCover(id,file){const form=new FormData();form.append('image',file);const r=await fetch(base+'/categories/'+id+'/image',{method:'POST',credentials:'same-origin',headers:{'X-Keleva-CSRF':csrf(),'Origin':location.origin},body:form}),d=await r.json().catch(()=>({}));if(!r.ok)throw new Error(d.message||'La couverture ne peut pas être importée.');return d}
async function moveCategory(id,direction){const current=categories.findIndex(c=>Number(c.id)===Number(id)),target=current+direction;if(current<0||target<0||target>=categories.length)return;const next=[...categories],[item]=next.splice(current,1);next.splice(target,0,item);await categoryApi('/categories/order','POST',{ids:next.map(c=>c.id)});await loadCategories();renderCategories()}
function decorateCategoryTools(){const list=q('#mk-category-list');if(list)list.querySelectorAll('[data-category-open]').forEach(card=>{if(card.dataset.categoryToolsReady)return;card.dataset.categoryToolsReady='true';const id=card.dataset.categoryOpen,tools=document.createElement('div');tools.className='mk-category-tools';tools.innerHTML='<button class="mk-secondary" type="button" data-category-up="'+id+'" aria-label="Monter cette catégorie">↑ Monter</button><button class="mk-secondary" type="button" data-category-down="'+id+'" aria-label="Descendre cette catégorie">↓ Descendre</button>';card.insertAdjacentElement('afterend',tools);tools.querySelector('[data-category-up]').onclick=()=>moveCategory(id,-1).catch(e=>note(e.message));tools.querySelector('[data-category-down]').onclick=()=>moveCategory(id,1).catch(e=>note(e.message))});const editor=q('[data-category-id]');if(!editor||editor.dataset.categoryCoverReady)return;editor.dataset.categoryCoverReady='true';const id=editor.dataset.categoryId,category=categories.find(c=>Number(c.id)===Number(id));if(!category)return;const cover=document.createElement('section');cover.className='mk-category-cover';cover.innerHTML=(category.cover?.url?'<img src="'+esc(category.cover.url)+'" alt="Couverture actuelle">':'<div class="mk-category-cover__empty">Aucune couverture</div>')+'<label class="mk-label"><span>Image de couverture</span><input data-category-cover-input type="file" accept="image/jpeg,image/png,image/webp,image/avif"><small>JPG, PNG, WebP ou AVIF, 5 Mo maximum.</small></label>';editor.querySelector('.mk-panel-body')?.insertBefore(cover,editor.querySelector('.mk-grid2'));cover.querySelector('[data-category-cover-input]').onchange=async e=>{const file=e.target.files?.[0];if(!file)return;try{e.target.disabled=true;await uploadCategoryCover(id,file);await loadCategories();openCategory(id)}catch(err){message(err.message)}finally{e.target.disabled=false}}}
const categoryToolsObserver=new MutationObserver(()=>requestAnimationFrame(decorateCategoryTools));categoryToolsObserver.observe(q('#mk-panel'),{childList:true,subtree:true});
`;
  const categoryRenderer = source.indexOf('function renderCategories');
  if (categoryRenderer < 0) throw new Error('Rendu catégories introuvable.');
  const scriptEnd = source.indexOf('</script>', categoryRenderer);
  const iifeEnd = source.lastIndexOf('})();', scriptEnd);
  if (scriptEnd < 0 || iifeEnd < categoryRenderer) throw new Error('Fermeture de portée catégories introuvable.');
  source = `${source.slice(0, iifeEnd)}${addon}\n${source.slice(iifeEnd)}`;
}

await writeFile(sourcePath, source, 'utf8');
console.log('Portée outils catégories : OK');
