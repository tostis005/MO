import json, re, html, sys, unicodedata, collections
import torch
from transformers import MarianMTModel, MarianTokenizer

MODEL = 'Helsinki-NLP/opus-mt-es-en'
with open('mdo-launch-products.json', encoding='utf-8') as f:
    source_products = {int(p['id']): p for p in json.load(f)['products']}
with open('mdo-launch-translations.json', encoding='utf-8') as f:
    payload = json.load(f)
translated = payload['products']

tag_re = re.compile(r'(<[^>]+>)', re.S)
alpha_re = re.compile(r'[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]')
# Preserve nutrition values, weights, percentages and additive codes literally.
tech_re = re.compile(r'(\bE\s*-?\s*\d+[A-Z]*\b|\d+(?:[.,]\d+)?(?:\s*(?:%|°C|ºC))?)', re.I)
spanish_hint = re.compile(r'\b(?:para|desde|hasta|que|los|las|una|unos|unas|producto|almacenamiento|consumo|ingredientes|conservaci[oó]n|env[ií]o|peso|cerdo|curaci[oó]n|deshuesad[oa]|cortad[oa]|piezas|sobres|recomendamos|alimentados|lugar|meses)\b', re.I)

def plain_visible(s):
    return html.unescape(re.sub(r'<[^>]+>', ' ', s or ''))

def nums(s):
    return collections.Counter(x.replace(',', '.') for x in re.findall(r'\d+(?:[.,]\d+)?', plain_visible(s)))

def ecodes(s):
    return collections.Counter(re.sub(r'[^A-Z0-9]', '', x.upper()) for x in re.findall(r'\bE\s*-?\s*\d+[A-Z]*\b', plain_visible(s), re.I))

def tags(s):
    return re.findall(r'<[^>]+>', s or '', re.S)

def slugify(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')[:170].strip('-')

# Load the model only after we know there is repair work.
needs = []
for tr in translated:
    src = source_products[int(tr['id'])]
    for field in ('title', 'content', 'excerpt'):
        sf = src.get(field, '') or ''
        tf = tr.get(field, '') or ''
        if nums(sf) != nums(tf) or ecodes(sf) != ecodes(tf):
            needs.append((int(tr['id']), field))

print('Technical fields requiring literal-value repair:', len(needs), flush=True)
if needs:
    tok = MarianTokenizer.from_pretrained(MODEL)
    model = MarianMTModel.from_pretrained(MODEL)
    model.eval()
    torch.set_num_threads(max(1, min(4, torch.get_num_threads())))
    cache = {}

    def polish(t):
        fixes = [
            (r'\bfield bait\b', 'free-range grain-fed'), (r'\bbait field\b', 'free-range grain-fed'),
            (r'\bbait-fed\b', 'grain-fed'), (r'\bbait\b', 'grain-fed'),
            (r'\bIberian race\b', 'Iberian breed'), (r'\bpalette(s)?\b', lambda m: 'shoulder hams' if m.group(1) else 'shoulder ham'),
            (r'\bcane of loin\b', 'cured loin'), (r'\benvelopes\b', 'packs'), (r'\bvacuum packed\b', 'vacuum-packed'),
        ]
        for pat, rep in fixes:
            t = re.sub(pat, rep, t, flags=re.I)
        return t.replace('Robles Bridge', 'Puente Robles').replace('Bridge Robles', 'Puente Robles')

    def translate_piece(s):
        if not s or not alpha_re.search(s):
            return s
        key = re.sub(r'\s+', ' ', s).strip()
        if not key:
            return s
        if key not in cache:
            inputs = tok([key], return_tensors='pt', padding=True, truncation=True, max_length=470)
            with torch.no_grad():
                outs = model.generate(**inputs, max_length=510, num_beams=3, early_stopping=True)
            cache[key] = polish(tok.batch_decode(outs, skip_special_tokens=True)[0].strip())
        lead = re.match(r'^\s*', s).group(0)
        trail = re.search(r'\s*$', s).group(0)
        return lead + cache[key] + trail

    def safe_text(src):
        # Translate only text around technical tokens; tokens themselves are copied byte-for-byte.
        parts = tech_re.split(src)
        out = []
        for i, part in enumerate(parts):
            if i % 2 == 1:
                out.append(part)
            else:
                out.append(translate_piece(part))
        return ''.join(out)

    def safe_html(src):
        parts = tag_re.split(src or '')
        out = []
        in_script = False
        for part in parts:
            if part.startswith('<'):
                low = part.lower()
                if low.startswith('<script') or low.startswith('<style'):
                    in_script = True
                elif low.startswith('</script') or low.startswith('</style'):
                    in_script = False
                out.append(part)
            elif in_script or not part.strip() or not alpha_re.search(html.unescape(part)):
                out.append(part)
            else:
                decoded = html.unescape(part)
                out.append(html.escape(safe_text(decoded), quote=False))
        return ''.join(out)

    by_id = {int(x['id']): x for x in translated}
    for pid, field in needs:
        src = source_products[pid]
        tr = by_id[pid]
        if field == 'title':
            tr['title'] = safe_text(src['title'])
            tr['slug'] = slugify(tr['title'])
        else:
            tr[field] = safe_html(src.get(field, '') or '')
        print(f'Repaired {pid} {field}', flush=True)

qa = []
for tr in translated:
    pid = int(tr['id'])
    src = source_products[pid]
    src_all = (src.get('title', '') or '') + ' ' + (src.get('content', '') or '') + ' ' + (src.get('excerpt', '') or '')
    dst_all = (tr.get('title', '') or '') + ' ' + (tr.get('content', '') or '') + ' ' + (tr.get('excerpt', '') or '')
    if nums(src_all) != nums(dst_all):
        qa.append({'id': pid, 'type': 'numbers', 'src': dict(nums(src_all)), 'dst': dict(nums(dst_all))})
    if ecodes(src_all) != ecodes(dst_all):
        qa.append({'id': pid, 'type': 'e-codes', 'src': dict(ecodes(src_all)), 'dst': dict(ecodes(dst_all))})
    if tags(src.get('content', '') or '') != tags(tr.get('content', '') or ''):
        qa.append({'id': pid, 'type': 'content-html-tags'})
    if tags(src.get('excerpt', '') or '') != tags(tr.get('excerpt', '') or ''):
        qa.append({'id': pid, 'type': 'excerpt-html-tags'})
    visible = plain_visible(dst_all)
    hints = spanish_hint.findall(visible)
    if len(hints) > 12:
        qa.append({'id': pid, 'type': 'spanish-remnants', 'count': len(hints), 'sample': hints[:25]})
    if not tr.get('title') or not tr.get('slug') or (src.get('content', '').strip() and not tr.get('content', '').strip()):
        qa.append({'id': pid, 'type': 'empty'})

slugs = [x.get('slug', '') for x in translated]
for slug, count in collections.Counter(slugs).items():
    if slug and count > 1:
        qa.append({'type': 'duplicate-slug', 'slug': slug, 'count': count})

payload['products'] = translated
payload['qa'] = qa
payload['technical_repairs'] = len(needs)
with open('mdo-launch-translations.json', 'w', encoding='utf-8') as f:
    json.dump(payload, f, ensure_ascii=False)
print('Post-repair QA issues:', len(qa), flush=True)
for issue in qa[:120]:
    print('QA', json.dumps(issue, ensure_ascii=False), flush=True)
if qa:
    sys.exit(21)
