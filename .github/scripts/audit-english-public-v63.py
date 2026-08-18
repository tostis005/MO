#!/usr/bin/env python3
import json, re, sys, time
from collections import deque
from urllib.parse import urljoin, urlparse, urlunparse
import requests
from bs4 import BeautifulSoup

ROOT = 'https://www.elmercadodeorigen.com'
SEEDS = [
    ROOT + '/en/', ROOT + '/en/shop/', ROOT + '/en/categories/', ROOT + '/en/producers/',
    ROOT + '/en/journal/', ROOT + '/en/contact/', ROOT + '/en/cart/', ROOT + '/en/checkout/',
    ROOT + '/en/my-account/'
]
MAX_URLS = 500
TIMEOUT = 35
UA = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/126 Safari/537.36 MDO-English-QA/63'

SPANISH_BASES = {
    'tienda','producto','categoria-producto','etiqueta-producto','productores','categorias','contacto',
    'carrito','finalizar-compra','mi-cuenta','quienes-somos','politica','politica-de-cookies',
    'condiciones-especiales','acercade','variedad'
}
HIGH_CONFIDENCE = [
    r'\bMostrando\b', r'\bresultados?\b', r'\bVendido por\b', r'\bValorado con\b',
    r'\bSeleccionar opciones\b', r'\bSin existencias\b', r'\bAñadir(?: al carrito)?\b',
    r'\b¡?Oferta!?\b', r'\bNovedad\b', r'\bÚltimas unidades\b', r'\bFiltros?\b',
    r'\bBuscar productos\b', r'\bOrdenar por\b', r'\bSiguiente\b', r'\bAnterior\b',
    r'\bCarrito\b', r'\bFinalizar compra\b', r'\bMi cuenta\b', r'\bPedido mínimo\b',
    r'\bImporte mínimo\b', r'\bDetalles de facturación\b', r'\bRealizar pedido\b',
    r'\bQuiénes somos\b', r'\bTérminos y condiciones\b', r'\bPolítica de cookies\b',
    r'\bTu nombre\b', r'\bTu correo electrónico\b', r'\bTu mensaje\b', r'\bAsunto\b',
    r'\bVisitar tienda\b', r'\bAcerca de\b', r'\bPolíticas\b', r'\bCategorías del producto\b',
    r'\bEtiquetas del producto\b', r'\bEscribe para buscar\b', r'\bConsulta\b'
]
HIGH_RE = re.compile('|'.join('(?:%s)' % p for p in HIGH_CONFIDENCE), re.I)
SPANISH_STOP = set('el la los las un una unos unas de del al y o pero para por con sin en es son está estan esta este estos estas que como se su sus nuestro nuestra nuestros nuestras tu tus más muy desde hasta sobre entre cuando donde porque qué cómo cuál cada ya si no hemos han tiene tienen puede pueden todo toda todos todas'.split())
ENGLISH_STOP = set('the a an of and or but for by with without in is are this these that to from on as it its our your more very when where because what how each already have has can all not we they'.split())
IGNORE_PATH_PREFIXES = ('/wp-content/','/wp-includes/','/wp-json/','/xmlrpc.php','/wp-admin/','/feed/')

session = requests.Session()
session.headers.update({'User-Agent': UA, 'Accept-Language': 'en-GB,en;q=0.9'})


def norm(url):
    try:
        p = urlparse(url)
        if p.hostname not in ('www.elmercadodeorigen.com','elmercadodeorigen.com'):
            return None
        path = re.sub('/+', '/', p.path or '/')
        if not path.endswith('/') and '.' not in path.rsplit('/',1)[-1]:
            path += '/'
        return urlunparse(('https','www.elmercadodeorigen.com',path,'','',''))
    except Exception:
        return None


def add_sitemaps(queue, queued):
    sitemap_urls = [ROOT + '/mdo-sitemap-pages.xml', ROOT + '/mdo-sitemap-categories.xml', ROOT + '/mdo-sitemap-products.xml']
    found = []
    for sm in sitemap_urls:
        try:
            r = session.get(sm, timeout=TIMEOUT, allow_redirects=True)
            for loc in re.findall(r'<loc>\s*([^<]+)\s*</loc>', r.text, re.I):
                u = norm(loc.strip())
                if u and urlparse(u).path.startswith('/en/'):
                    found.append(u)
        except Exception as e:
            found.append('ERROR:' + sm + ':' + str(e))
    for u in found:
        if u.startswith('ERROR:'): continue
        if u not in queued:
            queued.add(u); queue.append(u)
    return found


def spanish_block(text):
    clean = re.sub(r'\s+', ' ', text).strip()
    if not clean: return False, ''
    if HIGH_RE.search(clean): return True, clean[:320]
    tokens = re.findall(r"[A-Za-zÁÉÍÓÚÜÑáéíóúüñ']+", clean.lower())
    if len(tokens) < 8: return False, ''
    es = sum(t in SPANISH_STOP for t in tokens)
    en = sum(t in ENGLISH_STOP for t in tokens)
    # Strong language signal only; avoids flagging names like Córdoba or Picual.
    if es >= 4 and es >= en + 3:
        return True, clean[:320]
    return False, ''

