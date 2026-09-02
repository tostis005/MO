from pathlib import Path
import base64, zlib, json, re, importlib.util, sys, hashlib
BASE=Path(__file__).resolve().parent

# Recover the five complete articles from the interrupted compressed transfer.
parts=[]
for n in (1,2):
    parts.append(''.join((BASE/f'authority-gaps-20260902.part{n}.b64').read_text(encoding='utf-8').split()))
joined=''.join(parts)
raw=base64.b64decode(joined + '='*((-len(joined))%4))
z=zlib.decompressobj(31)
prefix=z.decompress(raw)+z.flush()
text=prefix.decode('utf-8',errors='strict')
dec=json.JSONDecoder(); idx=0; recovered=[]
while idx<len(text) and text[idx].isspace(): idx+=1
if idx>=len(text) or text[idx]!='[': raise RuntimeError('Recovered stream does not start with an array')
idx+=1
while True:
    while idx<len(text) and (text[idx].isspace() or text[idx]==','): idx+=1
    if idx>=len(text) or text[idx]==']': break
    try:
        val,end=dec.raw_decode(text,idx)
    except json.JSONDecodeError:
        break
    recovered.append(val); idx=end
if [int(a.get('pos',-1)) for a in recovered] != [12,14,18,20,21]:
    raise RuntimeError('Unexpected recovered positions: '+repr([a.get('pos') for a in recovered]))

# Two articles rebuilt manually from the interrupted sixth object and verified source material.
manual=json.loads((BASE/'authority-gaps-data-25-45.json').read_text(encoding='utf-8'))

# Fourteen deterministic regional/origin articles.
spec=importlib.util.spec_from_file_location('authority_regional', BASE/'generate-authority-gaps-regional-20260902.py')
mod=importlib.util.module_from_spec(spec); spec.loader.exec_module(mod)
regional=sorted([mod.cheese(p,mod.CHEESE[p]) for p in sorted(mod.CHEESE)] + [mod.oil(p,mod.OILS[p]) for p in sorted(mod.OILS)] + [mod.special90()], key=lambda x:int(x['pos']))

articles=sorted(recovered+manual+regional,key=lambda x:int(x['pos']))
expected=[12,14,18,20,21,25,45,67,68,70,73,74,75,82,83,84,85,86,87,88,90]
positions=[int(a['pos']) for a in articles]
if positions!=expected: raise RuntimeError(f'Positions mismatch: {positions}')
if sum(a['category']=='quesos' for a in articles)!=13: raise RuntimeError('Expected 13 cheese articles')
if sum(a['category']=='aceites' for a in articles)!=8: raise RuntimeError('Expected 8 oil articles')
required={'pos','category','title','slug','en_title','en_slug','excerpt','en_excerpt','lead_es','lead_en','facts_es','facts_en','sections_es','sections_en','faq_es','faq_en','conclusion_es','conclusion_en','related','sources'}
if len({a['slug'] for a in articles})!=21 or len({a['en_slug'] for a in articles})!=21: raise RuntimeError('Duplicate slug')

def words(s): return len(re.findall(r"[A-Za-zÀ-ÖØ-öø-ÿ0-9]+(?:[’'-][A-Za-zÀ-ÖØ-öø-ÿ0-9]+)*",s))
for a in articles:
    missing=required-set(a)
    if missing: raise RuntimeError(f"Missing fields {missing} in {a.get('slug')}")
    if a['category'] not in ('quesos','aceites'): raise RuntimeError('Bad category '+a['slug'])
    if len(a['sections_es'])<6 or len(a['sections_en'])<6: raise RuntimeError('Too few sections '+a['slug'])
    if len(a['sources'])<2 or len(a['faq_es'])<1 or len(a['faq_en'])<1: raise RuntimeError('Missing editorial support '+a['slug'])
    es=words(a['lead_es']+' '+' '.join(str(x[1]) for x in a['sections_es'])+' '+a['conclusion_es'])
    en=words(a['lead_en']+' '+' '.join(str(x[1]) for x in a['sections_en'])+' '+a['conclusion_en'])
    if es<620 or en<520: raise RuntimeError(f"Article too short {a['slug']} ES={es} EN={en}")
    a['_validation_words_es']=es; a['_validation_words_en']=en

payload=json.dumps(articles,ensure_ascii=False,separators=(',',':'))
out=Path(sys.argv[1]) if len(sys.argv)>1 else BASE/'authority-gaps-all-20260902.json'
out.write_text(payload,encoding='utf-8')
print('AUTHORITY_GAPS_PACKAGE_OK=21')
print('POSITIONS='+','.join(map(str,positions)))
print('SHA256='+hashlib.sha256(payload.encode()).hexdigest())
for a in articles: print(f"OK pos={a['pos']} cat={a['category']} ES={a['_validation_words_es']} EN={a['_validation_words_en']} slug={a['slug']}")
