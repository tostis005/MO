import json,re,html,time,sys,unicodedata,collections
from deep_translator import GoogleTranslator

SRC=sys.argv[1] if len(sys.argv)>1 else '/tmp/mdo-final-en-source.json'
DST=sys.argv[2] if len(sys.argv)>2 else '/tmp/mdo-final-en-output.json'
with open(SRC,encoding='utf-8') as f:data=json.load(f)
products=data['products']

GLOSSARY={
'El Catedrático':'El Catedrático','Puente Robles':'Puente Robles','Hidalgo de la Jara':'Hidalgo de la Jara','Arribes del Duero':'Arribes del Duero',
'Llévate 6 y paga 5':'Buy 6, pay for 5','Llévate seis y paga cinco':'Buy 6, pay for 5',
'paletas de cebo de campo':'free-range grain-fed shoulder hams','paletas de bellota':'acorn-fed shoulder hams','paletas de cebo':'grain-fed shoulder hams',
'paleta de cebo de campo':'free-range grain-fed shoulder ham','paleta de bellota':'acorn-fed shoulder ham','paleta de cebo':'grain-fed shoulder ham',
'jamones de cebo de campo':'free-range grain-fed hams','jamones de bellota':'acorn-fed hams','jamones de cebo':'grain-fed hams',
'jamón de cebo de campo':'free-range grain-fed ham','jamón de bellota':'acorn-fed ham','jamón de cebo':'grain-fed ham',
'caña de lomo de cebo de campo':'free-range grain-fed cured loin','caña de lomo de bellota':'acorn-fed cured loin','caña de lomo':'cured loin',
'lomos de cebo de campo':'free-range grain-fed cured loins','lomos de bellota':'acorn-fed cured loins','lomo de cebo de campo':'free-range grain-fed cured loin','lomo de bellota':'acorn-fed cured loin','lomo bellota':'acorn-fed cured loin','lomo embuchado':'cured pork loin',
'lomito de bellota':'acorn-fed presa loin','cinta de lomo':'pork loin','cabecero de lomo':'cured loin head',
'chorizos culares de bellota':'acorn-fed cular chorizos','chorizo cular de bellota':'acorn-fed cular chorizo','chorizo cular':'cular chorizo','chorizo de bellota':'acorn-fed chorizo',
'salchichones culares de bellota':'acorn-fed cular salchichones','salchichón cular de bellota':'acorn-fed cular salchichón','salchichón cular':'cular salchichón','salchichón de bellota':'acorn-fed salchichón',
'virutas de jamón':'ham shavings','tacos de jamón':'ham chunks','taco de jamón':'ham chunk','tabla jamonera':'ham stand','cuña de queso':'cheese wedge',
'cortado a cuchillo por profesional':'hand-sliced by a professional','cortada a cuchillo por profesional':'hand-sliced by a professional',
'cortado a cuchillo':'hand-sliced','cortada a cuchillo':'hand-sliced','cortado a máquina':'machine-sliced','cortada a máquina':'machine-sliced',
'envasado al vacío':'vacuum-packed','envasada al vacío':'vacuum-packed','envasados al vacío':'vacuum-packed','envasadas al vacío':'vacuum-packed','envasado a vacío':'vacuum-packed',
'pura raza ibérica':'pure Iberian breed','raza ibérica':'Iberian breed','cerdos ibéricos':'Iberian pigs','cerdo ibérico':'Iberian pig',
'cebo de campo':'free-range grain-fed','cebo':'grain-fed','bellotas':'acorns','bellota':'acorn','dehesa':'dehesa pasture','montanera':'montanera season',
'proceso de curación':'curing process','tiempo de curación':'curing time','meses de curación':'months of curing','curación':'curing','secaderos naturales':'natural drying rooms','secadero natural':'natural drying room',
'sentar cátedra':'set the standard','sienta cátedra':'sets the standard','sentar catedra':'set the standard','sienta catedra':'sets the standard','buen hacer':'craftsmanship',
'sobres':'packs','sobre':'pack','codillo':'ham hock','punta':'end piece','paletas':'shoulder hams','paleta':'shoulder ham','jamones':'hams','jamón':'ham','lomos':'cured loins','lomo':'cured loin','lomito':'presa loin',
'deshuesados':'boneless','deshuesadas':'boneless','deshuesado':'boneless','deshuesada':'boneless','loncheado':'sliced','loncheada':'sliced','loncheados':'sliced','loncheadas':'sliced',
'en caja de madera':'in a wooden box','en tubo':'in a presentation tube','lotes':'selections','lote':'selection','surtido':'assortment','degustación':'tasting','cata':'tasting',
'pimentón':'paprika','conservador':'preservative','conservadores':'preservatives','antioxidantes':'antioxidants','estabilizantes':'stabilisers','regulador de acidez':'acidity regulator',
'ingredientes':'ingredients','información nutricional':'nutritional information','valor energético':'energy','hidratos de carbono':'carbohydrates','proteínas':'protein',
'conservación y caducidad':'storage and shelf life','conservación':'storage','caducidad':'shelf life','recomendaciones':'recommendations','alimentación':'diet','forma de envío':'shipping format','envío':'shipping','peso':'weight',
'modelo bellota':'Bellota model','herradura':'horseshoe-shaped'
}
GLOSSARY_ITEMS=sorted(GLOSSARY.items(),key=lambda kv:len(kv[0]),reverse=True)
TAG_RE=re.compile(r'(<[^>]+>)',re.S)
ALPHA_RE=re.compile(r'[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]')
NUM_RE=re.compile(r'\d+(?:[.,]\d+)?')
MANUAL_TITLES={'La caja del estudiante':'The Student Box','Cátedra ibérica':'Iberian Masterclass','Universidad ibérica':'Iberian University','La cátedra del sabor':'The Flavour Masterclass','El catedrático selección':'El Catedrático Selection','El catedrático gourmet':'El Catedrático Gourmet','Catedrático degustación':'El Catedrático Tasting Selection','Tabla jamonera modelo bellota 3':'Bellota Model 3 Ham Stand'}

