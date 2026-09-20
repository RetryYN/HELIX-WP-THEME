import fs from 'node:fs';import {spawnSync} from 'node:child_process';import assert from 'node:assert/strict';
const file='docs/research/2026-09-20-section-contract-poc/contract.mjs',source=fs.readFileSync(file,'utf8');
const cases=[
 ['01B','h4-becomes-section','![2,3].includes(b.level)','![2,3,4].includes(b.level)','01B exact boundaries'],
 ['01A','heading-derived-id',"b.level===2?b.stableId:`${parent}/${b.stableId}`","b.level===2?b.stableId:`${parent}/${b.stableId}-${b.text}`",'01A heading rename'],
 ['02B','outside-write-allowed','!allowed.has(c.key)||','false||','02B foreign block'],
 ['02A','move-offset-wrong','target.end-piece.length','target.end','02A move preserves'],
 ['02B','event-id-missing',"variantId:'demo-section-v1',",'','02B event invalid'],
 ['02A','post-override-ignored','state.postSlot||state.globalSlot','state.globalSlot','02A global nth']
];
const rows=[];
try{for(const [ac,name,from,to,grep]of cases){assert(source.includes(from),`Missing mutation ${name}`);fs.writeFileSync(file,source.replace(from,to));const result=spawnSync('npx',['playwright','test','tests/e2e/section-contract-poc-selection-catalog.spec.ts','--workers=1','--reporter=json','--grep',grep],{encoding:'utf8',maxBuffer:16*1024*1024,env:process.env});const report=JSON.parse(result.stdout);assert.equal(report.stats.unexpected,1,`${name} must fail exactly one test`);assert.equal(report.stats.expected+report.stats.skipped+report.stats.flaky,0);rows.push({name:`${ac} mutation ${name} rejected`,pass:true,unexpected:report.stats.unexpected});fs.writeFileSync(file,source);}}finally{fs.writeFileSync(file,source);}
console.log(JSON.stringify(rows));
