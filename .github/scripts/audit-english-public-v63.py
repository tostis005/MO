#!/usr/bin/env python3
import json, re, sys
from concurrent.futures import ThreadPoolExecutor, as_completed
from urllib.parse import urljoin, urlparse, urlunparse
import requests
from bs4 import BeautifulSoup

ROOT='https://www.elmercadodeorigen.com'
SEEDS=[ROOT+'/en/',ROOT+'/en/shop/',ROOT+'/en/categories/',ROOT+'/en/producers/',ROOT+'/en/journal/',ROOT+'/en/contact/',ROOT+'/en/cart/',ROOT+'/en/checkout/',ROOT+'/en/my-account/']
MAX_URLS=500; TIMEOUT=25; WORKERS=14
UA='Mozilla/5.0 AppleWebKit/537.36 Chrome/126 Safari/537.36 MDO-English-QA/63'
SPANISH_BASES={'tienda','producto','categoria-producto','etiqueta-producto','productores','categorias','contacto','carrito','finalizar-compra','mi-cuenta','quienes-somos','politica','politica-de-cookies','condiciones-especiales','acercade','variedad'}
HIGH_RE=re.compile('|'.join('(?:%s)'%p for p in [r'\bMostrando\b',r'\bresultados?\b',r'\bVendido por\b',r'\bValorado con\b',r'\bSeleccionar opciones\b',r'\bSin existencias\b',r'\bAñadir(?: al carrito)?\b',r'\b¡?Oferta!?\b',r'\bNovedad\b',r'\bÚltimas unidades\b',r'\bFiltros?\b',r'\bBuscar productos\b',r'\bOrdenar por\b',r'\bSiguiente\b',r'\bAnterior\b',r'\bCarrito\b',r'\bFinalizar compra\b',r'\bMi cuenta\b',r'\bPedido mínimo\b',r'\bImporte mínimo\b',r'\bDetalles de facturación\b',r'\bRealizar pedido\b',r'\bQuiénes somos\b',r'\bTérminos y condiciones\b',r'\bPolítica de cookies\b',r'\bTu nombre\b',r'\bTu correo electrónico\b',r'\bTu mensaje\b',r'\bAsunto\b',r'\bVisitar tienda\b',r'\bAcerca de\b',r'\bPolíticas\b',r'\bCategorías del producto\b',r'\bEtiquetas del producto\b',r'\bEscribe para buscar\b',r'\bConsulta\b']),re.I)
ES=set('el la los las un una unos unas de del al y o pero para por con sin en es son está estan esta este estos estas que como se su sus nuestro nuestra nuestros nuestras tu tus más muy desde hasta sobre entre cuando donde porque qué cómo cuál cada ya si no hemos han tiene tienen puede pueden todo toda todos todas'.split())
EN=set('the a an of and or but for by with without in is are this these that to from on as it its our your more very when where because what how each already have has can all not we they'.split())
IGNORE=('/wp-content/','/wp-includes/','/wp-json/','/xmlrpc.php','/wp-admin/','/feed/')

def norm(u):
    try:
        p=urlparse(u)
        if p.hostname not in ('www.elmercadodeorigen.com','elmercadodeorigen.com'): return None
        path=re.sub('/+','/',p.path or '/')
        if not path.endswith('/') and '.' not in path.rsplit('/',1)[-1]: path+='/'
        return urlunparse(('https','www.elmercadodeorigen.com',path,'','',''))
    except: return None

def spanish_block(text):
    t=re.sub(r'\s+',' ',text).strip()
    if not t:return False,''
    if HIGH_RE.search(t):return True,t[:360]
    toks=re.findall(r"[A-Za-zÁÉÍÓÚÜÑáéíóúüñ']+",t.lower())
    if len(toks)<8:return False,''
    es=sum(x in ES for x in toks); en=sum(x in EN for x in toks)
    return (es>=4 and es>=en+3),t[:360]

def fetch(url):
    s=requests.Session(); s.headers.update({'User-Agent':UA,'Accept-Language':'en-GB,en;q=0.9'})
    try:
        r=s.get(url,timeout=TIMEOUT,allow_redirects=True)
        return url,r,None
    except Exception as e:return url,None,str(e)

def sitemap_urls():
    out=[]
    for path in ('/mdo-sitemap-pages.xml','/mdo-sitemap-categories.xml','/mdo-sitemap-products.xml'):
        try:
            r=requests.get(ROOT+path,headers={'User-Agent':UA},timeout=TIMEOUT)
            for loc in re.findall(r'<loc>\s*([^<]+)\s*</loc>',r.text,re.I):
                u=norm(loc.strip())
                if u and urlparse(u).path.startswith('/en/'):out.append(u)
        except:pass
    return sorted(set(out))

