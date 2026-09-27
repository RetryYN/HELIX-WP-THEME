import { contentLab } from './lib/content-lab-env.mjs';
import fs from 'node:fs';import path from 'node:path';import os from 'node:os';import {execFileSync,spawnSync} from 'node:child_process';import {createHash} from 'node:crypto';
const out='docs/research/2026-09-14-header-navigation-editing';fs.mkdirSync(out,{recursive:true});
const php=code=>execFileSync('docker',['exec',contentLab.wpContainer,'php','-r',`require '/var/www/html/wp-load.php'; ${code}`],{encoding:'utf8'}).trim();
if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('dedicated lab required');
const snapshot=path.join(os.tmpdir(),'helix-header-editing-restore.json');if(fs.existsSync(snapshot))throw Error('Pending recovery must be resolved first');
const before=php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));');const rows=[];const check=(name,pass)=>{rows.push({name,pass:!!pass});if(!pass)throw Error(name);};
for(const [name,code]of [['signal',143],['rejection',1],['exception',1]]){
 const run=spawnSync(process.execPath,['scripts/verify-header-navigation-editing.mjs',`--test-${name}-cleanup`],{encoding:'utf8',timeout:60000});
 check(name+':expected-exit',run.status===code);check(name+':snapshot-removed',!fs.existsSync(snapshot));
 check(name+':full-settings-restored',php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));')===before);
 check(name+':owned-fixtures-removed',php('echo (int)(!get_page_by_path("header-editor-nav-a",OBJECT,"wp_navigation")&&!get_page_by_path("header-editor-nav-b",OBJECT,"wp_navigation"));')==='1');
}
for(let i=0;i<2;i++){execFileSync(process.execPath,['scripts/recover-header-navigation-editing.mjs']);check('idempotent:'+i,php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));')===before);}
const files=['scripts/verify-header-navigation-editing.mjs','scripts/recover-header-navigation-editing.mjs','scripts/verify-header-navigation-recovery.mjs'];const sourceDigests=Object.fromEntries(files.map(f=>[f,createHash('sha256').update(fs.readFileSync(f)).digest('hex')]));
fs.writeFileSync(out+'/recovery.json',JSON.stringify({completed:rows.every(r=>r.pass),conditionCount:4,assertionCount:rows.length,sourceDigests,rows},null,2)+'\n');console.log('recovery',rows.length,rows.filter(r=>!r.pass));
