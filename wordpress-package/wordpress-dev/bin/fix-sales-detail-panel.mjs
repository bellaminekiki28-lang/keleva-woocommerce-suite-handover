import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');
const before = "showPanel(salesDetailMarkup(d.order));q('[data-order-detail-back]').onclick=openSales";
const after = "const detailPanel=q('#mk-panel');detailPanel.innerHTML=salesDetailMarkup(d.order);detailPanel.querySelector('button')?.focus();q('[data-order-detail-back]').onclick=openSales";
if (source.includes(before)) {
  source = source.replace(before, after);
  await writeFile(sourcePath, source, 'utf8');
}
console.log('Panneau détail ventes : OK');
