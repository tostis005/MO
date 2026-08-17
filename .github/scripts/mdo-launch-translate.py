import json, re, html, unicodedata, collections, sys
import torch
from transformers import MarianMTModel, MarianTokenizer

MODEL = 'Helsinki-NLP/opus-mt-es-en'
with open('mdo-launch-products.json', encoding='utf-8') as f:
    products = json.load(f)['products']

manual = {
    'Producto cárnico.':'Meat product.',
    'Tiempo de curación':'Curing time','Almacenamiento':'Storage','Alimentación':'Diet','Consumo':'Serving','Consumo.':'Serving.',
    'VALORES NUTRICIONALES':'NUTRITIONAL VALUES','Valores nutricionales':'Nutritional values','VALOR MEDIO POR 100 G.':'AVERAGE VALUE PER 100 G','Valor medio por 100 g.':'Average value per 100 g.',
    'INFORMACIÓN NUTRICIONAL POR 100G:':'NUTRITIONAL INFORMATION PER 100 G:','Valor energético:':'Energy:','Valor Energético (Kcal.)':'Energy (kcal)','Valor Energético (Kj.)':'Energy (kJ)',
    'Hidratos de carbono':'Carbohydrates','Hidratos de carbono:':'Carbohydrates:','De los cuales azúcares':'of which sugars','de los cuales azúcares:':'of which sugars:',
    'Grasas':'Fat','Grasas:':'Fat:','Grasa:':'Fat:','De las cuales ácidos grasos saturados':'of which saturates','de las cuales ácidos grasos saturados:':'of which saturates:',
    'Proteínas':'Protein','Proteínas:':'Protein:','Sal':'Salt','Sal:':'Salt:','VALOR:':'VALUE:',
    'ENVÍO:':'SHIPPING:','ENVÍO':'SHIPPING','Envío':'Shipping','PESO:':'WEIGHT:','PESO':'WEIGHT','Peso':'Weight',
    'CONSERVACIÓN Y CADUCIDAD:':'STORAGE AND SHELF LIFE:','CONSERVACIÓN Y CADUCIDAD':'STORAGE AND SHELF LIFE','Conservación y Caducidad':'Storage and shelf life','Conservación y caducidad':'Storage and shelf life',
    'RECOMENDACIONES:':'RECOMMENDATIONS:','RECOMENDACIONES':'RECOMMENDATIONS','Recomendaciones':'Recommendations',
    'CERTIFICACIÓN':'CERTIFICATION','Certificación':'Certification','Ingredientes':'Ingredients','INGREDIENTES':'INGREDIENTS','Premios':'Awards','Precauciones':'Precautions','Forma de envío':'Shipping format',
    'Saborea la excelencia en cada bocado.':'Taste excellence in every bite.','Saborea la excelencia del ibérico en cada bocado.':'Taste the excellence of Iberian cured meats in every bite.',
    'Consumo preferente: 6 meses.':'Best before: 6 months.','Consumo preferente: 6 meses':'Best before: 6 months.','Consumo preferente: 12 meses.':'Best before: 12 months.',
    'Consumo preferente: 13 meses.':'Best before: 13 months.','Consumo preferente: 13 meses':'Best before: 13 months.','Consumo preferente: 16 meses.':'Best before: 16 months.',
    'Consumo preferente: 16 meses':'Best before: 16 months.','Consumo preferente: 24 meses.':'Best before: 24 months.','Consumo preferente: 24 meses':'Best before: 24 months',
    'Envasado al vacío en sobres individuales.':'Vacuum-packed in individual packs.','Envasados al vacío.':'Vacuum-packed.','Envasada al vacío.':'Vacuum-packed.','Todas las piezas van envasadas al vacío.':'All pieces are vacuum-packed.',
    'Ubicarlo en un lugar fresco y seco que esté protegido de la luz solar directa.':'Store in a cool, dry place protected from direct sunlight.',
    'Ubicarlos en un lugar fresco y seco que esté protegido de la luz solar directa.':'Store in a cool, dry place protected from direct sunlight.',
    'Mantener en un lugar fresco y seco protegido de la luz solar directa.':'Store in a cool, dry place protected from direct sunlight.',
    'Conservar en un lugar fresco y seco, protegido de la luz solar directa.':'Store in a cool, dry place protected from direct sunlight.',
    'Mantener en un lugar refrigerado entre 2 y 6 grados.':'Keep refrigerated between 2°C and 6°C.',
    'Cereales y bellotas.':'Cereals and acorns.','Cereales, piensos y bellotas.':'Cereals, natural feed and acorns.',
    'Cereales, hierbas y plantas silvestres.':'Cereals, grasses and wild plants.','Hierbas, plantas silvestres y bellotas.':'Grasses, wild plants and acorns.',
    'Piensos naturales.':'Natural feed.','Cereales y pienso natural.':'Cereals and natural feed.','Cereales y piensos naturales.':'Cereals and natural feed.',
    '¡PRIMER PREMIO AL MEJOR CHORIZO DEL MUNDO!':'FIRST PRIZE FOR THE BEST CHORIZO IN THE WORLD!',
}

