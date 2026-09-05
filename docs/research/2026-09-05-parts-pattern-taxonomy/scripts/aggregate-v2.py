# recapture-v2/coded/g*.jsonl → recapture-v2/aggregate-v2.{json,md}
import json,glob,collections,os,sys
T=sys.argv[1] if len(sys.argv)>1 else os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))),'recapture-v2')
rows={}
for f in sorted(glob.glob(f'{T}/coded/g*.jsonl')):
    for l in open(f,encoding='utf-8'):
        if l.strip():
            r=json.loads(l); rows[r['key']]=r
rows=list(rows.values())
parts=collections.defaultdict(lambda: collections.defaultdict(collections.Counter))
for r in rows:
    page,dev=r['page'],r['dev']; pat=r['key'].split('-')[0]
    for part,val in (r.get('tags') or {}).items():
        for v in [x.strip() for x in str(val).split('|') if x.strip()]:
            parts[part][f'{page}/{dev}'][v]+=1; parts[part][f'{page}/{dev}/{pat}'][v]+=1
json.dump({'n':len(rows),'by_part':{p:{k:dict(c.most_common()) for k,c in d.items()} for p,d in parts.items()}},open(f'{T}/aggregate-v2.json','w'),ensure_ascii=False,indent=1)
md=['# footer・記事末尾・カテゴリ面 出現率（再取得コーディング v2 集計）','',f'- keys: {len(rows)}（サイト×面×端末。スクリプト scripts/aggregate-v2.py で再生成）','- n は付与された値の総数（複数値は個別に数える）。観察 = n − na。%は n 比。','']
for part in sorted(parts):
    md.append(f'## {part}')
    for k in sorted(parts[part]):
        if k.count('/')!=1: continue
        c=parts[part][k]; tot=sum(c.values())
        if tot<5: continue
        md.append(f'- {k} (n={tot}, 観察 {tot-c.get("na",0)}): '+', '.join(f'{v} {n} ({n*100//tot}%)' for v,n in c.most_common(12)))
    md.append('')
open(f'{T}/aggregate-v2.md','w').write('\n'.join(md))
print('keys',len(rows))
