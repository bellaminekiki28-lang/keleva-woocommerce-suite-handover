import { readFileSync, writeFileSync } from 'node:fs';

const runtimePath = process.argv[2] || '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = readFileSync(runtimePath, 'utf8');

if (source.includes('keleva-console-skeleton-v2')) {
  console.log('skeleton_patch=already-applied');
  process.exit(0);
}

source = source.replace(/<style id="keleva-console-skeleton-v1">[\s\S]*?<\/script>/, '');

const replacements = [
  ['async function reloadCatalog(){summary=await api(catalogRoute());renderHome()}', "async function reloadCatalog(){window.kelevaShowSkeleton?.('home');summary=await api(catalogRoute());renderHome();window.kelevaClearSkeleton?.('home')}"],
  ["async function load(){try{summary=await api(catalogRoute());", "async function load(){try{q('#mk-login').style.display='none';q('#mk-app').style.display='block';window.kelevaShowSkeleton?.('home');summary=await api(catalogRoute());"],
  ["async function load(){try{window.kelevaShowSkeleton?.('home');summary=await api(catalogRoute());", "async function load(){try{q('#mk-login').style.display='none';q('#mk-app').style.display='block';window.kelevaShowSkeleton?.('home');summary=await api(catalogRoute());"],
  ["q('#mk-logout').style.display='inline-block';renderHome();const audit=await api('/audit');", "q('#mk-logout').style.display='inline-block';renderHome();window.kelevaClearSkeleton?.('home');const audit=await api('/audit');"],
  ["async function openProduct(id){try{const p=await api('/products/'+id);active={id,p};showPanel(productPanel(p));", "async function openProduct(id){try{window.kelevaShowSkeleton?.('panel');const p=await api('/products/'+id);active={id,p};showPanel(productPanel(p));window.kelevaClearSkeleton?.('panel');"],
  ["async function openSales(){try{const [summary,o,c]=await Promise.all([salesApi('/summary'),salesApi('/orders?limit=20'),salesApi('/coupons')]);window.__kelevaOrderStatuses=o.statuses||{};renderSales(summary,o.orders||[],c.coupons||[]);", "async function openSales(){try{window.kelevaShowSkeleton?.('panel');const [summary,o,c]=await Promise.all([salesApi('/summary'),salesApi('/orders?limit=20'),salesApi('/coupons')]);window.__kelevaOrderStatuses=o.statuses||{};renderSales(summary,o.orders||[],c.coupons||[]);window.kelevaClearSkeleton?.('panel');"],
];

for (const [needle, replacement] of replacements) {
  if (source.includes(needle)) source = source.replace(needle, replacement);
}

const enhancement = String.raw`
<style id="keleva-console-skeleton-v2">
#mk-app{position:relative}.mk-skeleton-overlay{position:absolute;z-index:30;inset:0;display:grid;align-content:start;padding:24px;background:rgba(18,28,31,.97)}
.mk-skeleton{display:grid;gap:12px;padding:22px;border:1px solid rgba(255,255,255,.16);border-radius:18px;background:rgba(255,255,255,.05)}
.mk-skeleton__line,.mk-skeleton__card{display:block;border-radius:8px;background:linear-gradient(100deg,rgba(255,255,255,.08) 20%,rgba(255,255,255,.19) 38%,rgba(255,255,255,.08) 58%);background-size:200% 100%;animation:mk-skeleton-shimmer 1.25s linear infinite}
.mk-skeleton__line{height:14px;width:56%}.mk-skeleton__line--short{width:32%}.mk-skeleton__cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.mk-skeleton__card{height:86px}
@keyframes mk-skeleton-shimmer{to{background-position:-200% 0}}@media(prefers-reduced-motion:reduce){.mk-skeleton__line,.mk-skeleton__card{animation:none}}@media(max-width:620px){.mk-skeleton-overlay{padding:16px}.mk-skeleton__cards{grid-template-columns:1fr}.mk-skeleton{padding:16px}}
</style>
<script>/* keleva-console-skeleton-v2 */(()=>{const markup=kind=>'<section class="mk-skeleton" role="status" aria-live="polite" aria-label="Chargement en cours"><span class="screen-reader-text">Chargement en cours.</span><span class="mk-skeleton__line" aria-hidden="true"></span><span class="mk-skeleton__line mk-skeleton__line--short" aria-hidden="true"></span><div class="mk-skeleton__cards">'+Array.from({length:kind==='panel'?2:3},()=>'<span class="mk-skeleton__card" aria-hidden="true"></span>').join('')+'</div></section>';const target=kind=>kind==='panel'?document.querySelector('#mk-panel'):document.querySelector('#mk-app');const timers=new Map;const clear=kind=>{const node=target(kind),timer=timers.get(kind);if(timer)window.clearTimeout(timer);timers.delete(kind);document.querySelector('#mk-skeleton-overlay')?.remove();if(node)node.removeAttribute('aria-busy')};window.kelevaShowSkeleton=kind=>{const node=target(kind);if(!node)return;clear(kind);node.setAttribute('aria-busy','true');if(kind==='home'){const layer=document.createElement('div');layer.id='mk-skeleton-overlay';layer.className='mk-skeleton-overlay';layer.innerHTML=markup(kind);node.appendChild(layer)}else node.innerHTML=markup(kind);timers.set(kind,window.setTimeout(()=>{if(kind==='home'){clear(kind);const error=document.querySelector('#mk-error');if(error){error.setAttribute('role','alert');error.textContent='Le chargement prend plus de temps que prévu. Vérifiez votre connexion puis réessayez.'}}else if(node.getAttribute('aria-busy')==='true'){node.removeAttribute('aria-busy');node.innerHTML='<p class="mk-message" role="alert">Le chargement prend plus de temps que prévu. Vérifiez votre connexion puis réessayez.</p>'}},15000))};window.kelevaClearSkeleton=clear})();</script>`;

const insertionAnchor = source.includes('</body>') ? '</body>' : '<!-- /wp:html -->';
if (!source.includes(insertionAnchor)) throw new Error('Point d’insertion skeleton introuvable.');
source = source.replace(insertionAnchor, `${enhancement}\n${insertionAnchor}`);
writeFileSync(runtimePath, source);
console.log('skeleton_patch=applied');