brand_tokens = {
    'Puente Robles':'MDOZZPUENTEROBLESZZ',
    'El Catedrático':'MDOZZELCATEDRATICOZZ',
    'El Catedratico':'MDOZZELCATEDRATICOZZ',
    'EL CATEDRATICO':'MDOZZELCATEDRATICOZZ',
    'Arribes del Duero':'MDOZZARRIBESZZ',
}
restore_tokens = {
    'MDOZZPUENTEROBLESZZ':'Puente Robles',
    'MDOZZELCATEDRATICOZZ':'El Catedrático',
    'MDOZZARRIBESZZ':'Arribes del Duero',
}

tok = MarianTokenizer.from_pretrained(MODEL)
model = MarianMTModel.from_pretrained(MODEL)
model.eval()
torch.set_num_threads(max(1, min(4, torch.get_num_threads())))

tag_re = re.compile(r'(<[^>]+>)', re.S)
alpha_re = re.compile(r'[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]')
spanish_hint = re.compile(r'\b(?:para|desde|hasta|que|los|las|una|unos|unas|producto|almacenamiento|consumo|ingredientes|conservaci[oó]n|env[ií]o|peso|cerdo|curaci[oó]n|deshuesad[oa]|cortad[oa]|piezas|sobres|recomendamos|alimentados|lugar|meses)\b', re.I)

def norm(s):
    return re.sub(r'\s+', ' ', html.unescape(str(s))).strip()

def protect(s):
    for src, token in brand_tokens.items():
        s = s.replace(src, token)
    return s

def restore(s):
    for token, dst in restore_tokens.items():
        s = re.sub(re.escape(token), dst, s, flags=re.I)
    return s

def chunks_for_long(s, limit_words=90):
    if len(s.split()) <= limit_words:
        return [s]
    pieces = re.split(r'(?<=[.!?])\s+', s)
    out, cur, n = [], [], 0
    for p in pieces:
        w = len(p.split())
        if cur and n + w > limit_words:
            out.append(' '.join(cur)); cur, n = [p], w
        else:
            cur.append(p); n += w
    if cur: out.append(' '.join(cur))
    return out

def polish(t):
    fixes = [
        (r'\bfield bait\b','free-range grain-fed'),(r'\bbait field\b','free-range grain-fed'),(r'\bbait-fed\b','grain-fed'),(r'\bbait\b','grain-fed'),
        (r'\bIberian race\b','Iberian breed'),(r'\bIberian breed breed\b','Iberian breed'),
        (r'\bpalette(s)?\b',lambda m:'shoulder hams' if m.group(1) else 'shoulder ham'),
        (r'\bacorn ham\b','acorn-fed ham'),(r'\bacorn Iberian ham\b','acorn-fed Iberian ham'),
        (r'\bshoulder palette\b','shoulder ham'),(r'\bcane of loin\b','cured loin'),
        (r'\bfeed and cereals\b','natural feed and cereals'),(r'\benvelopes\b','packs'),
        (r'\bvacuum packed\b','vacuum-packed'),(r'\bSpanish race\b','Iberian breed'),
    ]
    for pat, rep in fixes:
        t = re.sub(pat, rep, t, flags=re.I)
    t = t.replace('Arribes of the Duero','Arribes del Duero').replace('Arribes de Duero','Arribes del Duero')
    t = t.replace('Robles Bridge','Puente Robles').replace('Bridge Robles','Puente Robles')
    t = restore(t)
    return t

