export const schema = {
 id:'wt-admin-poc.v1',
 fields:[
  {id:'site.bundle',label:'サイトの既定セット',type:'enum',values:['editorial','service'],default:'editorial',owner:'settings JSON',options:[['editorial','読みもの中心','本文と目次を優先'],['service','サービス紹介','案内と行動導線を優先']]},
  {id:'parts.toc',label:'目次の表示',type:'enum',values:['auto','off'],default:'auto',owner:'settings JSON',options:[['auto','見出しが３つ以上','本文冒頭 / SPは折りたたむ'],['off','表示しない','記事ごとの上書きは別途選択']]},
  {id:'article.toc',label:'この記事の目次',type:'enum',values:['inherit','auto','off'],default:'inherit',owner:'post meta 接続プレビュー',options:[['inherit','サイト設定を継承','パーツの選択に追従'],['auto','この記事で表示','見出し３つ以上が条件'],['off','この記事では非表示','この記事だけ上書き']]},
  {id:'site.label',label:'セットの管理名',type:'string',minLength:1,maxLength:24,pattern:'^[^<>]+$',default:'編集用セット',owner:'settings JSON'},
  {id:'parts.threshold',label:'目次に必要な見出し数',type:'integer',min:3,max:8,default:3,owner:'settings JSON'}
 ],
 actions:[{id:'apply',label:'一時設定へ適用',bulk:false},{id:'inherit',label:'選択記事を継承へ戻す',bulk:true},{id:'export',label:'JSONを書き出す',bulk:false},{id:'import',label:'JSONを検証して取り込む',bulk:false}],
 hierarchy:[{id:'site',parent:null},{id:'parts',parent:'site'},{id:'article',parent:'parts'}],
 states:['unchanged','dirty','invalid','applied'],
 exclusions:['計測・広告コードはテーマ外','色・書体・寸法はtheme.json / Site Editor','credential・AI処理なし']
};
export const defaults=()=>Object.fromEntries([['schema',schema.id],...schema.fields.map(f=>[f.id,f.default])]);
export function validate(value){
 const errors=[];
 if(!value||typeof value!=='object'||Array.isArray(value))return [{field:'document',message:'JSONオブジェクトを指定してください。'}];
 for(const key of Object.keys(value))if(key!=='schema'&&!schema.fields.some(f=>f.id===key))errors.push({field:key,message:'schemaにない項目です。'});
 if(value.schema!==schema.id)errors.push({field:'schema',message:'schemaの版が一致しません。'});
 for(const f of schema.fields){const v=value[f.id];let valid=f.type==='enum'?f.values.includes(v):f.type==='integer'?Number.isInteger(v)&&v>=f.min&&v<=f.max:typeof v==='string'&&v.length>=f.minLength&&v.length<=f.maxLength&&new RegExp(f.pattern).test(v);if(!valid)errors.push({field:f.id,message:`${f.label}: ${f.type==='enum'?'候補から選択':f.type==='integer'?`${f.min}〜${f.max}の整数`:`${f.minLength}〜${f.maxLength}文字、<>は不可`}。`});}
 return errors;
}
export function project(value){return {settings:{schema:value.schema,site:{bundle:value['site.bundle'],label:value['site.label']},parts:{toc:value['parts.toc'],threshold:value['parts.threshold']}},postMetaPreview:{toc:value['article.toc']},effectiveToc:value['article.toc']==='inherit'?value['parts.toc']:value['article.toc']};}
export const layers=[['site','サイト既定','サイト全体の出発点を選ぶ'],['parts','パーツ','既定セットから部品を調整'],['article','記事の上書き','継承する値と例外を見分ける']];
