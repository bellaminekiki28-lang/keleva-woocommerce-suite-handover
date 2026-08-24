import { readFile, writeFile } from 'node:fs/promises';

const storefrontFiles = [
  '/home/ubuntu/keleva-local-wordpress/site/wp-content/plugins/keleva-woo-addons/assets/css/product-options.css',
  '/home/ubuntu/keleva-local-wordpress/site/wp-content/plugins/keleva-woo-addons/assets/css/restaurant-extras.css',
];
const aliases = [
  ['#fff5f1', 'color-mix(in srgb,var(--accent,#b9412d) 9%,var(--surface,#fffdf8))'],
  ['#fff3ef', 'color-mix(in srgb,var(--accent,#b9412d) 9%,var(--surface,#fffdf8))'],
  ['#1e1c19', 'var(--ink,#1e1c19)'],
  ['#282521', 'var(--ink,#1e1c19)'],
  ['#df432d', 'var(--accent-strong,#933121)'],
  ['#b73523', 'var(--accent-strong,#933121)'],
  ['#ff5b3e', 'var(--accent,#b9412d)'],
  ['#706a61', 'var(--muted,#68645d)'],
  ['#777168', 'var(--muted,#68645d)'],
  ['#62615c', 'var(--muted,#68645d)'],
  ['#9c998f', 'var(--subtle,#8e887e)'],
  ['#ebe5db', 'var(--line,#e8e2d7)'],
  ['#ded8ce', 'var(--line,#e8e2d7)'],
  ['#fff', 'var(--surface,#fffdf8)'],
];

for (const path of storefrontFiles) {
  let css = await readFile(path, 'utf8');
  if (css.includes('keleva-option-tokens-v1')) continue;
  for (const [color, token] of aliases) css = css.replaceAll(color, token);
  css = `/* keleva-option-tokens-v1: storefront colors inherit active Keleva palette. */\n${css}`;
  await writeFile(path, css, 'utf8');
}

const adminPath = '/home/ubuntu/keleva-local-wordpress/site/wp-content/plugins/keleva-woo-addons/assets/css/product-options-admin.css';
let adminCss = await readFile(adminPath, 'utf8');
if (!adminCss.includes('keleva-admin-option-tokens-v1')) {
  const colors = [...new Set([...adminCss.matchAll(/#[0-9a-fA-F]{3,8}\b/g)].map((match) => match[0].toLowerCase()))];
  const tokens = colors.map((color, index) => [`--keleva-admin-option-color-${index + 1}`, color]);
  for (const [token, color] of tokens) adminCss = adminCss.replaceAll(color, `var(${token})`);
  const definitions = tokens.map(([token, color]) => `${token}:${color};`).join('');
  adminCss = `/* keleva-admin-option-tokens-v1: single color source for native option admin. */\n:root{${definitions}}\n${adminCss}`;
  await writeFile(adminPath, adminCss, 'utf8');
}

console.log('Jetons CSS options : OK');
