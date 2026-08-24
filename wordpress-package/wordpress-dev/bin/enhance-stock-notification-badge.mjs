import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');

const previous = "const alert=q('#mk-notifications-entry');if(alert&&Number(metrics.orders_awaiting||0)>0)alert.textContent='Notifications ('+Number(metrics.orders_awaiting)+')'";
const replacement = "/* keleva-stock-notification-badge-v1 */const alert=q('#mk-notifications-entry'),awaiting=Number(metrics.orders_awaiting||0),outOfStock=Number(metrics.out_of_stock||0),attention=awaiting+outOfStock;if(alert&&attention>0){alert.textContent='Notifications ('+attention+')';alert.setAttribute('aria-label','Notifications : '+awaiting+' commande(s) à préparer et '+outOfStock+' produit(s) en rupture de stock.')}";

if (!source.includes('keleva-stock-notification-badge-v1')) {
  if (!source.includes(previous)) throw new Error('Point d’injection du badge notifications introuvable.');
  source = source.replace(previous, replacement);
  await writeFile(sourcePath, source, 'utf8');
}

console.log('Badge notifications commandes et ruptures : OK');
