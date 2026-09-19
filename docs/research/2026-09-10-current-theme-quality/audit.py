from pathlib import Path
import re,json,collections,hashlib
root=Path('docs/research/2026-09-05-design-prototype-03/theme/helix-wt')
out=Path('docs/research/2026-09-10-current-theme-quality')
raw_re=re.compile(r'(?<![\w-])[-+]?(?:\d*\.)?\d+(?:px|rem|em)\b')
raw=[];important=[]
for p in sorted(root.rglob('*')):
 if not p.is_file() or p.suffix not in ['.css','.php','.html','.json','.js']:continue
 source=p.read_text();s=re.sub(r'/\*.*?\*/',lambda m:'\n'*m[0].count('\n'),source,flags=re.S);file=str(p.relative_to(root))
 if p.suffix=='.json':
  def visit(v,path=[]):
   if isinstance(v,dict):
    for k,x in v.items():visit(x,path+[k])
   elif isinstance(v,list):
    for k,x in enumerate(v):visit(x,path+[str(k)])
   elif isinstance(v,str):
    for m in raw_re.finditer(v):
     kind='canonical token definition' if path and path[0]=='settings' else ('zero' if float(re.sub('[a-z]+$','',m[0]))==0 else 'structured style value')
     raw.append(dict(file=file,path='.'.join(path),value=m[0],kind=kind))
  visit(json.loads(source));continue
 media=[(m.start(),m.end())for m in re.finditer(r'@media[^{}]+',s)]
 for m in raw_re.finditer(s):
  prefix=s[max(s.rfind(';',0,m.start()),s.rfind('{',0,m.start()))+1:m.start()]
  value=m[0];number=float(re.sub('[a-z]+$','',value))
  if number==0:kind='zero'
  elif any(a<=m.start()<b for a,b in media):kind='responsive condition'
  elif re.search(r'--[\w-]+\s*:[^;{}]*$',prefix):kind='local token definition or axis override'
  elif value=='1px' and re.search(r'border(?:-(?:top|right|bottom|left|width|block|inline))?\s*:',prefix):kind='hairline border'
  elif p.suffix=='.js':kind='JS geometry / observation (review individually)'
  else:kind='component / content dimension (not automatically allowed)'
  raw.append(dict(file=file,line=s.count('\n',0,m.start())+1,value=value,kind=kind))
 stack=[];start=0
 for m in re.finditer(r'[{};]',s):
  piece=s[start:m.start()];start=m.end()
  if m[0]=='{':stack.append(piece.strip())
  else:
   if '!important' in piece:
    prop=re.match(r'\s*([\w-]+)\s*:',piece);prop=prop[1]if prop else '?'
    kind='reduced-motion protection'if any('prefers-reduced-motion' in x for x in stack)else('visibility / axis selection (not automatically allowed)'if prop=='display'else'visual / layout override (unresolved)')
    important.append(dict(file=file,line=s.count('\n',0,m.start())+1,selector=stack[-1]if stack else '',property=prop,kind=kind))
   if m[0]=='}' and stack:stack.pop()
report={'scope':'Current helix-wt runtime .css/.php/.html/.json/.js; block comments stripped; raw occurrences are not violation counts','rawCount':len(raw),'rawCategories':dict(collections.Counter(r['kind']for r in raw)),'importantCount':len(important),'importantCategories':dict(collections.Counter(r['kind']for r in important)),'raw':raw,'important':important}
(out/'audit.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
print({k:v for k,v in report.items()if k not in ['raw','important']})
