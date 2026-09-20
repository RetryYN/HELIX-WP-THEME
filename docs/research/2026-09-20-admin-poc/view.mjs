import {schema,defaults,validate,project} from './contract.mjs';
let applied=defaults();
const $=s=>document.querySelector(s),form=$('form'),area=$('#json'),state=$('#state');
const label={unchanged:'未変更',dirty:'未適用の変更があります',invalid:'検証に失敗しました',applied:'一時設定へ適用しました（未保存）'};
const setState=s=>{state.dataset.state=s;state.textContent=label[s];};
function render(){for(const f of schema.fields){for(const input of document.getElementsByName(f.id)){if(input.type==='radio')input.checked=input.value===applied[f.id];else input.value=applied[f.id];}}area.value=JSON.stringify(applied,null,2);$('#projection').textContent=JSON.stringify(project(applied),null,2);$('#effective').textContent=`目次：${project(applied).effectiveToc==='auto'?'表示（見出し条件あり）':'非表示'} / 記事：${applied['article.toc']==='inherit'?'継承':'上書き'}`;}
function apply(value){const errors=validate(value);$('#errors').replaceChildren();if(errors.length){for(const error of errors){const li=document.createElement('li');li.textContent=`${error.field} — ${error.message}`;$('#errors').append(li);}$('#diagnostic').hidden=false;setState('invalid');$('#diagnostic').focus();return false;}applied=structuredClone(value);$('#diagnostic').hidden=true;render();setState('applied');return true;}
form.addEventListener('input',()=>setState('dirty'));
form.addEventListener('submit',event=>{event.preventDefault();const value=structuredClone(applied),data=new FormData(form);for(const f of schema.fields)if(data.has(f.id))value[f.id]=f.type==='integer'?Number(data.get(f.id)):data.get(f.id);apply(value);});
$('#export').addEventListener('click',()=>{area.value=JSON.stringify(applied,null,2);area.focus();state.textContent='適用済みJSONを書き出しました（テキスト欄）';});
$('#import').addEventListener('click',()=>{try{apply(JSON.parse(area.value));}catch{apply(null);}});
area.addEventListener('input',()=>setState('dirty'));
if($('#bulk')){$('#bulk-select').addEventListener('change',e=>{$('#bulk').setAttribute('aria-disabled',String(!e.target.checked));$('#bulk-help').textContent=e.target.checked?'デモ記事１件が対象です。実投稿は変更しません。':'対象を選ぶと操作できます。実投稿の権限と保存は未接続です。';});$('#bulk').addEventListener('click',()=>{if(!$('#bulk-select').checked){$('#bulk-help').textContent='先に対象の記事を選んでください。';return;}apply({...applied,'article.toc':'inherit'});});}
render();setState('unchanged');
