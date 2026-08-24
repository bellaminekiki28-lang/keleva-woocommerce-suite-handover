import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');
const before = "function showPanel(html){q('#mk-panel').innerHTML=html;q('#mk-panel').scrollIntoView({behavior:'smooth',block:'start'})}";
const after = "function showPanel(html){const panel=q('#mk-panel');panel.innerHTML=html;panel.setAttribute('tabindex','-1');panel.scrollIntoView({behavior:'smooth',block:'start'});requestAnimationFrame(()=>{const target=panel.querySelector('button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[href]');(target||panel).focus({preventScroll:true})})}";

if (source.includes(before)) {
  source = source.replace(before, after);
}
if (!source.includes('keleva-sheet-focus-observer-v1')) {
  const observer = `<script>/* keleva-sheet-focus-observer-v1 */(()=>{const panel=document.querySelector('#mk-panel');if(!panel)return;const focusPanel=()=>requestAnimationFrame(()=>{const target=panel.querySelector('button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[href]');if(target)target.focus({preventScroll:true})});new MutationObserver(records=>{if(records.some(record=>record.type==='childList'&&panel.children.length))focusPanel()}).observe(panel,{childList:true,subtree:false})})();</script>`;
  source = source.replace('<!-- /wp:html -->', `${observer}\n<!-- /wp:html -->`);
}
await writeFile(sourcePath, source, 'utf8');
if (!source.includes('function showPanel(html){const panel=')) throw new Error('Correctif focus des feuilles introuvable.');
console.log('Focus feuilles mobile : OK');
