import json,glob,statistics as st,collections,re,sys
def load(d):
    out=[]
    for f in glob.glob(d+'/results/*.json'):
        r=json.load(open(f))
        if r.get('error'): continue
        out.append(r)
    return out
def g(o,*ks):
    for k in ks:
        if o is None: return None
        o=o.get(k) if isinstance(o,dict) else None
    return o
def q(vals):
    v=[x for x in vals if isinstance(x,(int,float)) and x is not None]
    if not v: return None
    v.sort();n=len(v)
    def pc(p):
        i=(n-1)*p;lo=int(i);hi=min(lo+1,n-1);return round(v[lo]+(v[hi]-v[lo])*(i-lo),1)
    return {'n':n,'q1':pc(.25),'med':pc(.5),'q3':pc(.75),'p10':pc(.1),'p90':pc(.9)}
METRICS={
 'body':('typography','bodyText','fontSize'),
 'bodyLH':('typography','bodyText','lineHeightRatio'),
 'bodyLS':('typography','bodyText','letterSpacing'),
 'h1':('typography','headings','h1','fontSize'),
 'h2':('typography','headings','h2','fontSize'),
 'h3':('typography','headings','h3','fontSize'),
 'h2mt':('typography','headings','h2','marginTop'),
 'h2mb':('typography','headings','h2','marginBottom'),
 'pgap':('spacing','paragraphGap'),
 'container':('typography','container','innerWidth'),
 'padX':('typography','container','paddingLeft'),
 'header':('components','header','height'),
 'hero':('components','hero','height'),
 'btnH':('components','buttons','height'),
 'btnPX':('components','buttons','paddingX'),
 'btnR':('components','buttons','radius'),
 'btnFS':('components','buttons','fontSize'),
 'imgR':('components','imageRadius'),
 'cardR':('components','cards','radius'),
 'cardP':('components','cards','paddingX'),
 'secPT':('spacing','paddingTop'),
 'secGap':('spacing','gapMax'),
 'fvChars':('mobile','firstViewTextChars'),
 'smallTap':('mobile','smallTapRate'),
 'bottomFixedH':('components','bottomFixedHeight'),
 'lcp':('perf','lcp'),
 'kb':('perf','transferBytes'),
 'fonts':('perf','fontFiles'),
 'dom':('perf','domNodes'),
 'transitions':('motion','transitionElements'),
 'keyframes':('motion','keyframes'),
}
BOOLS={'sticky':('components','stickyHeader'),'bottomFixed':('components','bottomFixed'),'hamburger':('components','hamburger'),
 'toc':('components','toc'),'breadcrumb':('components','breadcrumb'),'shadow':('components','shadowUsed'),
 'scrollDriven':('motion','scrollDrivenAnimation'),'reducedMotion':('motion','reducedMotionQuery'),'canvas':('motion','canvas'),
 'webgl':('motion','webgl'),'videoAuto':('motion','videoAutoplay'),'lottie':('motion','lottie'),'gsap':('motion','gsap'),
 'io':('motion','intersectionObserver'),'below16':('mobile','bodyFontBelow16'),'hscroll':('mobile','horizontalScroll'),'wp':('meta','isWordPress')}
def num(x):
    if isinstance(x,str):
        m=re.match(r'-?[\d.]+',x); return float(m.group()) if m else None
    return x