queued=set(filter(None,[norm(u) for u in SEEDS]+sitemap_urls())); pending=list(queued); seen=set()
results=[]; errors=[]; lang_issues=[]; canonical_issues=[]; slug_links=[]; leaks=[]; spanish=[]
while pending and len(seen)<MAX_URLS:
    batch=[]
    while pending and len(seen)+len(batch)<MAX_URLS and len(batch)<WORKERS*3:
        u=pending.pop(0)
        if u not in seen:seen.add(u);batch.append(u)
    with ThreadPoolExecutor(max_workers=WORKERS) as ex:
        futs=[ex.submit(fetch,u) for u in batch]
        for f in as_completed(futs):
            url,r,err=f.result()
            if err:errors.append({'url':url,'error':err});continue
            final=norm(r.url) or r.url; status=r.status_code; ctype=r.headers.get('content-type','')
            item={'url':url,'final':final,'status':status,'bytes':len(r.content),'title':''};results.append(item)
            if status!=200:errors.append({'url':url,'status':status,'final':final})
            if status!=200 or 'text/html' not in ctype:continue
            soup=BeautifulSoup(r.text,'html.parser')
            if soup.title:item['title']=soup.title.get_text(' ',strip=True)[:180]
            html=soup.find('html'); lang=(html.get('lang','') if html else '').lower()
            if not lang.startswith('en'):lang_issues.append({'url':url,'lang':lang})
            can=soup.find('link',rel=lambda v:v and 'canonical' in (v if isinstance(v,list) else [v]))
            canon=norm(can.get('href','')) if can else None
            if canon is None or not urlparse(canon).path.startswith('/en/'):canonical_issues.append({'url':url,'canonical':can.get('href','') if can else ''})
            for tag in soup(['script','style','noscript','template','svg']):tag.decompose()
            blocks=[]
            for tag in soup.find_all(['h1','h2','h3','h4','h5','h6','p','li','button','label','option','th','td']):
                bad,snip=spanish_block(tag.get_text(' ',strip=True))
                if bad and snip not in blocks:blocks.append(snip)
                if len(blocks)>=12:break
            if blocks:spanish.append({'url':url,'snippets':blocks})
            for a in soup.find_all('a',href=True):
                href=a.get('href','').strip()
                if href.startswith(('#','mailto:','tel:','javascript:')):continue
                absu=urljoin(r.url,href); p=urlparse(absu)
                if p.hostname not in ('www.elmercadodeorigen.com','elmercadodeorigen.com'):continue
                path=re.sub('/+','/',p.path or '/')
                if path.startswith(IGNORE):continue
                anchor=a.get_text(' ',strip=True)[:100]; hreflang=(a.get('hreflang') or '').lower(); seg=[x for x in path.strip('/').split('/') if x]
                switch=hreflang.startswith('es') or anchor.lower() in ('es','español','spanish')
                if seg and seg[0].lower()=='en':
                    if any(x.lower() in SPANISH_BASES for x in seg[1:]):slug_links.append({'from':url,'href':absu,'anchor':anchor})
                    n=norm(absu)
                    if n and n not in queued and len(queued)<MAX_URLS:queued.add(n);pending.append(n)
                elif path not in ('/','') and not switch and not path.endswith(('.jpg','.jpeg','.png','.webp','.gif','.pdf','.xml','.css','.js','.ico','.woff','.woff2')):
                    leaks.append({'from':url,'href':absu,'anchor':anchor})

def uniq(rows,keys):
    out=[];s=set()
    for x in rows:
        k=tuple(x.get(q,'') for q in keys)
        if k not in s:s.add(k);out.append(x)
    return out
slug_links=uniq(slug_links,('from','href')); leaks=uniq(leaks,('from','href','anchor'))
summary={'audited_urls':len(results),'queued_urls':len(queued),'sitemap_english_urls':len(sitemap_urls()),'http_errors':len(errors),'lang_issues':len(lang_issues),'canonical_issues':len(canonical_issues),'spanish_slug_links':len(slug_links),'links_escaping_english':len(leaks),'pages_with_spanish_text':len(spanish)}
report={'summary':summary,'errors':errors,'lang_issues':lang_issues,'canonical_issues':canonical_issues,'spanish_slug_links':slug_links,'links_escaping_english':leaks,'spanish_text':spanish,'urls':sorted(results,key=lambda x:x['url'])}
print(json.dumps(report,ensure_ascii=False,indent=2));json.dump(report,open('/tmp/english-public-audit-v63.json','w',encoding='utf-8'),ensure_ascii=False,indent=2)
