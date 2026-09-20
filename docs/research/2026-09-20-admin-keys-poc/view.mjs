import {createDemoRegistry} from './contract.mjs';
const registry=createDemoRegistry(),$=s=>document.querySelector(s);let selected='',visible=false,lastFailure=null;const logs=[];
const log=(action,id,result)=>{logs.push(`${action} / ${id||'未発行'} / ${result}`);$('#log').replaceChildren();for(const text of logs){const li=document.createElement('li');li.textContent=text;$('#log').append(li);}};
function forget(){visible=false;$('#value').value='';$('#once').hidden=true;}
function refresh(){const records=registry.list();$('#record').replaceChildren();for(const r of records){const option=document.createElement('option');option.value=r.id;option.textContent=`${r.id} / ${r.permission==='read'?'読み取り専用':'書き込み可'}`;$('#record').append(option);}if(!records.length){const option=document.createElement('option');option.value='';option.textContent='未発行';$('#record').append(option);}$('#record').value=selected;const r=records.find(x=>x.id===selected);$('#record-status').textContent=r?(r.status==='active'?'有効（デモ）':'失効済み（デモ）'):'未発行';$('#record-permission').textContent=r?(r.permission==='read'?'読み取り専用':'書き込み可'):'—';$('#record-value-state').textContent=visible?'今回だけ表示中':'保持していません';$('#revoke').setAttribute('aria-disabled',String(!r||r.status==='revoked'));$('#reveal').setAttribute('aria-disabled','true');}
function diagnostic(result,retry=null){$('#error-message').textContent=result.error.message;$('#error-code').textContent=`LOCAL / ${result.error.code}`;$('#error').hidden=false;lastFailure=retry;$('#retry').hidden=!retry;$('#error').focus();}
function run(action){const fail=$('#failure').value===action;if(fail)$('#failure').value='none';
 if(action==='issue'){
  if(visible){diagnostic({error:{code:'CLOSE_FIRST',message:'今回の表示を閉じてから、新しい発行を試してください。'}});return;}
  const result=registry.issue(document.querySelector('[name=permission]:checked')?.value,fail);log('発行',result.ok?result.record.id:'',result.ok?'成功':'失敗');if(!result.ok){diagnostic(result,'issue');refresh();return;}selected=result.record.id;$('#error').hidden=true;lastFailure=null;visible=true;$('#value').value=result.value;$('#once').hidden=false;$('#status').textContent='発行できました（無効なデモ値）。閉じると値を消します。';$('#access-result').textContent='対象の接続で操作を試せます。';refresh();$('#once').focus();return;
 }
 forget();const result=registry.revoke(selected,fail);log('失効',selected,result.ok?'成功':'失敗');if(!result.ok)diagnostic(result,result.error.code==='REVOKE_FAILED'?'revoke':null);else{$('#error').hidden=true;lastFailure=null;$('#status').textContent='選択した接続を失効しました（デモ）。';$('#access-result').textContent='失効した接続のアクセスは拒否します。';}refresh();
}
$('#issue').addEventListener('click',()=>run('issue'));$('#revoke').addEventListener('click',()=>run('revoke'));
$('#forget').addEventListener('click',()=>{forget();refresh();$('#status').textContent='発行値を消しました。再表示できません。';$('#issue').focus();});
$('#select-value').addEventListener('click',()=>{$('#value').focus();$('#value').select();});
$('#record').addEventListener('change',e=>{forget();selected=e.target.value;$('#error').hidden=true;$('#access-result').textContent='選択した接続で操作を試せます。';refresh();});
$('#reveal').addEventListener('click',()=>{forget();diagnostic(registry.reveal());refresh();});
for(const operation of ['read','write'])$('#'+operation).addEventListener('click',()=>{const result=registry.check(selected,operation);$('#access-result').textContent=result.ok?`${operation==='read'?'読み取り':'書き込み'}を許可しました（ローカル判定）。`:`拒否：${result.error.message}`;log(operation==='read'?'読み取り':'書き込み',selected,result.ok?'許可':'拒否');});
$('#retry').addEventListener('click',()=>{if(lastFailure)run(lastFailure);});window.addEventListener('pagehide',forget);refresh();
