import { readFileSync } from 'node:fs';

const sourcePath = process.argv[2];
if (!sourcePath) {
  throw new Error('Usage: node validate-native-console.mjs <console.html>');
}

const source = readFileSync(sourcePath, 'utf8');
if (source.includes('sessionStorage') || source.includes('keleva_native_key') || source.includes('X-Keleva-Dashboard-Key')) {
  throw new Error('La console contient encore un mécanisme de clé navigateur interdit.');
}
if (!source.includes('/session/login') || !source.includes('/categories')) {
  throw new Error('La console ne contient pas les flux session ou catégories attendus.');
}

const scripts = [...source.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(match => match[1]);
if (scripts.length < 2) {
  throw new Error('Les deux scripts attendus de la console sont absents.');
}

scripts.forEach((script, index) => {
  new Function(script);
  console.log(`Script console ${index + 1}: syntaxe valide`);
});
