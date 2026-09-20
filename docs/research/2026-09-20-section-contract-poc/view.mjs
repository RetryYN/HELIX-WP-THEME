import {fixture,clone,project,propose,apply,move,eventFor} from './contract.mjs';
import {article,toc,registry} from './render.mjs';
let state=clone(fixture),previous=null,pending=null;const $=id=>document.getElementById(id);const events=[];let observer;const timers=new Map();
const selected=()=> $('target').value;const status=text=>$('status').textContent=text;
function observe(){observer?.disconnect();for(const t of timers.values())clearTimeout(t);timers.clear();observer=new IntersectionObserver(entries=>{for(const entry of entries){const id=entry.target.dataset.observe;if(entry.isIntersecting&&document.visibilityState==='visible'){emit(id,'reach');if(!timers.has(id))timers.set(id,setTimeout(()=>{emit(id,'dwell',500);timers.delete(id);},500));}else{clearTimeout(timers.get(id));timers.delete(id);}}},{threshold:1});document.querySelectorAll('[data-observe]').forEach(e=>observer.observe(e));}
function emit(id,event,ms=0){try{const detail=eventFor(state,id,event,matchMedia('(max-width:700px)').matches?'sp':'pc',ms);events.push(detail);if(events.length>40)events.shift();document.dispatchEvent(new CustomEvent('helix:section-event',{detail}));$('events').textContent=JSON.stringify(events,null,2);$('event-status').textContent=`${id} / ${event}（外部送信なし）`;}catch{$('event-status').textContent='必須ID不正のためイベントを停止';}}
document.addEventListener('visibilitychange',()=>{if(document.hidden){for(const t of timers.values())clearTimeout(t);timers.clear();}else observe();});
function draw(){ $('content').innerHTML=article(state);$('toc').innerHTML=toc(state);$('registry').innerHTML=registry(state);$('projection').textContent=JSON.stringify(project(state),null,2);$('rollback').disabled=!previous;sync();observe();}
function sync(){const s=project(state).sections.find(s=>s.id===selected());$('heading-text').value=s.title;for(const key of ['collapsed','hidden','noToc'])$(key).checked=!!state.display[s.id]?.[key];}
function commit(next,message){previous=clone(state);state=next;pending=null;$('proposal').hidden=true;draw();status(message);}
function run(action){try{action();}catch(error){status(`変更を停止：${error.message}。状態は保持しました。`);}}
$('tools').disabled=false;$('target').onchange=()=>{pending=null;$('proposal').hidden=true;sync();};
$('rename').onclick=()=>run(()=>{const text=$('heading-text').value.trim();if(!text)throw Error('見出しを入力してください');const next=clone(state),section=project(state).sections.find(s=>s.id===selected());next.blocks[section.start].text=text;next.version++;commit(next,'見出し文言を変更しました。section IDは同じです。');});
for(const mode of ['rewrite','replace'])$(mode).onclick=()=>run(()=>{pending=propose(state,selected(),mode);$('diff').textContent=pending.changes.map(c=>`${c.key}\n− ${c.before}\n＋ ${c.after}`).join('\n\n');$('proposal').hidden=false;status('まだ適用していません。選択区間の差分を確認してください。');});
$('apply').onclick=()=>run(()=>{if(!pending)throw Error('差分がありません');commit(apply(state,pending),'選択区間だけを適用しました。');});$('reject').onclick=()=>{pending=null;$('proposal').hidden=true;status('差分を却下しました。本文は変わりません。');};
$('rollback').onclick=()=>run(()=>{if(!previous)throw Error('戻す変更がありません');state=previous;previous=null;pending=null;$('proposal').hidden=true;draw();status('直近の変更前へ戻しました。');});
$('move').onclick=()=>run(()=>commit(move(state,selected()),'同じ親の次の区間と順序を入れ替えました。'));
for(const key of ['collapsed','hidden','noToc'])$(key).onchange=()=>{const next=clone(state);next.display[selected()]={...next.display[selected()],[key]:$(key).checked};next.version++;commit(next,'表示設定を反映しました。');};
$('set-slot').onclick=()=>{const next=clone(state);next.postSlot=$('slot').value==='post'?{kind:'section',id:selected(),position:'before',label:'この記事：補足の案内'}:null;next.version++;commit(next,'挿入規則を反映しました。');};
observe();
