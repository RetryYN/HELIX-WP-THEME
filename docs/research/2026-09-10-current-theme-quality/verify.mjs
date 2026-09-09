import {readFileSync,writeFileSync}from'node:fs';
import{createHash}from'node:crypto';
const dir='docs/research/2026-09-10-current-theme-quality/';
const root='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';
const json=p=>JSON.parse(readFileSync(p,'utf8'));
const rows=[];const check=(name,pass)=>rows.push({name,pass:Boolean(pass)});
function rendered(row){return row.http===200&&!row.overflow&&(row.found===true||row.text>=20);}
check('fixture positive',rendered({http:200,overflow:false,text:20}));
for(const [key,value]of Object.entries({http:404,overflow:true,text:0}))check('fixture rejects '+key,!rendered({http:200,overflow:false,text:20,[key]:value}));
const before=json(dir+'before.json').rows;const after=json(dir+'after.json').rows;
check('content matrix has 24 matching conditions',after.length===24&&before.length===24&&after.every((x,i)=>x.route===before[i].route&&x.width===before[i].width&&x.design===before[i].design));
for(const [i,row]of after.entries())check('content render '+i,rendered(row));
const differences=json(dir+'after-differences.json');
check('only intended form surface differs',differences.length===4&&differences.every(r=>r.after.class==='wt-form__side'&&Object.keys(r.after.styles).every(k=>k==='background-color'||r.before.styles[k]===r.after.styles[k])&&r.before.styles['background-color']==='rgba(0, 0, 0, 0)'&&r.after.styles['background-color']==='rgb(244, 246, 249)'));
const initialCommon=json(dir+'before-common.json');
const common=json(dir+'after-common.json');
const rgb=hex=>'rgb('+hex.replace('#','').match(/../g).map(x=>parseInt(x,16)).join(', ')+')';
check('common computed values preserve all but repaired surfaces',common.every((x,i)=>!x.parts||x.parts.length===initialCommon[i].parts.length&&x.parts.every((part,j)=>Object.keys(part).every(k=>part[k]===initialCommon[i].parts[j][k]||(k==='background'&&x.face==='form'&&initialCommon[i].parts[j][k]==='rgba(0, 0, 0, 0)'&&part[k]===rgb(x.palette.surface))))));
check('variation resolved palette unchanged',common.every((x,i)=>!x.palette||JSON.stringify(x.palette)===JSON.stringify(initialCommon[i].palette)));
check('common matrix has 35 cases',common.length===35);
for(const [i,row]of common.entries()){
 check('common render '+i,rendered(row));
 if(!row.noJs){check('motion stopped '+i,row.parts.every(x=>x.animation==='none'&&x.transition==='0s'));check('required palette resolved '+i,['surface','ok','warn-soft','ok-soft'].every(x=>row.palette[x]));}
}
const t=json(root+'theme.json');
check('font9/space8 preserved',t.settings.typography.fontSizes.length===9&&t.settings.spacing.spacingSizes.length===8);
const sameSlugs=(parent,variation)=>JSON.stringify([...parent].sort())===JSON.stringify([...variation].sort());
const parentSlugs=t.settings.color.palette.map(x=>x.slug);
for(const name of ['rules','mincho'])check('variation slugs '+name,sameSlugs(parentSlugs,json(root+'styles/'+name+'.json').settings.color.palette.map(x=>x.slug)));
check('negative variation added slug rejected',!sameSlugs(parentSlugs,[...parentSlugs,'fixture-added']));
check('negative variation removed slug rejected',!sameSlugs(parentSlugs,parentSlugs.slice(1)));
check('no undefined soft reference',!readFileSync(root+'assets/css/theme.css','utf8').includes('--wp--preset--color--soft'));
check('content faces has no important',!/!\s*important/.test(readFileSync(root+'assets/css/content-faces.css','utf8')));
for(const [file,digests]of Object.entries(json(dir+'source-digests.json').files))check('source digest '+file,createHash('sha256').update(readFileSync(root+file)).digest('hex')===digests.after_sha256);
check('all audit fixtures removed',json(dir+'fixtures.json').length===4&&json(dir+'fixtures.json').every(x=>x.created&&x.priorSlugAbsent&&x.deleted&&x.absent));
const pass=rows.every(x=>x.pass);
const result={schema:'wt-current-theme-quality-verification.v1',scope:'Current helix-wt partial quality improvement; not LOOK-01B completion',completed:pass,pass,passed:rows.filter(x=>x.pass).length,failed:rows.filter(x=>!x.pass).length,rows};
writeFileSync(dir+'verify.json',JSON.stringify(result,null,2)+'\n');console.log(result.passed+' pass / '+result.failed+' fail');process.exitCode=result.pass?0:1;
