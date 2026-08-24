import { readFile, writeFile } from 'node:fs/promises';

const files = [
  '/home/ubuntu/keleva-local-wordpress/site/wp-content/themes/keleva-woo/style.css',
  '/home/ubuntu/keleva-local-wordpress/site/wp-content/themes/keleva-woo/assets/css/velora-parity.css',
];
const aliases = [
  ['rgba(30,28,25,.52)', 'color-mix(in srgb,var(--ink) 52%,transparent)'],
  ['rgba(30,28,25,.5)', 'color-mix(in srgb,var(--ink) 50%,transparent)'],
  ['rgba(30,28,25,.3)', 'color-mix(in srgb,var(--ink) 30%,transparent)'],
  ['rgba(30,28,25,.18)', 'color-mix(in srgb,var(--ink) 18%,transparent)'],
  ['rgba(75,62,43,.07)', 'color-mix(in srgb,var(--ink) 7%,transparent)'],
  ['rgba(75,62,43,.06)', 'color-mix(in srgb,var(--ink) 6%,transparent)'],
  ['rgba(75,62,43,.055)', 'color-mix(in srgb,var(--ink) 5.5%,transparent)'],
  ['rgba(255,91,62,.4)', 'color-mix(in srgb,var(--accent) 40%,transparent)'],
  ['rgba(255,91,62,.16)', 'color-mix(in srgb,var(--accent) 16%,transparent)'],
  ['rgba(255,253,248,.94)', 'color-mix(in srgb,var(--surface) 94%,transparent)'],
  ['rgba(255,253,248,.92)', 'color-mix(in srgb,var(--surface) 92%,transparent)'],
  ['rgba(255,253,248,.9)', 'color-mix(in srgb,var(--surface) 90%,transparent)'],
  ['rgba(255,253,248,.5)', 'color-mix(in srgb,var(--surface) 50%,transparent)'],
  ['rgba(247,244,238,.96)', 'color-mix(in srgb,var(--bg) 96%,transparent)'],
  ['rgba(247,244,238,.93)', 'color-mix(in srgb,var(--bg) 93%,transparent)'],
  ['rgba(232,226,215,.9)', 'color-mix(in srgb,var(--line) 90%,transparent)'],
  ['#cdc6b9', 'var(--line)'],
  ['#aaa39a', 'var(--subtle)'],
  ['#aaa398', 'var(--subtle)'],
  ['#a7a199', 'var(--subtle)'],
  ['#858078', 'var(--subtle)'],
  ['#817b72', 'var(--subtle)'],
  ['#777168', 'var(--muted)'],
  ['#e9b0a6', 'color-mix(in srgb,var(--accent) 55%,var(--surface))'],
  ['#57534d', 'var(--muted)'],
  ['#eadac5', 'var(--benefit)'],
  ['#d7b48e', 'var(--media)'],
  ['#8f6445', 'color-mix(in srgb,var(--accent) 30%,var(--ink))'],
  ['#f4ede3', 'color-mix(in srgb,var(--benefit) 55%,var(--surface))'],
];

for (const path of files) {
  let css = await readFile(path, 'utf8');
  if (css.includes('keleva-theme-token-residuals-v1')) continue;
  const rootMatch = css.match(/:root\s*\{[^}]*\}/);
  const rootTokens = rootMatch?.[0] || '';
  if (rootTokens) css = css.replace(rootTokens, '__KELEVA_ROOT_TOKENS__');
  for (const [color, token] of aliases) css = css.replaceAll(color, token);
  if (rootTokens) css = css.replace('__KELEVA_ROOT_TOKENS__', rootTokens);
  css = `/* keleva-theme-token-residuals-v1: shadow and translucent states inherit active palette. */\n${css}`;
  await writeFile(path, css, 'utf8');
}

console.log('Jetons CSS résiduels : OK');