def summarize(rs,dev):
    out={'n':len(rs),'metrics':{},'bools':{},'fonts':collections.Counter(),'bg':collections.Counter(),'accent':collections.Counter()}
    for k,path in METRICS.items():
        vals=[]
        for r in rs:
            v=g(r,dev,'data',*path); v=num(v)
            if k=='kb' and v: v=v/1024
            if k=='canvas': v=bool(v)
            vals.append(v)
        out['metrics'][k]=q(vals)
    for k,path in BOOLS.items():
        vals=[g(r,dev,'data',*path) for r in rs]
        vals=[bool(v) for v in vals if v is not None]
        out['bools'][k]={'n':len(vals),'rate':round(sum(vals)/len(vals),2) if vals else None}
    for r in rs:
        ff=g(r,dev,'data','typography','bodyText','fontFamily') or ''
        fam=ff.split(',')[0].strip('"\' ')
        if fam: out['fonts'][fam]+=1
        for f in (g(r,dev,'data','typography','loadedFonts') or []): out['fonts']['[loaded] '+f]+=1
        tc=g(r,dev,'data','colors','topColors') or []
        if tc: out['bg'][tc[0]['hex']]+=1
        # accent: first non-grey top color
        for c in tc[1:]:
            h=c['hex']; rr,gg,bb=int(h[1:3],16),int(h[3:5],16),int(h[5:7],16)
            if max(rr,gg,bb)-min(rr,gg,bb)>40: out['accent'][h]+=1; break
    out['fonts']=out['fonts'].most_common(15); out['bg']=out['bg'].most_common(8); out['accent']=out['accent'].most_common(12)
    return out
full=load('full'); ref=load('ref')
groups={}
groups['all/top']=[r for r in full if r.get('page')=='top' or not r['id'].endswith('-article')]
groups['all/article']=[r for r in full if r['id'].endswith('-article')]
for p in ['corporate','service','brand','portal','compare','motion']:
    groups[p+'/top']=[r for r in groups['all/top'] if r['pattern']==p]
    if p in ('portal','compare'): groups[p+'/article']=[r for r in groups['all/article'] if r['pattern']==p]
groups['jp/top']=[r for r in groups['all/top'] if r.get('country')=='jp']
groups['other/top']=[r for r in groups['all/top'] if r.get('country')!='jp']
res={'groups':{k:{'sp':summarize(v,'sp'),'pc':summarize(v,'pc')} for k,v in groups.items()},'ref':{}}
for r in ref:
    row={}
    for dev in ('sp','pc'):
        row[dev]={k:num(g(r,dev,'data',*p)) for k,p in METRICS.items()}
        row[dev]['kb']=round(row[dev]['kb']/1024) if row[dev].get('kb') else None
        row[dev].update({k:g(r,dev,'data',*p) for k,p in BOOLS.items()})
        row[dev]['font']=(g(r,dev,'data','typography','bodyText','fontFamily') or '').split(',')[0]
    res['ref'][r['id']]=row
json.dump(res,open('analysis.json','w'),ensure_ascii=False,indent=1)
# print concise view
def show(name,dev,keys):
    s=res['groups'][name][dev]; print(f"== {name} {dev} n={s['n']}")
    for k in keys:
        m=s['metrics'][k]; print(f"  {k:12s}", m and f"med {m['med']} (q1 {m['q1']} q3 {m['q3']} p10 {m['p10']} p90 {m['p90']} n={m['n']})")
K=['body','bodyLH','h1','h2','h3','h2mt','pgap','container','padX','header','hero','btnH','btnR','btnFS','imgR','cardR','secPT','fvChars','smallTap','lcp','kb']
for n in ['all/top','all/article','jp/top','other/top']:
    for dev in ('sp','pc'): show(n,dev,K)
for n in ['all/top','all/article']:
    print('== bools',n,{k:v['rate'] for k,v in res['groups'][n]['sp']['bools'].items()},'PC sticky',res['groups'][n]['pc']['bools']['sticky'])
    print('  fonts',res['groups'][n]['sp']['fonts'][:10]); print('  bg',res['groups'][n]['sp']['bg'][:5]); print('  accent',res['groups'][n]['sp']['accent'][:8])
print('== REF')
for k,v in res['ref'].items():
    print(k,'SP',{x:v['sp'][x] for x in ['body','bodyLH','h1','h2','h3','header','fvChars','btnH','container','bottomFixedH','kb','lcp']})
    print(k,'PC',{x:v['pc'][x] for x in ['body','h1','h2','h3','header','container']})
