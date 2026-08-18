import json,re,sys,unicodedata

SRC,DST=sys.argv[1:3]
with open(SRC,encoding='utf-8') as f:data=json.load(f)

def clean_text(s):
    if not s:return s
    fixes=[
        (r'\bIberian\s+Iberian\b','Iberian'),
        (r'\bIberian breed\s+Iberian breed\b','Iberian breed'),
        (r'\bCátedra\b','Masterclass'),
        (r'\bcátedra\b','masterclass'),
        (r'\bCesarean section\b','Cesáreo'),
        (r'\bcesarean section\b','Cesáreo'),
        (r'\bHam reservation\b','Reserve Ham'),
        (r'\bham reservation\b','reserve ham'),
        (r'\bheadboard\b','loin head'),
    ]
    for pat,rep in fixes:s=re.sub(pat,rep,s)
    return s

def slugify(s):
    s=unicodedata.normalize('NFKD',s).encode('ascii','ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+','-',s).strip('-')[:175].strip('-')

for p in data['products']:
    for key in ('title','excerpt','content'):
        p[key]=clean_text(p.get(key,''))
    p['slug']=slugify(p['title'])

bad=[]
pat=re.compile(r'cesarean|caesarean|\breservation\b|headboard|Iberian\s+Iberian|\bCátedra\b',re.I)
for p in data['products']:
    text=' '.join(str(p.get(k,'')) for k in ('title','excerpt','content','slug'))
    m=pat.search(text)
    if m:bad.append({'id':p['id'],'match':m.group(0)})
if bad:
    raise SystemExit('remaining bad patterns: '+json.dumps(bad[:30],ensure_ascii=False))
with open(DST,'w',encoding='utf-8') as f:json.dump(data,f,ensure_ascii=False,separators=(',',':'))
print(json.dumps({'count':len(data['products']),'bad_patterns':0},ensure_ascii=False))
