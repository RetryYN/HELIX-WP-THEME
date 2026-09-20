import {fixture,copy,projection} from './model.mjs';
const $=id=>document.getElementById(id),id=document.body.dataset.page;let state=copy(fixture);
function render(next){const p=projection(next,id);$('breadcrumb').innerHTML=p.html;$('breadcrumb-json').textContent=JSON.stringify(p.json);$('schema-preview').textContent=JSON.stringify(p.json,null,2);$('page-title').textContent=next.nodes[id].name;state=next;}
$('editor').disabled=false;
$('apply').onclick=()=>{try{const next=copy(state);next.nodes[id]={...next.nodes[id],name:$('name').value,path:$('path').value,parent:$('parent').value};render(next);$('status').textContent='表示とJSON-LDを同じ正本から更新しました。保存・外部送信なし。';}catch(error){$('status').textContent=`更新を停止：${error.message}。前の表示を保持しました。`;}};
$('reset').onclick=()=>{render(copy(fixture));for(const key of ['name','path','parent'])$(key).value=fixture.nodes[id][key];$('status').textContent='初期状態へ戻しました。';};
$('breadcrumb').addEventListener('click',event=>{const a=event.target.closest('a');if(a){event.preventDefault();$('status').textContent=`リンク先の表示例：${a.href}（外部へ移動しません）`;}});
