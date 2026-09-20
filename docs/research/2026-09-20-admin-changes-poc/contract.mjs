export const fixtures=[
 {id:'review',label:'差分レビュー',lead:'変更前と変更後を確認してから適用する。'},
 {id:'blocked',label:'破壊域の停止',lead:'保護対象が含まれる変更案は、まとめて停止する。'},
 {id:'recovery',label:'失敗からの復旧',lead:'差分と診断を残し、直近の状態へ戻す。'}
];
export const base={toc:'auto',threshold:3,protectedContent:'保持'};
export const proposal={toc:'off',threshold:4,protectedContent:'保持'};
export const names={dryrun:'dry-run',apply:'適用',reject:'却下',rollback:'rollback',hold:'保留'};
export function initial(kind){return {kind,status:'unreviewed',current:{...base},target:{...proposal,...(kind==='blocked'?{protectedContent:'削除'}:{})},checked:false,snapshot:null,error:null,log:[],sequence:0};}
export function change(state,action,{failure='none',confirmed=false}={}){
 const next=structuredClone(state);
 const log=(result)=>{next.sequence++;next.log.push({id:`LOCAL-CHANGE-${String(next.sequence).padStart(3,'0')}`,action,result});};
 const failed=(code,message,recovery)=>{log('失敗');next.error={id:`LOCAL-${action.toUpperCase()}-${String(next.sequence).padStart(3,'0')}`,action,code,message,recovery};return next;};
 if(!Object.hasOwn(names,action))return state;
 if(action==='dryrun'){
  if(['applied','partial'].includes(state.status))return failed('RESTORE_FIRST','直近の変更を復旧してから再検査してください。','rollback');
  if(failure==='dryrun')return failed('DRYRUN_UNAVAILABLE','差分の検査を完了できませんでした。値は変更していません。','dryrun');
  next.status=state.target.protectedContent!==base.protectedContent?'blocked':'reviewed';next.checked=true;next.error=null;log(next.status==='blocked'?'破壊域停止':'検査済み');return next;
 }
 if(action==='apply'){
  if(['applied','partial'].includes(state.status))return failed('RESTORE_FIRST','直近の変更をrollbackしてから、変更案を再検査してください。','rollback');
  if(state.status!=='reviewed'||!state.checked||!confirmed||state.target.protectedContent!==base.protectedContent)return failed('REVIEW_REQUIRED','検査済みの差分と確認チェックが必要です。破壊域停止は解除できません。','dryrun');
  if(failure==='apply')return failed('APPLY_UNAVAILABLE','適用を完了できませんでした。元の値を保持しています。','apply');
  next.snapshot={...state.current};
  if(failure==='partial'){next.current.toc=next.target.toc;next.status='partial';return failed('PARTIAL_APPLY','２項目のうち１項目だけが変わりました。適用は未完了です。再適用前にrollbackしてください。','rollback');}
  next.current={...next.target};next.status='applied';next.error=null;log('適用済み（未保存）');return next;
 }
 if(action==='rollback'){
  if(!state.snapshot||!['applied','partial'].includes(state.status))return failed('NO_SNAPSHOT','戻せる直近変更がありません。値は変更していません。','dryrun');
  if(failure==='rollback')return failed('ROLLBACK_UNAVAILABLE','復旧を完了できませんでした。戻す前の値と差分を保持しています。','rollback');
  next.current={...state.snapshot};next.snapshot=null;next.status='restored';next.checked=false;next.error=null;log('復旧済み（未保存）');return next;
 }
 if(['applied','partial'].includes(state.status))return failed('RESTORE_FIRST','適用後は却下・保留せず、rollbackで復旧してください。','rollback');
 if(failure===action)return failed('REJECT_UNAVAILABLE','却下を完了できませんでした。変更案を保持しています。','reject');
 next.status=action==='reject'?'rejected':'held';next.checked=false;next.error=null;log(action==='reject'?'却下済み':'保留中');return next;
}
