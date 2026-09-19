import fs from 'node:fs';
import {createHash} from 'node:crypto';
const dir='docs/research/2026-09-13-header-navigation-complete';
const sha=p=>createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const inputs=[['matrix',504],['boundaries',459],['editor/verify',72],['static',0]];
const rows=[],artifacts={},counts={};
for(const [name,expected] of inputs){
 const file=`${dir}/${name}.json`,data=JSON.parse(fs.readFileSync(file));
 artifacts[file]=sha(file);counts[name]={conditions:data.conditionCount||0,assertions:data.assertionCount};
 rows.push({name:name+':expected-condition-count',pass:(data.conditionCount||0)===expected});
 rows.push({name:name+':all-assertions-pass',pass:(data.rows||data.checks||[]).length===data.assertionCount&&(data.rows||data.checks||[]).every(r=>r.pass===true)});
 for(const row of data.rows||data.checks||[])rows.push({...row,name:name+':'+row.name});
}
const sourceDigests=JSON.parse(fs.readFileSync(`${dir}/static.json`)).sourceDigests;
for(const file of ['scripts/verify-header-navigation-complete.mjs','scripts/verify-header-navigation-boundaries.mjs','scripts/verify-header-navigation-editor.mjs','scripts/verify-header-navigation-static.mjs','scripts/summarize-header-navigation-complete.mjs'])sourceDigests[file]=sha(file);
for(const [file,expected]of Object.entries(sourceDigests))rows.push({name:'source-current:'+file,pass:sha(file)===expected});
const completed=rows.every(r=>r.pass===true);
const report={schema:'wt-header-navigation-complete.v1',completed,conditionCount:Object.values(counts).reduce((n,c)=>n+c.conditions,0),assertionCount:rows.length,counts,artifacts,sourceDigests,rows};
fs.writeFileSync(`${dir}/verify.json`,JSON.stringify(report,null,2)+'\n');
console.log(JSON.stringify({completed,conditions:report.conditionCount,assertions:report.assertionCount,failures:rows.filter(r=>!r.pass)}));
if(!completed)process.exitCode=1;
