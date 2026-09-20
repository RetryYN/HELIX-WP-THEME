export const fixtures=[{id:'readonly',label:'読み取り用',lead:'閲覧だけを許可する専用ロールの表示候補。'},{id:'writer',label:'書き込み用',lead:'更新を許可する用途を、読み取り用から分ける。'},{id:'revoked',label:'失効後',lead:'失効した接続は読み取りも書き込みも拒否する。'}];
export function createDemoRegistry(){
 let sequence=0;const records=new Map();
 const result=(code,message)=>({ok:false,error:{code,message}});
 return {
  list:()=>Array.from(records.values(),r=>({...r})),
  issue(permission,fail=false){if(!['read','write'].includes(permission))return result('INVALID_PERMISSION','読み取り用または書き込み用を選んでください。');if(fail)return result('ISSUE_FAILED','発行を完了できませんでした。接続情報も発行値も作成していません。');sequence++;const record={id:`DEMO-REF-${sequence}`,permission,role:permission==='read'?'reader-role-preview':'writer-role-preview',status:'active'};records.set(record.id,record);return {ok:true,record:{...record},value:['DEMO','NOT','A','CREDENTIAL',String(sequence)].join('-')};},
  reveal(){return result('VALUE_NOT_RETRIEVABLE','発行値は再表示できません。必要なら失効して、新しく発行してください。');},
  revoke(id,fail=false){const record=records.get(id);if(!record)return result('NOT_FOUND','対象の接続情報がありません。');if(record.status==='revoked')return result('ALREADY_REVOKED','この接続はすでに失効しています。');if(fail)return result('REVOKE_FAILED','失効を完了できませんでした。状態は有効のままです。再試行してください。');record.status='revoked';return {ok:true,record:{...record}};},
  check(id,operation){const record=records.get(id);if(!record||record.status!=='active')return result('ACCESS_REVOKED','失効済み、または対象がないため拒否しました。');if(!['read','write'].includes(operation))return result('INVALID_OPERATION','対象外の操作です。');if(operation==='write'&&record.permission!=='write')return result('READ_ONLY','読み取り用のため書き込みを拒否しました。');return {ok:true,operation};}
 };
}
