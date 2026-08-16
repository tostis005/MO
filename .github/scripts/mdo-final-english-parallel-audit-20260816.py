import concurrent.futures, urllib.request, urllib.parse, urllib.error, ssl, re, html, http.cookiejar, time

BASE='https://www.elmercadodeorigen.com'
STAMP=str(time.time_ns())
PATHS=[
'/en/','/en/shop/','/en/producers/','/en/journal/','/en/contact/','/en/privacy-policy/','/en/cookie-policy/','/en/cart/','/en/my-account/',
'/en/extra-virgin-olive-oil/','/en/iberian-ham/','/en/oranges/','/en/product-category/oils/',
'/en/product/100-iberian-acorn-fed-ham-black-tag/','/en/product/extra-virgin-olive-oil-5l/','/en/product/100-iberian-acorn-fed-sobrasada/',
'/en/store/hidalgo-de-la-jara/','/en/store/1957/'
]
SSL=ssl._create_unverified_context()
BAD_TEXT=re.compile(r'\b(añadir al carrito|pedido mínimo|importe mínimo|gastos de envío|política de privacidad|política de cookies|envíos en|elaborado a partir|información nutricional media|para pedidos|aceite de oliva virgen extra es un zumo|jamón procedente|paleta procedente|recolección|nuestros jamones|nuestras variedades|devolución fácil|resolvemos tus dudas)\b',re.I)
BAD_LINK=re.compile(r'href=["\'][^"\']*/en/(?:tienda|producto|politica-de-cookies)/',re.I)

def fetch(path):
    url=BASE+path+('?' if '?' not in path else '&')+'mdo_parallel='+STAMP
    req=urllib.request.Request(url,headers={'User-Agent':'MDO-English-QA/1.0'})
    try:
        with urllib.request.urlopen(req,context=SSL,timeout=22) as r:
            return path,r.status,r.geturl(),r.read().decode('utf-8','ignore')
    except urllib.error.HTTPError as e:
        try: body=e.read().decode('utf-8','ignore')
        except Exception: body=''
        return path,e.code,e.geturl(),body
    except Exception as e:
        return path,0,str(e),''

def head_audit(path, body):
    head=body.split('</head>',1)[0]
    canonical=[]; hreflang=[]; descriptions=[]; jsonld_bad=[]
    for tag in re.findall(r'<link\b[^>]*>',head,re.I):
        if re.search(r'\brel=["\']canonical["\']',tag,re.I):
            m=re.search(r'\bhref=["\']([^"\']+)',tag,re.I)
            if m: canonical.append(html.unescape(m.group(1)))
        m1=re.search(r'\bhreflang=["\']([^"\']+)',tag,re.I); m2=re.search(r'\bhref=["\']([^"\']+)',tag,re.I)
        if m1 and m2: hreflang.append((m1.group(1),html.unescape(m2.group(1))))
    for tag in re.findall(r'<meta\b[^>]*>',head,re.I):
        if re.search(r'\bname=["\']description["\']',tag,re.I):
            m=re.search(r'\bcontent=["\']([^"\']*)',tag,re.I)
            if m: descriptions.append(html.unescape(m.group(1)))
    for m in re.finditer(r'<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>',head,re.I|re.S):
        txt=html.unescape(m.group(1))
        if BAD_TEXT.search(txt): jsonld_bad.append(re.sub(r'\s+',' ',txt)[:500])
    return canonical,hreflang,descriptions,jsonld_bad

failed=False
results=[]
with concurrent.futures.ThreadPoolExecutor(max_workers=9) as ex:
    futs=[ex.submit(fetch,p) for p in PATHS]
    for f in concurrent.futures.as_completed(futs): results.append(f.result())
for path,status,final,body in sorted(results):
    lang='translatepress-en_US' in body
    bad_text=sorted(set(m.group(0) for m in BAD_TEXT.finditer(body)))[:10]
    bad_links=BAD_LINK.findall(body)
    print(f'PAGE|{path}|HTTP={status}|LANG={lang}|FINAL={final}')
    if bad_text: print('BAD_TEXT|'+path+'|'+','.join(bad_text))
    if bad_links: print('BAD_LINK|'+path+'|count='+str(len(bad_links)))
    if path in ['/en/shop/','/en/product/100-iberian-acorn-fed-ham-black-tag/','/en/product-category/oils/','/en/extra-virgin-olive-oil/','/en/privacy-policy/','/en/store/hidalgo-de-la-jara/']:
        c,h,d,j=head_audit(path,body)
        print('SEO|'+path+'|CANON='+repr(c[:2])+'|HREF='+repr(h[:8])+'|DESC='+repr(d[:1]))
        if j: print('BAD_JSONLD|'+path+'|'+repr(j))
        # English canonical should exist and point to /en/ canonical path (except vendor plugin may omit).
        if c and '/en/' not in c[0] and path != '/en/': failed=True
    if status!=200 or not lang or bad_text or bad_links: failed=True

# Spanish shop safety.
sp,sc,sf,sb=fetch('/tienda/')
print(f'SPANISH|HTTP={sc}|ESLANG={"translatepress-es_ES" in sb}|SHOP={"woocommerce-shop" in sb}|COOKIE_ES={"/politica-de-cookies/" in sb}')
if sc!=200 or 'translatepress-es_ES' not in sb or 'woocommerce-shop' not in sb: failed=True

# Real English cart session.
cj=http.cookiejar.CookieJar(); opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj),urllib.request.HTTPSHandler(context=SSL))
def session_get(url):
    req=urllib.request.Request(url,headers={'User-Agent':'MDO-English-QA/1.0'})
    with opener.open(req,timeout=25) as r: return r.status,r.geturl(),r.read().decode('utf-8','ignore')
try:
    session_get(BASE+'/en/?add-to-cart=1350&quantity=1&mdo_cart='+STAMP)
    cs,cf,cb=session_get(BASE+'/en/cart/?mdo_cart='+STAMP)
    c_bad=sorted(set(m.group(0) for m in BAD_TEXT.finditer(cb)))
    c_links=BAD_LINK.findall(cb)
    print(f'CART|HTTP={cs}|LANG={"translatepress-en_US" in cb}|FINAL={cf}|BAD_TEXT={c_bad[:10]}|BAD_LINKS={len(c_links)}')
    texts=[]
    plain=re.sub(r'<script.*?</script>|<style.*?</style>',' ',cb,flags=re.I|re.S)
    plain=re.sub(r'<[^>]+>',' ',plain); plain=html.unescape(re.sub(r'\s+',' ',plain))
    for pat in ['minimum','Minimum','supplier','Supplier','producer','Producer','order','Order']:
        i=plain.find(pat)
        if i>=0: texts.append(plain[max(0,i-120):i+420])
    for x in texts[:4]: print('CART_CONTEXT|'+x)
    if cs!=200 or 'translatepress-en_US' not in cb or c_bad or c_links: failed=True
except Exception as e:
    print('CART_ERROR|'+repr(e)); failed=True

raise SystemExit(1 if failed else 0)
