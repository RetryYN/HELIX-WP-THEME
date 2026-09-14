import fs from 'node:fs';import {createHash} from 'node:crypto';
const out='docs/research/2026-09-14-header-navigation-editing';const sha=file=>createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows=[],artifacts={},sourceDigests={},counts={};
for(const [name,expected]of [['verify',72],['scope',18],['recovery',4],['public-isolation',72]]){
 const file=`${out}/${name}.json`,data=JSON.parse(fs.readFileSync(file)),checks=data.rows||data.checks||[];artifacts[file]=sha(file);counts[name]={conditions:data.conditionCount,assertions:data.assertionCount};
 rows.push({name:name+':completed',pass:data.completed===true&&data.conditionCount===expected&&checks.length===data.assertionCount&&checks.every(r=>r.pass===true)});
 for(const row of checks)rows.push({...row,name:name+':'+row.name});
 for(const [source,hash]of Object.entries(data.sourceDigests)){rows.push({name:name+':source-current:'+source,pass:sha(source)===hash});sourceDigests[source]=hash;}
}
const baseline='docs/research/2026-09-13-header-navigation-complete/verify.json',base=JSON.parse(fs.readFileSync(baseline));artifacts[baseline]=sha(baseline);
rows.push({name:'baseline:all-regression-assertions-pass',pass:base.completed===true&&base.conditionCount===1035&&base.rows.every(r=>r.pass===true)});
for(const [file,hash]of Object.entries(base.sourceDigests)){rows.push({name:'baseline:source-current:'+file,pass:sha(file)===hash});sourceDigests[file]=hash;}
for(const [file,hash]of Object.entries(base.artifacts))rows.push({name:'baseline:artifact-current:'+file,pass:sha(file)===hash});
sourceDigests['scripts/summarize-header-navigation-editing.mjs']=sha('scripts/summarize-header-navigation-editing.mjs');
const completed=rows.every(r=>r.pass===true);const report={schema:'wt-header-navigation-editing.v1',completed,issue:185,parentIssue:100,conditionCount:Object.values(counts).reduce((sum,c)=>sum+c.conditions,0),assertionCount:rows.length,counts,baseline:{conditions:base.conditionCount,assertions:base.assertionCount,note:'Regression conditions overlap new editor/public probes; not a sum of unique configurations.'},sourceDigests,artifacts,rows};
fs.writeFileSync(out+'/summary.json',JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify({completed,conditions:report.conditionCount,assertions:rows.length,failures:rows.filter(r=>!r.pass)}));if(!completed)process.exitCode=1;
