import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');
const before = "money=(v,c='EUR')=>new Intl.NumberFormat('fr-FR',{style:'currency',currency:c}).format(Number(v||0))";
const after = "money=(v,c=summary?.metrics?.currency||'EUR')=>new Intl.NumberFormat(document.documentElement.lang||'fr-FR',{style:'currency',currency:c}).format(Number(v||0))";

if (source.includes(before)) {
  source = source.replace(before, after);
  await writeFile(sourcePath, source, 'utf8');
}
if (!source.includes("summary?.metrics?.currency||'EUR'")) throw new Error('Correctif devise console introuvable.');
console.log('Devise WooCommerce console : OK');
