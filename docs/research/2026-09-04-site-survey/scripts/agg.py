import json,glob,collections
rows=[]
for f in glob.glob('visual/*.jsonl'):
    for l in open(f):
        l=l.strip()
        if l: rows.append(json.loads(l))
rows=[r for r in rows if r.get('layout') or r.get('vibe')]
rows=[r for r in rows if r.get('notable')!='計測不能（白紙/ブロック画面）']
FIELDS=['layout','type','color','surface','shape','motion_cue','nav','vibe']
def pat(r): return r['id'].split('-')[0]
def cty(r): return 'jp' if '-jp-' in r['id'] else 'other'
groups={'all':rows,'jp':[r for r in rows if cty(r)=='jp'],'other':[r for r in rows if cty(r)=='other']}
for p in ['corporate','service','brand','portal','compare','motion']: groups[p]=[r for r in rows if pat(r)==p]
out={}
for g,rs in groups.items():
    out[g]={'n':len(rs)}
    for f in FIELDS:
        c=collections.Counter()
        for r in rs:
            v=r.get(f); 
            if isinstance(v,list):
                for t in set(v): c[t]+=1
            elif v: c[v]+=1
        out[g][f]={t:round(n/len(rs),2) for t,n in c.most_common()}
json.dump(out,open('visual/tag-rates.json','w'),ensure_ascii=False,indent=1)
json.dump(rows,open('visual/all.json','w'),ensure_ascii=False)
print('n',len(rows),{g:v['n'] for g,v in out.items()})
for f in FIELDS:
    print('==',f); print(' all',list(out['all'][f].items())[:10]); print(' jp ',list(out['jp'][f].items())[:8]); print(' oth',list(out['other'][f].items())[:8])