queue = deque()
queued = set()
for u in SEEDS:
    n = norm(u)
    if n and n not in queued: queued.add(n); queue.append(n)
sitemap_found = add_sitemaps(queue, queued)

results = []
seen = set()
link_leaks = []
spanish_slugs = []
errors = []
canonical_issues = []
lang_issues = []
spanish_text = []

while queue and len(seen) < MAX_URLS:
    url = queue.popleft()
    if url in seen: continue
    seen.add(url)
    try:
        r = session.get(url, timeout=TIMEOUT, allow_redirects=True)
    except Exception as e:
        errors.append({'url':url,'error':str(e)})
        continue
    final = norm(r.url) or r.url
    status = r.status_code
    ctype = r.headers.get('content-type','')
    item = {'url':url,'final':final,'status':status,'bytes':len(r.content),'title':''}
    results.append(item)
    if status != 200 or 'text/html' not in ctype:
        if status != 200: errors.append({'url':url,'status':status,'final':final})
        continue
    soup = BeautifulSoup(r.text, 'html.parser')
    if soup.title: item['title'] = soup.title.get_text(' ', strip=True)[:180]
    html = soup.find('html')
    lang = (html.get('lang','') if html else '').lower()
    if not lang.startswith('en'):
        lang_issues.append({'url':url,'lang':lang})
    canonical = soup.find('link', rel=lambda v: v and 'canonical' in (v if isinstance(v,list) else [v]))
    canon = norm(canonical.get('href','')) if canonical else None
    if canon is None or not urlparse(canon).path.startswith('/en/'):
        canonical_issues.append({'url':url,'canonical':canonical.get('href','') if canonical else ''})

    for tag in soup(['script','style','noscript','template','svg']): tag.decompose()
    blocks = []
    for tag in soup.find_all(['h1','h2','h3','h4','h5','h6','p','li','button','label','option','th','td','a']):
        txt = tag.get_text(' ', strip=True)
        bad, snippet = spanish_block(txt)
        if bad and snippet not in blocks:
            blocks.append(snippet)
            if len(blocks) >= 12: break
    if blocks:
        spanish_text.append({'url':url,'snippets':blocks})

    for a in soup.find_all('a', href=True):
        href = a.get('href','').strip()
        if href.startswith(('#','mailto:','tel:','javascript:')): continue
        absu = urljoin(r.url, href)
        p = urlparse(absu)
        if p.hostname not in ('www.elmercadodeorigen.com','elmercadodeorigen.com'): continue
        path = re.sub('/+','/',p.path or '/')
        if path.startswith(IGNORE_PATH_PREFIXES): continue
        anchor = a.get_text(' ', strip=True)[:100]
        hreflang = (a.get('hreflang') or '').lower()
        # Explicit Spanish language selector is allowed.
        is_language_switch = hreflang.startswith('es') or anchor.lower() in ('es','español','spanish')
        segs = [s for s in path.strip('/').split('/') if s]
        if segs and segs[0].lower() == 'en':
            if any(s.lower() in SPANISH_BASES for s in segs[1:]):
                spanish_slugs.append({'from':url,'href':absu,'anchor':anchor})
            n = norm(absu)
            if n and n not in queued and n not in seen:
                queued.add(n); queue.append(n)
        elif path == '/en' or path == '/en/':
            pass
        elif not is_language_switch:
            # Same-host navigable link escaping the English island.
            if path not in ('/','') and not path.endswith(('.jpg','.jpeg','.png','.webp','.gif','.pdf','.xml','.css','.js','.ico','.woff','.woff2')):
                link_leaks.append({'from':url,'href':absu,'anchor':anchor})

# Deduplicate structured issues.
def uniq(rows, keys):
    out=[]; seenk=set()
    for row in rows:
        k=tuple(row.get(x,'') for x in keys)
        if k in seenk: continue
        seenk.add(k); out.append(row)
    return out

link_leaks = uniq(link_leaks, ('from','href','anchor'))
spanish_slugs = uniq(spanish_slugs, ('from','href'))
summary = {
    'audited_urls': len(results),
    'discovered_urls': len(seen),
    'sitemap_english_urls': len([u for u in sitemap_found if not u.startswith('ERROR:')]),
    'http_errors': len(errors),
    'lang_issues': len(lang_issues),
    'canonical_issues': len(canonical_issues),
    'spanish_slug_links': len(spanish_slugs),
    'links_escaping_english': len(link_leaks),
    'pages_with_spanish_text': len(spanish_text),
}
report = {
    'summary': summary,
    'errors': errors[:100],
    'lang_issues': lang_issues[:100],
    'canonical_issues': canonical_issues[:100],
    'spanish_slug_links': spanish_slugs[:200],
    'links_escaping_english': link_leaks[:200],
    'spanish_text': spanish_text[:200],
    'urls': results,
}
print(json.dumps(report, ensure_ascii=False, indent=2))
with open('/tmp/english-public-audit-v63.json','w',encoding='utf-8') as f:
    json.dump(report,f,ensure_ascii=False,indent=2)
# Audit is diagnostic: never fail before we can inspect the complete report.
sys.exit(0)
