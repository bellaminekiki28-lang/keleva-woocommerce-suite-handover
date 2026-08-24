import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');
const previous = "panel.querySelector('.mk-palette-grid')?.insertAdjacentElement('afterend',frame)";
const replacement = "(panel.querySelector('.mk-palette-grid')||panel.querySelector('[data-appearance-palette]')?.parentElement)?.insertAdjacentElement('afterend',frame)";

if (source.includes(previous)) {
  source = source.replace(previous, replacement);
  await writeFile(sourcePath, source, 'utf8');
}

console.log('Montage aperçu storefront palette : OK');
