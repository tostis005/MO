import json, re, html, sys, unicodedata, collections

with open('mdo-launch-products.json', encoding='utf-8') as f:
    source_products = {int(p['id']): p for p in json.load(f)['products']}
with open('mdo-launch-translations.json', encoding='utf-8') as f:
    payload = json.load(f)
translated = payload['products']

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
    s = unicodedata.normalize('NFKD', str(s)).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')

# Make every target slug vendor-qualified. This keeps identical product names from
# different producers distinct without changing the customer-visible title.
seen = set()
for tr in translated:
    pid = int(tr['id'])
    base = slugify(tr.get('title', ''))[:145].strip('-')
    vendor = slugify(tr.get('vendor', 'product'))[:30].strip('-') or 'product'
    candidate = f'{base}-{vendor}'.strip('-')
    if not candidate:
        candidate = f'product-{pid}'
    if candidate in seen:
        candidate = f'{candidate}-{pid}'
    while candidate in seen:
        candidate = f'{candidate}-{pid}'
    tr['slug'] = candidate[:190].strip('-')
    seen.add(tr['slug'])

qa = []
for tr in translated:
    pid = int(tr['id'])
    src = source_products[pid]
    src_all = (src.get('title', '') or '') + ' ' + (src.get('content', '') or '') + ' ' + (src.get('excerpt', '') or '')
    dst_all = (tr.get('title', '') or '') + ' ' + (tr.get('content', '') or '') + ' ' + (tr.get('excerpt', '') or '')
    if nums(src_all) != nums(dst_all):
        qa.append({'id': pid, 'type': 'numbers'})
    if ecodes(src_all) != ecodes(dst_all):
        qa.append({'id': pid, 'type': 'e-codes'})
    if tags(src.get('content', '') or '') != tags(tr.get('content', '') or ''):
        qa.append({'id': pid, 'type': 'content-html-tags'})
    if tags(src.get('excerpt', '') or '') != tags(tr.get('excerpt', '') or ''):
        qa.append({'id': pid, 'type': 'excerpt-html-tags'})
    hints = spanish_hint.findall(plain_visible(dst_all))
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
payload['slug_strategy'] = 'english-title-plus-vendor'
with open('mdo-launch-translations.json', 'w', encoding='utf-8') as f:
    json.dump(payload, f, ensure_ascii=False)

print('Final slug and structural QA issues:', len(qa), flush=True)
for issue in qa[:120]:
    print('QA', json.dumps(issue, ensure_ascii=False), flush=True)
if qa:
    sys.exit(22)
print('Finalized unique English slugs:', len(slugs), flush=True)
