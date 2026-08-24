import { readFile, writeFile } from 'node:fs/promises';

const sourcePath = '/home/ubuntu/keleva-audit-tools/runtime/keleva-native-console.html';
let source = await readFile(sourcePath, 'utf8');

if (!source.includes('keleva-photo-progress-v1')) {
  const css = `/* keleva-photo-progress-v1 */.mk-photo-progress{display:flex;align-items:center;gap:7px;margin:9px 0 0;color:var(--muted);font-size:11px;font-weight:750}.mk-photo-progress[aria-busy="true"]{color:var(--blue)}.mk-photo-progress[aria-busy="true"]:before{content:"";width:11px;height:11px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:kelevaPhotoSpin .7s linear infinite}@keyframes kelevaPhotoSpin{to{transform:rotate(360deg)}}@media(prefers-reduced-motion:reduce){.mk-photo-progress[aria-busy="true"]:before{animation:none;border-right-color:currentColor}}`;
  const script = `<script>/* keleva-photo-progress-v1 */(()=>{const status=(text,busy=false)=>{const field=document.querySelector('.mk-photo');if(!field)return;let node=document.querySelector('#mk-photo-progress');if(!node){node=document.createElement('p');node.id='mk-photo-progress';node.className='mk-photo-progress';node.setAttribute('role','status');node.setAttribute('aria-live','polite');field.insertAdjacentElement('afterend',node)}node.textContent=text;node.setAttribute('aria-busy',busy?'true':'false')};document.addEventListener('change',event=>{if(event.target?.id==='mk-photo-file'&&event.target.files?.[0])status('Photo prête à être enregistrée.')},true);document.addEventListener('click',event=>{const action=event.target?.closest?.('#mk-save-photo,#mk-create-draft');if(!action||!document.querySelector('#mk-photo-file')?.files?.[0])return;status('Import de la photo en cours…',true);window.setTimeout(()=>{const message=document.querySelector('#mk-message')?.textContent||'';if(/photo|image/i.test(message))status(message,false)},0)},true)})();</script>`;
  source = source.replace('</style>', `${css}</style>`);
  source = source.replace('<!-- /wp:html -->', `${script}\n<!-- /wp:html -->`);
  await writeFile(sourcePath, source, 'utf8');
}

console.log('Patch progression photo console : OK');
