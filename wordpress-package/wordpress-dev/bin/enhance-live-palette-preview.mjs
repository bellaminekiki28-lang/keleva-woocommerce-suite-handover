import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');

if (!source.includes('keleva-live-palette-preview-v1')) {
  const listener = "document.querySelectorAll('[data-appearance-palette]').forEach(b=>b.onclick=()=>confirmAppearance(data,b.dataset.appearancePalette))";
  const replacement = "document.querySelectorAll('[data-appearance-palette]').forEach(b=>{const preview=()=>previewStorefrontPalette(b.dataset.appearancePalette);b.onmouseenter=preview;b.onfocus=preview;b.onclick=()=>confirmAppearance(data,b.dataset.appearancePalette)})";
  if (!source.includes(listener)) throw new Error('Écouteurs palette introuvables.');
  source = source.replace(listener, replacement);
  const confirm = 'function confirmAppearance(data,id){';
  const preview = "/* keleva-live-palette-preview-v1 */function previewStorefrontPalette(id){const panel=q('#mk-panel');let frame=q('#mk-storefront-preview');if(!frame){frame=document.createElement('iframe');frame.id='mk-storefront-preview';frame.title='Aperçu storefront non enregistré';frame.loading='lazy';frame.style.cssText='width:100%;height:360px;border:1px solid var(--line);border-radius:14px;margin:14px 0;background:var(--paper)';panel.querySelector('.mk-palette-grid')?.insertAdjacentElement('afterend',frame)}frame.src='/?keleva_palette_preview='+encodeURIComponent(id)}" + confirm;
  if (!source.includes(confirm)) throw new Error('Confirmation palette introuvable.');
  source = source.replace(confirm, preview);
  await writeFile(sourcePath, source, 'utf8');
}

console.log('Aperçu storefront palette : OK');
