// 表示・入力状態のみ。計算/採点/文章組立はテーマ外のローカル処理に委ねる。
const $=id=>document.getElementById(id),form=document.querySelector('form'),fields=[...form.querySelectorAll('input')];
let controller=null,version=0;
$('submit').disabled=false;
function hideResult(){ $('result').hidden=true;$('placeholder').hidden=false; }
function clearErrors(){ $('error').textContent='';for(const input of fields){input.removeAttribute('aria-invalid');$(input.id+'-error').textContent='';} }
function end(){controller=null;$('submit').disabled=false;$('cancel').hidden=true;form.removeAttribute('aria-busy');}
function invalidate(message){version++;controller?.abort();end();hideResult();$('status').textContent=message;}
form.addEventListener('input',()=>{invalidate('入力が変わりました。もう一度、結果を確かめてください。');clearErrors();});
$('cancel').addEventListener('click',()=>{invalidate('処理を中止しました。入力はそのままです。');fields[0].focus();});
$('again').addEventListener('click',()=>{invalidate('入力を変えて、もう一度試せます。');fields[0].focus();});
function showErrors(errors){$('error').textContent='入力を確認してください。修正後にもう一度実行できます。';for(const [id,message]of Object.entries(errors)){const input=fields.find(x=>x.id===id);if(input){input.setAttribute('aria-invalid','true');$(id+'-error').textContent=message;}}(fields.find(x=>x.hasAttribute('aria-invalid'))||fields[0]).focus();}
form.addEventListener('submit',async event=>{
 event.preventDefault();invalidate('入力を確認しています。');clearErrors();
 const input=Object.fromEntries(fields.map(x=>[x.id,x.type==='checkbox'?x.checked:x.value]));
 const errors={};for(const field of fields){if(!field.validity.valid)errors[field.id]=field.type==='number'?`${field.min}〜${field.max}の整数で入力してください。`:'テーマを入力してください。';}
 if(Object.keys(errors).length){$('status').textContent='入力の修正が必要です。';showErrors(errors);return;}
 controller=new AbortController();const own=version,signal=controller.signal;
 $('submit').disabled=true;$('cancel').hidden=false;form.setAttribute('aria-busy','true');$('status').textContent='処理しています。入力は保存されません。';
 const requestController=controller;
 const timeout=setTimeout(()=>requestController.abort(),5000);
 try{
  const response=await fetch('/utility/execute',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({kind:document.body.dataset.kind,input}),signal,cache:'no-store'});
  if(own!==version)return;
  const output=await response.json();
  if(own!==version)return;
  if(response.status===422&&output.errors){$('status').textContent='入力の修正が必要です。';showErrors(output.errors);return;}
  const r=output.result;
  if(!response.ok||!r||r.schema!=='wt-utility-result.v1'||r.kind!==document.body.dataset.kind||!Array.isArray(r.items)||!r.items.every(x=>typeof x==='string')||!['value','used','method','updated','rounding','exclusions'].every(k=>typeof r[k]==='string'&&r[k]))throw Error('contract');
  $('value').textContent=r.value;$('items').replaceChildren(...r.items.map(text=>{const li=document.createElement('li');li.textContent=text;return li;}));
  $('basis').replaceChildren();for(const[label,text]of [['使用した入力',r.used],['計算・判断の基準',r.method],['基準更新日',r.updated],['丸め',r.rounding],['対象外',r.exclusions]]){const dt=document.createElement('dt'),dd=document.createElement('dd');dt.textContent=label;dd.textContent=text;dd.dir='auto';$('basis').append(dt,dd);}
  $('result').hidden=false;$('placeholder').hidden=true;$('status').textContent='結果を更新しました。根拠と対象外も確認してください。';$('result-title').focus();
 }catch{if(own===version){hideResult();$('status').textContent='結果を取得できませんでした。';$('error').textContent='入力はそのままです。もう一度実行するか、「手元で確かめる方法」を使ってください。';}}
 finally{clearTimeout(timeout);if(own===version)end();}
});
