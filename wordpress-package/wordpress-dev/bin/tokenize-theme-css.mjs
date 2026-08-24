import { readFile, writeFile } from 'node:fs/promises';

const files = [
  '/home/ubuntu/keleva-local-wordpress/site/wp-content/themes/keleva-woo/style.css',
  '/home/ubuntu/keleva-local-wordpress/site/wp-content/themes/keleva-woo/assets/css/velora-parity.css',
];
const aliases = [
  ['#fffdf8', 'var(--surface)'],
  ['#f7f4ee', 'var(--bg)'],
  ['#1e1c19', 'var(--ink)'],
  ['#68645d', 'var(--muted)'],
  ['#8e887e', 'var(--subtle)'],
  ['#e8e2d7', 'var(--line)'],
  ['#b9412d', 'var(--accent)'],
  ['#933121', 'var(--accent-strong)'],
  ['#176b4d', 'var(--success)'],
  ['#e8ded0', 'var(--media)'],
  ['#e9e1d4', 'var(--benefit)'],
  ['#706a61', 'var(--muted)'],
  ['#67625a', 'var(--muted)'],
  ['#625d55', 'var(--muted)'],
  ['#9d978e', 'var(--subtle)'],
  ['#9e988f', 'var(--subtle)'],
  ['#9c968c', 'var(--subtle)'],
  ['#969087', 'var(--subtle)'],
  ['#928c82', 'var(--subtle)'],
  ['#78736b', 'var(--muted)'],
  ['#6e6a63', 'var(--muted)'],
  ['#5f5a52', 'var(--muted)'],
  ['#eee9df', 'var(--media)'],
  ['#ece6dc', 'var(--media)'],
  ['#ebe6dc', 'var(--line)'],
  ['#ded7ca', 'var(--line)'],
  ['#dcd5c8', 'var(--line)'],
  ['#f0b8ae', 'color-mix(in srgb,var(--accent) 35%,var(--surface))'],
  ['#fff5f1', 'color-mix(in srgb,var(--accent) 8%,var(--surface))'],
  ['#f3f1ec', 'color-mix(in srgb,var(--ink) 5%,var(--bg))'],
  ['#f6f3ed', 'color-mix(in srgb,var(--ink) 4%,var(--bg))'],
];

for (const path of files) {
  let css = await readFile(path, 'utf8');
  if (css.includes('keleva-theme-token-migration-v1')) continue;
  const rootMatch = css.match(/^\s*:root\s*\{[^}]*\}/);
  const rootTokens = rootMatch?.[0] || '';
  if (rootTokens) css = css.replace(rootTokens, '__KELEVA_ROOT_TOKENS__');
  css = css.replace(/(background(?:-color)?\s*:\s*)#fff\b/gi, '$1var(--surface)');
  css = css.replace(/(color\s*:\s*)#fff\b/gi, '$1var(--on-accent)');
  for (const [color, token] of aliases) css = css.replaceAll(color, token);
  if (rootTokens) css = css.replace('__KELEVA_ROOT_TOKENS__', rootTokens);
  css = `/* keleva-theme-token-migration-v1: palette-sensitive colors use central tokens. */\n${css}`;
  await writeFile(path, css, 'utf8');
}

console.log('Jetons CSS thème : OK');