def alpha_code(n):
    s=''
    while True:
        n,r=divmod(n,26);s=chr(65+r)+s
        if n==0:return s
        n-=1

def norm(s):return re.sub(r'\s+',' ',html.unescape(str(s))).strip()
def num_counter(s):return collections.Counter(x.replace(',','.') for x in NUM_RE.findall(html.unescape(str(s))))
def num_total(s):return sum(num_counter(s).values())

def protect(s):
    repl={};idx=0
    for src,en in GLOSSARY_ITEMS:
        pat=re.compile(r'(?<!\w)'+re.escape(src)+r'(?!\w)',re.I)
        while True:
            m=pat.search(s)
            if not m:break
            token='ZZTERM'+alpha_code(idx)+'ZZ';idx+=1
            s=s[:m.start()]+token+s[m.end():];repl[token]=en
    nrepl={};nidx=0
    def nsub(m):
        nonlocal nidx
        token='ZZNUM'+alpha_code(nidx)+'ZZ';nidx+=1;nrepl[token]=m.group(0);return token
    s=NUM_RE.sub(nsub,s);repl.update(nrepl);return s,repl

def restore(s,repl):
    for token,val in repl.items():s=re.sub(re.escape(token),lambda m:val,s,flags=re.I)
    return s

def polish(s):
    fixes=[(r'\bTake 6 and pay 5\b','Buy 6, pay for 5'),(r'\bBuy 6 and pay 5\b','Buy 6, pay for 5'),(r'\bsachets\b','packs'),(r'\benvelopes\b','packs'),(r'\bpalette(s)?\b',lambda m:'shoulder hams' if m.group(1) else 'shoulder ham'),(r'\bpallets\b','shoulder hams'),(r'\bpallet\b','shoulder ham'),(r'\bIberian race\b','Iberian breed'),(r'\bIberic race\b','Iberian breed'),(r'\bhealing process\b','curing process'),(r'\bhealing time\b','curing time'),(r'\bhealing\b','curing'),(r'\bcattle ranches\b','farms'),(r'\bbred in freedom\b','raised free-range'),(r'\braised in freedom\b','raised free-range'),(r'\bpreferential consumption\b','best before'),(r'\bpreference consumption\b','best before'),(r'\bgrs\.?\b','g'),(r'\bgrams\b','g'),(r'\bKj\b','kJ'),(r'\bKcal\b','kcal'),(r'\bconservatives\b','preservatives'),(r'\bconservative\b','preservative'),(r'\bcarbon hydrates\b','carbohydrates'),(r'\bsit chair\b','set the standard'),(r'\bsits chair\b','sets the standard'),(r'\bset chair\b','set the standard'),(r'\bsausage table\b','charcuterie board'),(r'\bempty-packed\b','vacuum-packed'),(r'\bpacked into the vacuum\b','vacuum-packed'),(r'\bpacked to the vacuum\b','vacuum-packed'),(r'\bat a single price\b','at a special price'),(r'\bRaza Ibérica\b','Iberian breed'),(r'\bRaza Iberica\b','Iberian breed')]
    for pat,rep in fixes:s=re.sub(pat,rep,s,flags=re.I)
    s=re.sub(r'(?<=\d),(?=\d)', '.', s);s=re.sub(r'\s+([,.;:!?])',r'\1',s);s=re.sub(r' {2,}',' ',s);return s.strip()

