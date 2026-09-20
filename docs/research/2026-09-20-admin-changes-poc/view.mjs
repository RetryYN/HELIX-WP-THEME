import {initial,change,names} from './contract.mjs';
let state=initial(document.body.dataset.kind);
const $=s=>document.querySelector(s);
const statuses={unreviewed:'未検査',reviewed:'検査済み・未適用',blocked:'破壊域停止',applied:'適用済み（未保存）',partial:'部分適用・未完了',restored:'復旧済み（未保存）',rejected:'却下済み',held:'保留中'};
function render(){
 $('#state').textContent=statuses[state.status];$('#state').dataset.state=state.status;
 $('#current-toc').textContent=state.current.toc;$('#current-threshold').textContent=state.current.threshold;$('#current-protected').textContent=state.current.protectedContent;
 $('#apply').setAttribute('aria-disabled',String(!(state.status==='reviewed'&&$('#confirm').checked)));$('#rollback').setAttribute('aria-disabled',String(!state.snapshot));
 $('#action-help').textContent=state.status==='blocked'?'破壊域停止：安全な項目も含め、この変更案全体を適用しません。':state.status==='reviewed'?'差分を確認してチェックすると適用できます。':'適用にはdry-runの検査と確認が必要です。却下・保留・復旧後は再検査してください。';
 $('#rollback-help').textContent=state.snapshot?'直近の変更前の値へ戻せます。部分適用も復旧対象です。':'戻せる変更はありません。';
 $('#log').replaceChildren();for(const entry of state.log){const li=document.createElement('li');li.textContent=`${entry.id} / ${names[entry.action]} / ${entry.result}`;$('#log').append(li);}if(!state.log.length)$('#log').textContent='まだ操作していません。';
 $('#diagnostic').hidden=!state.error;if(state.error){const e=state.error;$('#error-message').textContent=e.message;$('#error-id').value=`${e.id}\n${e.code} / DEMO-001`;$('#recovery-context').textContent=`対象：DEMO-001 / 失敗操作：${names[e.action]} / 次の手順：${names[e.recovery]}。変更案と現在値はこの画面に保持しています。`;$('#retry').textContent=`${names[e.recovery]}を再試行`;$('#copy-status').textContent='';}
}
function perform(action){const chosen=$('#failure').value;const failure=chosen===action||(chosen==='partial'&&action==='apply')?chosen:'none';if(failure!=='none')$('#failure').value='none';state=change(state,action,{failure,confirmed:$('#confirm').checked});if(['rejected','held','restored'].includes(state.status))$('#confirm').checked=false;render();if(state.error)$('#diagnostic').focus();}
for(const action of Object.keys(names))$('#'+action).addEventListener('click',()=>perform(action));
$('#confirm').addEventListener('change',render);
$('#retry').addEventListener('click',()=>{if(state.error)perform(state.error.recovery);});
$('#copy').addEventListener('click',async()=>{const area=$('#error-id');try{await navigator.clipboard.writeText(area.value);$('#copy-status').textContent='診断情報をコピーしました。';}catch{area.focus();area.select();$('#copy-status').textContent='自動コピーできませんでした。選択した診断情報を手動でコピーしてください。';}});
render();
