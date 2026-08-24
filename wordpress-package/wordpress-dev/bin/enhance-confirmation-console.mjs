import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');

if (!source.includes('keleva-confirmation-sheet-v1')) {
  const css = `/* keleva-confirmation-sheet-v1 */.mk-confirm-scrim{position:fixed;inset:0;z-index:200;display:grid;place-items:end center;padding:18px;background:rgba(23,21,18,.48)}.mk-confirm-dialog{width:min(540px,100%);padding:20px;border:1px solid var(--line);border-radius:20px;background:var(--surface);box-shadow:0 22px 60px rgba(15,14,12,.28)}.mk-confirm-dialog h2{margin:0;font-size:22px;letter-spacing:-.05em}.mk-confirm-dialog p{margin:8px 0 0;color:var(--muted);font-size:13px;line-height:1.45}.mk-confirm-dialog .mk-actions{justify-content:flex-end}@media(max-width:700px){.mk-confirm-scrim{align-items:end;padding:0}.mk-confirm-dialog{border-radius:20px 20px 0 0;padding:20px 16px calc(16px + env(safe-area-inset-bottom))}.mk-confirm-dialog .mk-actions{display:grid;grid-template-columns:1fr;margin-top:18px}.mk-confirm-dialog button{min-height:48px}}`;
  const script = `<script>/* keleva-confirmation-sheet-v1 */function confirmDialog(message){return new Promise(resolve=>{const prior=document.activeElement,root=document.createElement('div');root.className='mk-confirm-scrim';root.innerHTML='<section class="mk-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="mk-confirm-title" aria-describedby="mk-confirm-copy"><div class="mk-kicker">Confirmation</div><h2 id="mk-confirm-title">Continuer cette action ?</h2><p id="mk-confirm-copy"></p><div class="mk-actions"><button type="button" id="mk-dialog-cancel" class="mk-secondary">Annuler</button><button type="button" id="mk-dialog-confirm" class="mk-primary">Oui, continuer</button></div></section>';root.querySelector('#mk-confirm-copy').textContent=message;const close=value=>{document.removeEventListener('keydown',keys);root.remove();prior?.focus?.();resolve(value)},keys=event=>{if(event.key==='Escape'){event.preventDefault();close(false)}};document.addEventListener('keydown',keys);root.addEventListener('click',event=>{if(event.target===root)close(false)});root.querySelector('#mk-dialog-cancel').onclick=()=>close(false);root.querySelector('#mk-dialog-confirm').onclick=()=>close(true);document.body.appendChild(root);root.querySelector('#mk-dialog-cancel').focus()})}</script>`;
  const replacements = [
    ["if(!confirm(statusValue==='publish'?'Ce produit sera visible par vos clients. Continuer ?':'Ce produit ne sera plus visible par vos clients. Continuer ?'))return;", "if(!await confirmDialog(statusValue==='publish'?'Ce produit sera visible par vos clients. Continuer ?':'Ce produit ne sera plus visible par vos clients. Continuer ?'))return;"],
    ["if(!confirm('Supprimer cette catégorie ? Les produits ne seront pas supprimés.'))return;", "if(!await confirmDialog('Supprimer cette catégorie ? Les produits ne seront pas supprimés.'))return;"],
    ["if(!confirm('Supprimer ce code de réduction ?'))return;", "if(!await confirmDialog('Supprimer ce code de réduction ?'))return;"],
  ];
  for (const [before, after] of replacements) {
    if (!source.includes(before)) throw new Error(`Confirmation native introuvable : ${before}`);
    source = source.replace(before, after);
  }
  source = source.replace('</style>', `${css}</style>`);
  source = source.replace('<!-- /wp:html -->', `${script}\n<!-- /wp:html -->`);
  await writeFile(sourcePath, source, 'utf8');
}

console.log('Patch confirmations console : OK');
