import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');
const before = "window.__kelevaSalesSummary=summary;window.__kelevaSalesOrders=orders;window.__kelevaSalesCoupons=coupons;const panel=q('#mk-panel');panel.innerHTML=";
const after = "window.__kelevaSalesSummary=summary;window.__kelevaSalesOrders=orders;window.__kelevaSalesCoupons=coupons;const panel=q('#mk-panel');delete panel.dataset.salesToolsReady;panel.innerHTML=";
if (source.includes(before)) {
  source = source.replace(before, after);
  await writeFile(sourcePath, source, 'utf8');
}
console.log('Réinitialisation ventes après filtre : OK');
