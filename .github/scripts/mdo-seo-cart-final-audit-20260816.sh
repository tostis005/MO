#!/usr/bin/env bash
set -u
stamp=$(date +%s%N)
base='https://www.elmercadodeorigen.com'
paths=(
  '/en/shop/'
  '/en/product/100-iberian-acorn-fed-ham-black-tag/'
  '/en/product-category/oils/'
  '/en/extra-virgin-olive-oil/'
  '/en/privacy-policy/'
  '/en/store/hidalgo-de-la-jara/'
)
for path in "${paths[@]}"; do
  echo "=== $path ==="
  curl -ksSL --max-time 45 "$base$path?mdo_seo=$stamp" -o /tmp/mdo-head.html || true
  python3 - "$path" <<'PY'
import sys,re,html
path=sys.argv[1]
s=open('/tmp/mdo-head.html',encoding='utf-8',errors='ignore').read()
head=s.split('</head>',1)[0]
canon=[]
for tag in re.findall(r'<link\b[^>]*>',head,re.I):
    if re.search(r'\brel=["\']canonical["\']',tag,re.I):
        m=re.search(r'\bhref=["\']([^"\']+)',tag,re.I)
        if m: canon.append(m.group(1))
alt=[]
for tag in re.findall(r'<link\b[^>]*>',head,re.I):
    m1=re.search(r'\bhreflang=["\']([^"\']+)',tag,re.I)
    m2=re.search(r'\bhref=["\']([^"\']+)',tag,re.I)
    if m1 and m2: alt.append((m1.group(1),m2.group(1)))
desc=[]
for tag in re.findall(r'<meta\b[^>]*>',head,re.I):
    if re.search(r'\bname=["\']description["\']',tag,re.I):
        m=re.search(r'\bcontent=["\']([^"\']*)',tag,re.I)
        if m: desc.append(html.unescape(m.group(1)))
print('CANONICAL',canon[:2])
print('HREFLANG',alt[:8])
print('DESCRIPTION',desc[:1])
spanish_rx=re.compile(r'\b(El aceite de oliva|jamón procedente|paleta procedente|política de privacidad|envíos en|información nutricional|introducción)\b',re.I)
for m in re.finditer(r'<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>',head,re.I|re.S):
    txt=html.unescape(m.group(1))
    if spanish_rx.search(txt): print('SPANISH_JSONLD',re.sub(r'\s+',' ',txt)[:800])
PY
done

echo '=== LIVE CART ==='
rm -f /tmp/mdo-cookies.txt
curl -ksSL --max-time 45 -c /tmp/mdo-cookies.txt -b /tmp/mdo-cookies.txt "$base/en/?add-to-cart=1350&quantity=1&mdo_cart=$stamp" -o /tmp/mdo-add.html || true
curl -ksSL --max-time 45 -c /tmp/mdo-cookies.txt -b /tmp/mdo-cookies.txt "$base/en/cart/?mdo_cart=$stamp" -o /tmp/mdo-cart.html -w 'CART_HTTP=%{http_code}\n' || true
python3 <<'PY'
import re,html
from html.parser import HTMLParser
s=open('/tmp/mdo-cart.html',encoding='utf-8',errors='ignore').read()
class V(HTMLParser):
    def __init__(self): super().__init__(convert_charrefs=True); self.skip=0; self.parts=[]
    def handle_starttag(self,t,a):
        if t in {'head','script','style','noscript','svg','template'}: self.skip+=1
    def handle_endtag(self,t):
        if t in {'head','script','style','noscript','svg','template'} and self.skip: self.skip-=1
    def handle_data(self,d):
        if not self.skip:
            x=' '.join(d.split())
            if x:self.parts.append(x)
p=V(); p.feed(s)
print('BODY_CLASS',re.findall(r'<body[^>]+class=["\']([^"\']+)',s,re.I)[:1])
for x in p.parts:
    if re.search(r'\b(minimum|supplier|seller|vendor|producer|order|cart|shipping|checkout|mínimo|pedido|carrito|envío|vendedor|productor)\b',x,re.I):
        print('CART_TEXT',x[:600])
for href in sorted(set(html.unescape(x) for x in re.findall(r'<a\b[^>]*href=["\']([^"\']+)',s,re.I|re.S))):
    if '/tienda/' in href or '/producto/' in href or '/politica-de-cookies/' in href or '/cart/' in href or '/checkout/' in href or '/shop/' in href or '/product/' in href:
        print('CART_LINK',href)
PY