def gtranslate_atomic(s):
    if not s or not ALPHA_RE.search(s):return s
    protected,repl=protect(s);err=None
    for attempt in range(6):
        try:
            raw=GoogleTranslator(source='es',target='en').translate(protected)
            if raw:
                out=polish(restore(raw,repl))
                if num_counter(s)==num_counter(out):return out
                err=RuntimeError('numeric token integrity mismatch')
        except Exception as e:err=e
        time.sleep(min(8,1.2*(attempt+1)))
    raise RuntimeError(f'atomic translation failed/integrity mismatch: {err}: {s[:180]}')

def split_once(text, pattern):
    parts=re.split(pattern,text)
    return [p.strip() for p in parts if p and p.strip()]

def hard_chunks(text,max_chars=520):
    words=text.split();out=[];cur=[];n=0
    for w in words:
        extra=len(w)+(1 if cur else 0)
        if cur and n+extra>max_chars:
            out.append(' '.join(cur));cur=[w];n=len(w)
        else:
            cur.append(w);n+=extra
    if cur:out.append(' '.join(cur))
    return out

def safe_chunks(s,max_chars=650,max_nums=7):
    s=norm(s)
    if len(s)<=max_chars and num_total(s)<=max_nums:return [s]
    # Sentence boundaries first; then semicolons/colons; then commas; finally whitespace.
    candidates=split_once(s,r'(?<=[.!?])\s+')
    if len(candidates)==1:candidates=split_once(s,r'(?<=[;])\s*')
    if len(candidates)==1:candidates=split_once(s,r'(?<=:)\s+')
    if len(candidates)==1 and (len(s)>max_chars or num_total(s)>max_nums):candidates=split_once(s,r'(?<=[,])\s+')
    refined=[]
    for p in candidates:
        if len(p)<=max_chars and num_total(p)<=max_nums:refined.append(p);continue
        sub=split_once(p,r'(?<=[;:])\s*')
        if len(sub)==1 and num_total(p)>max_nums:sub=split_once(p,r'(?<=[,])\s+')
        if len(sub)==1:sub=hard_chunks(p,max_chars)
        for q in sub:
            if len(q)>max_chars:refined.extend(hard_chunks(q,max_chars))
            else:refined.append(q)
    # If a chunk still has too many numbers, split greedily at commas/whitespace.
    final=[]
    for p in refined:
        if num_total(p)<=max_nums:final.append(p);continue
        comma=split_once(p,r'(?<=[,])\s+')
        if len(comma)>1:
            cur=[];count=0
            for q in comma:
                qc=num_total(q)
                if cur and count+qc>max_nums:
                    final.append(' '.join(cur));cur=[q];count=qc
                else:cur.append(q);count+=qc
            if cur:final.append(' '.join(cur))
        else:
            # Numeric-dense nutrition text: short whitespace chunks are safest.
            final.extend(hard_chunks(p,260))
    return [x for x in final if x]

def gtranslate(s):
    chunks=safe_chunks(s)
    if len(chunks)==1:return gtranslate_atomic(chunks[0])
    translated=[]
    for ch in chunks:translated.append(gtranslate_atomic(ch))
    out=' '.join(translated)
    if num_counter(s)!=num_counter(out):raise RuntimeError(f'chunk reassembly numeric mismatch: {s[:180]}')
    return polish(out)

