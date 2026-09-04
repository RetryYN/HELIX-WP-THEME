import json,glob,collections,sys
import os
T=sys.argv[1] if len(sys.argv)>1 else os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # 作業ディレクトリ（coded/ を含む）
rows=[]
for f in sorted(glob.glob(f'{T}/coded/g*.jsonl')):
    for l in open(f,encoding='utf-8'):
        l=l.strip()
        if not l: continue
        try: rows.append(json.loads(l))
        except Exception as e: print('bad line',f,l[:60],file=sys.stderr)
print('rows',len(rows))
# de-dup by key
seen={}
for r in rows: seen[r['key']]=r
rows=list(seen.values())
def sitepat(k): return k.split('-')[0]
def country(k): return k.split('-')[1]
out={'n':len(rows),'by_part':{}}
parts=collections.defaultdict(lambda: collections.defaultdict(collections.Counter))
for r in rows:
    page=r.get('page') or ('article' if '-article-' in r['key'] else 'top'); dev=r.get('dev') or r['key'].split('-')[-1]
    for part,val in (r.get('tags') or {}).items():
        if not isinstance(val,str): val=str(val)
        for v in [x.strip() for x in val.split('|') if x.strip()]:
            parts[part][f'{page}/{dev}'][v]+=1
            parts[part][f'{page}/{dev}/{sitepat(r["key"])}'][v]+=1
            parts[part][f'{page}/{dev}/{country(r["key"])}'][v]+=1
for part,d in parts.items():
    out['by_part'][part]={k:dict(c.most_common()) for k,c in d.items()}
json.dump(out,open(f'{T}/aggregate.json','w'),ensure_ascii=False,indent=1)
# markdown summary: page/dev level only
md=['# パーツ別パターン出現率（画像コーディング集計）','',f'- shots: {len(rows)}','']
for part in sorted(parts):
    md.append(f'## {part}')
    for k in sorted(parts[part]):
        if k.count('/')!=1: continue
        c=parts[part][k]; tot=sum(c.values())
        if tot<5: continue
        items=', '.join(f'{v} {n} ({n*100//tot}%)' for v,n in c.most_common(12))
        md.append(f'- {k} (n={tot}): {items}')
    md.append('')
open(f'{T}/aggregate.md','w',encoding='utf-8').write('\n'.join(md))
print('written')
