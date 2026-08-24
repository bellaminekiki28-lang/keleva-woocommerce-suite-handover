import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');

if (!source.includes('keleva-mobile-sheet-header-v1')) {
  const css = `/* keleva-mobile-sheet-header-v1 */@media(max-width:700px){#mk-panel .mk-panel-head{grid-template-columns:minmax(0,1fr)}#mk-panel .mk-panel-head>button{order:-1;justify-self:start;min-width:44px;min-height:44px}#mk-panel .mk-panel-head>div{min-width:0}#mk-panel .mk-panel-head h2{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}}`;
  source = source.replace('</style>', `${css}</style>`);
  await writeFile(sourcePath, source, 'utf8');
}

console.log('En-tête feuille mobile : OK');