cache={}
def collect_html(s,bag):
    if not s:return
    in_skip=False
    for part in TAG_RE.split(s):
        if part.startswith('<'):
            low=part.lower()
            if low.startswith('<script') or low.startswith('<style'):in_skip=True
            elif low.startswith('</script') or low.startswith('</style'):in_skip=False
            continue
        if in_skip:continue
        k=norm(part)
        if k and ALPHA_RE.search(k):bag.add(k)

def translate_bundle(items):
    # Never batch numeric-rich or long blocks: translate them via safe_chunks individually.
    if len(items)==1 or any(num_total(x)>4 or len(x)>700 for x in items):return [gtranslate(x) for x in items]
    markers=['ZZSEG'+alpha_code(i)+'ZZ' for i in range(len(items)-1)];combined=''
    for i,x in enumerate(items):
        if i:combined+='\n'+markers[i-1]+'\n'
        combined+=x
    try:
        out=gtranslate_atomic(combined)
        pattern=r'\s*(?:'+'|'.join(re.escape(m) for m in markers)+r')\s*'
        pieces=re.split(pattern,out,flags=re.I)
        if len(pieces)==len(items):
            pieces=[polish(x) for x in pieces]
            if all(num_counter(src)==num_counter(dst) for src,dst in zip(items,pieces)):return pieces
    except Exception:pass
    return [gtranslate(x) for x in items]

def fill_cache(strings):
    pending=[s for s in strings if s not in cache];pending.sort(key=len);bundle=[];chars=0;done=0
    def flush():
        nonlocal bundle,chars,done
        if not bundle:return
        vals=translate_bundle(bundle)
        for k,v in zip(bundle,vals):cache[k]=v
        done+=len(bundle);print(f'TRANSLATED {done}/{len(pending)}',flush=True);bundle=[];chars=0
    for s in pending:
        # Numeric-rich and long content is handled alone to prevent cross-segment token movement.
        if num_total(s)>4 or len(s)>700:
            flush();cache[s]=gtranslate(s);done+=1;print(f'TRANSLATED {done}/{len(pending)}',flush=True);continue
        extra=len(s)+18
        if bundle and (len(bundle)>=10 or chars+extra>2400):flush()
        bundle.append(s);chars+=extra
    flush()

def translate_html(src):
    if not src:return ''
    parts=TAG_RE.split(src);out=[];in_skip=False
    for part in parts:
        if part.startswith('<'):
            low=part.lower()
            if low.startswith('<script') or low.startswith('<style'):in_skip=True
            elif low.startswith('</script') or low.startswith('</style'):in_skip=False
            out.append(part);continue
        if in_skip:out.append(part);continue
        decoded=html.unescape(part);m1=re.match(r'^\s*',decoded);m2=re.search(r'\s*$',decoded);lead=m1.group(0);trail=m2.group(0);end=len(decoded)-len(trail) if trail else len(decoded);core=decoded[len(lead):end];k=norm(core)
        if not k or not ALPHA_RE.search(k):out.append(part);continue
        out.append(lead+html.escape(cache[k],quote=False)+trail)
    return ''.join(out)

def slugify(s):
    s=unicodedata.normalize('NFKD',s).encode('ascii','ignore').decode().lower();return re.sub(r'[^a-z0-9]+','-',s).strip('-')[:175].strip('-')

strings=set()
for p in products:
    strings.add(norm(p['title']));collect_html(p.get('excerpt',''),strings);collect_html(p.get('content',''),strings)
fill_cache(strings)
out=[]
for p in products:
    src_title=norm(p['title']);en_title=MANUAL_TITLES.get(src_title,cache[src_title]);en_title=polish(en_title);en_title=re.sub(r'^Selection\s+','',en_title,flags=re.I) if src_title.lower().startswith('lote puente robles') else en_title;en_title=en_title[0].upper()+en_title[1:] if en_title else en_title
    out.append({'id':p['id'],'author_id':p['author_id'],'vendor':p['vendor'],'source_status':p['status'],'title':en_title,'slug':slugify(en_title),'excerpt':translate_html(p.get('excerpt','')),'content':translate_html(p.get('content',''))})
payload={'source_site':data.get('site'),'products':out}
with open(DST,'w',encoding='utf-8') as f:json.dump(payload,f,ensure_ascii=False,separators=(',',':'))
print('OUTPUT',json.dumps({'count':len(out),'vendors':{v:sum(1 for p in out if p['vendor']==v) for v in sorted(set(p['vendor'] for p in out))}},ensure_ascii=False),flush=True)