cache = {}
def translate_batch(strings):
    pending = []
    for s in strings:
        n = norm(s)
        if not n or not alpha_re.search(n): cache.setdefault(n, n); continue
        if n in manual: cache[n] = manual[n]; continue
        if n not in cache: pending.append(n)
    pending = list(dict.fromkeys(pending))
    for start in range(0, len(pending), 20):
        batch = pending[start:start+20]
        expanded, owners = [], []
        for idx, s in enumerate(batch):
            for pc in chunks_for_long(protect(s)):
                expanded.append(pc); owners.append(idx)
        inputs = tok(expanded, return_tensors='pt', padding=True, truncation=True, max_length=470)
        with torch.no_grad():
            outs = model.generate(**inputs, max_length=510, num_beams=3, early_stopping=True)
        decoded = tok.batch_decode(outs, skip_special_tokens=True)
        grouped = [[] for _ in batch]
        for owner, text in zip(owners, decoded):
            grouped[owner].append(polish(text.strip()))
        for src, parts in zip(batch, grouped):
            cache[src] = ' '.join(parts).strip()
        print(f'Translated strings {min(start+20,len(pending))}/{len(pending)}', flush=True)

def translate_plain(s):
    n = norm(s)
    if not n: return ''
    if n not in cache: translate_batch([n])
    return cache[n]

def translate_html(src):
    if not src: return ''
    parts = tag_re.split(src); out = []; in_script = False
    for part in parts:
        if part.startswith('<'):
            low = part.lower()
            if low.startswith('<script') or low.startswith('<style'): in_script = True
            elif low.startswith('</script') or low.startswith('</style'): in_script = False
            out.append(part); continue
        if in_script or not part.strip() or not alpha_re.search(html.unescape(part)):
            out.append(part); continue
        decoded = html.unescape(part)
        lead = re.match(r'^\s*', decoded).group(0)
        trail = re.search(r'\s*$', decoded).group(0)
        core = decoded[len(lead):len(decoded)-len(trail) if trail else None]
        out.append(lead + html.escape(translate_plain(core), quote=False) + trail)
    return ''.join(out)

def canonical_title(src, fallback):
    s = src.strip(); suffix = ''
    suffixes = {
        '(Deshuesado)':'(Boneless)','(Deshuesada)':'(Boneless)',
        '(Cortado a cuchillo)':'(Hand-sliced)','(Cortada a cuchillo)':'(Hand-sliced)',
        '(Cortado a máquina)':'(Machine-sliced)','(Cortada a máquina)':'(Machine-sliced)',
    }
    for es, en in suffixes.items():
        if es.lower() in s.lower():
            s = re.sub(re.escape(es), '', s, flags=re.I).strip(); suffix = ' ' + en; break
    patterns = [
        (r'^Jam[oó]n de bellota ib[eé]rico 100% raza ib[eé]rica$', '100% Iberian acorn-fed ham'),
        (r'^Jam[oó]n de bellota ib[eé]rico 75% raza ib[eé]rica$', '75% Iberian acorn-fed ham'),
        (r'^Jam[oó]n de bellota ib[eé]rico 50% raza ib[eé]rica$', '50% Iberian acorn-fed ham'),
        (r'^Jam[oó]n de cebo de campo ib[eé]rico 50% raza ib[eé]rica$', '50% Iberian free-range grain-fed ham'),
        (r'^Jam[oó]n de cebo ib[eé]rico 50% raza ib[eé]rica$', '50% Iberian grain-fed ham'),
        (r'^Paleta de bellota ib[eé]ric[oa] 100% raza ib[eé]rica$', '100% Iberian acorn-fed shoulder ham'),
        (r'^Paleta de bellota ib[eé]ric[oa] 75% raza ib[eé]rica$', '75% Iberian acorn-fed shoulder ham'),
        (r'^Paleta de bellota ib[eé]ric[oa] 50% raza ib[eé]rica$', '50% Iberian acorn-fed shoulder ham'),
        (r'^Paleta de cebo de campo ib[eé]ric[oa] 50% raza ib[eé]rica$', '50% Iberian free-range grain-fed shoulder ham'),
        (r'^Paleta de cebo ib[eé]ric[oa] 50% raza ib[eé]rica$', '50% Iberian grain-fed shoulder ham'),
    ]
    for pat, en in patterns:
        if re.match(pat, s, re.I): return en + suffix
    return polish(fallback) + suffix

