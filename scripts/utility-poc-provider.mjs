// 研究用ローカル処理。製品テーマ/プラグインへ配布しない。
import {definitions} from '../docs/research/2026-09-20-utility-poc/definitions.mjs';
export function executeUtility(kind, input) {
 const d=definitions.find(x=>x.kind===kind);if(!d)return {errors:{form:'用途を選び直してください。'}};
 const errors={};
 for(const f of d.fields){
  const value=input[f.id];
  if(f.type==='number'&&(typeof value!=='string'||!/^\d+$/.test(value)||Number(value)<f.min||Number(value)>f.max))errors[f.id]=`${f.label}は${f.min}〜${f.max}の整数で入力してください。`;
  if(f.type==='text'&&(typeof value!=='string'||![...value.trim()].length||[...value.trim()].length>80))errors[f.id]='テーマは1〜80文字で入力してください。';
  if(f.type==='checkbox'&&typeof value!=='boolean')errors[f.id]='選択状態を確認してください。';
 }
 if(Object.keys(errors).length)return {errors};
 let value,items,used;
 if(kind==='calculator') {value=`${(Number(input.people)*Number(input.cost)).toLocaleString('ja-JP')} 円`;items=['送料や予備費が必要なら、別に加えて確かめてください。'];used=`人数 ${input.people}人 / 一人あたり ${input.cost}円`;}
 if(kind==='grader'){const checked=d.fields.filter(f=>input[f.id]);value=`${checked.length} / 3 項目を確認`;items=d.fields.map(f=>`${input[f.id]?'確認済み':'次に確認'}：${f.label}`);used=d.fields.map(f=>`${f.label}：${input[f.id]?'はい':'未確認'}`).join(' / ');}
 if(kind==='generator'){const topic=input.topic.trim();value='３つの見出しのたたき台';items=['はじめてガイド','選ぶ前のチェック','よくある質問'].map(s=>`${topic} ${s}`);used=topic;}
 return {result:{schema:'wt-utility-result.v1',kind,value,items,used,method:d.method,updated:'2026-09-20',rounding:kind==='calculator'?'円単位・四捨五入（整数入力のため端数なし）':'丸めなし',exclusions:d.exclusions}};
}