def slugify(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii','ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+','-',s).strip('-')[:170].strip('-')

def nums(s):
    text = html.unescape(re.sub(r'<[^>]+>', ' ', s or ''))
    return collections.Counter(x.replace(',','.') for x in re.findall(r'\d+(?:[.,]\d+)?', text))

def ecodes(s):
    text = html.unescape(re.sub(r'<[^>]+>', ' ', s or ''))
    return collections.Counter(re.sub(r'[^A-Z0-9]','',x.upper()) for x in re.findall(r'\bE\s*-?\s*\d+[A-Z]*\b', text, re.I))

# Deduplicate model work across titles, excerpts and every visible HTML text node.
all_strings = []
for p in products:
    all_strings += [p['title'], p.get('excerpt','')]
    in_script = False
    for part in tag_re.split(p.get('content','') or ''):
        if part.startswith('<'):
            low = part.lower()
            if low.startswith('<script') or low.startswith('<style'): in_script = True
            elif low.startswith('</script') or low.startswith('</style'): in_script = False
            continue
        if not in_script and norm(part): all_strings.append(norm(part))
translate_batch(all_strings)

out, qa = [], []
for p in products:
    raw_title = translate_plain(p['title'])
    en_title = canonical_title(p['title'], raw_title)
    en_content = translate_html(p.get('content',''))
    en_excerpt = translate_html(p.get('excerpt',''))
    en_slug = slugify(en_title)
    src_all = (p['title'] or '') + ' ' + (p.get('content','') or '') + ' ' + (p.get('excerpt','') or '')
    dst_all = en_title + ' ' + en_content + ' ' + en_excerpt
    if nums(src_all) != nums(dst_all): qa.append({'id':p['id'],'type':'numbers','src':dict(nums(src_all)),'dst':dict(nums(dst_all))})
    if ecodes(src_all) != ecodes(dst_all): qa.append({'id':p['id'],'type':'e-codes','src':dict(ecodes(src_all)),'dst':dict(ecodes(dst_all))})
    visible = html.unescape(re.sub(r'<[^>]+>', ' ', dst_all))
    hints = spanish_hint.findall(visible)
    if len(hints) > 12: qa.append({'id':p['id'],'type':'spanish-remnants','count':len(hints),'sample':hints[:25]})
    if not en_title or not en_slug or (p.get('content','').strip() and not en_content.strip()): qa.append({'id':p['id'],'type':'empty'})
    out.append({'id':p['id'],'author_id':p['author_id'],'vendor':p['vendor'],'source_status':p['status'],'title':en_title,'content':en_content,'excerpt':en_excerpt,'slug':en_slug})

with open('mdo-launch-translations.json','w',encoding='utf-8') as f:
    json.dump({'products':out,'qa':qa,'translation_cache_size':len(cache)},f,ensure_ascii=False)
print('Translated products:', len(out))
print('Unique translated text chunks:', len(cache))
print('QA issues:', len(qa))
for issue in qa[:120]: print('QA', json.dumps(issue,ensure_ascii=False))
if qa: sys.exit(20)
