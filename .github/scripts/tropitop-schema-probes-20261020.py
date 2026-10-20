"""Read-only diversified product-page diagnostics for safe import schema validation."""
import collections, json, re, time
from urllib.parse import urljoin,urlparse
import requests
from bs4 import BeautifulSoup
s=requests.Session()
s.headers.update({"User-Agent":"EMDO QA (+https://www.elmercadodeorigen.com/)","Accept-Language":"es-ES,es;q=0.9"})
catalog={}
home=s.get("https://tropitop.com/es/",timeout=20)
cats={}
for a in BeautifulSoup(home.content,"html.parser").select('a[href]'):
    u=urljoin(home.url,a.get("href",""))
    m=re.search(r'^/es/(\\d+-[a-z0-9-]+)/?for p in range(1,9):
    rr=s.get(f"https://tropitop.com/es/84-disponible?page={p}",timeout=20)
    if rr.status_code!=200: break
    soup=BeautifulSoup(rr.text,"html.parser")
    cards=soup.select("article.product-miniature, .product-miniature.js-product-miniature")
    if not cards: break
    for card in cards:
        for a in card.select('a[href]'):
            u=urljoin(rr.url,a["href"]).split('#')[0].split('?')[0]
            m=re.search(r'^/es/([^/]+)/(\d+)(?:-\d+)?-[a-z0-9-]+\.html$',urlparse(u).path)
            if m:
                catalog[m[2]]={"url":u,"category":m[1]}
                break
    if len(cards)<24: break
by=collections.defaultdict(list)
for x in catalog.values():by[x['category']].append(x)
selection=[]
for category,items in sorted(by.items()):
    selection.extend(items[:2])
    if category in ('gourmet','packs','aguacates','frutas'):selection.extend(items[-1:])
print("PROBE_SELECTED",len(selection),"DISCOVERED",len(catalog))
for i,entry in enumerate(selection):
    try:
        u=entry["url"]
        r=s.get(u,timeout=25);soup=BeautifulSoup(r.content,"html.parser")
        h=soup.select_one('h1')
        form=soup.select_one('form#add-to-cart-or-refresh')
        variations=soup.select_one('.product-variants')
        desc=soup.select_one('#description .product-description, .product-description, #description, [itemprop="description"]')
        availability=soup.select_one('#product-availability, .product-availability')
        btn=soup.select_one('button[data-button-action="add-to-cart"]')
        groups=[]
        if variations:
            for g in variations.select('.product-variants-item'):
                name=g.select_one('span.control-label,label.control-label')
                elements=g.select('option,.input-radio,.input-color,li')
                groups.append({"name":name.get_text(' ',strip=True)[:70] if name else '',
                  "values":[x.get_text(' ',strip=True)[:115] for x in elements[:14]]})
        vtext=variations.get_text(' ',strip=True)[:450] if variations else ''
        hjson=soup.select_one('script[type="application/ld+json"]')
        scripts=len(soup.select('script[type="application/ld+json"]'))
        micro=soup.select('[itemprop="weight"],[itemprop="availability"],[itemprop="price"]')
        details={
          "category":entry['category'],"url":u,"status":r.status_code,"title":h.get_text(' ',strip=True)[:80] if h else '',
          "selector":vtext, "groups":groups,
          "button":(btn.get_text(' ',strip=True)[:60] if btn else None),"disabled":bool(btn and btn.has_attr('disabled')),
          "availability":availability.get_text(' ',strip=True)[:90] if availability else None,
          "description_len":len(desc.get_text(' ',strip=True)) if desc else 0,
          "image_count":len(soup.select('.product-cover img[data-image-large-src]')),
          "ldjson":scripts,"microattrs":[(t.get('itemprop'),t.get('content')) for t in micro[:4]],
          "data_product":list((form.attrs if form else {}).keys())[:15],
        }
        print("TROPITOP_PAGE_PROBE="+json.dumps(details,ensure_ascii=False,separators=(",",":")))
        time.sleep(.4)
    except Exception as exc: print("TROPITOP_PAGE_PROBE_ERROR="+json.dumps({"url":entry["url"],"error":str(exc)[:140]}))
,urlparse(u).path)
    if m: cats[m.group(1)]=cats.get(m.group(1),0)+1
print("TROPITOP_HOME_CATEGORY_LINKS="+json.dumps(cats,ensure_ascii=False,separators=(",",":")))
for p in range(1,9):
    rr=s.get(f"https://tropitop.com/es/84-disponible?page={p}",timeout=20)
    if rr.status_code!=200: break
    soup=BeautifulSoup(rr.text,"html.parser")
    cards=soup.select("article.product-miniature, .product-miniature.js-product-miniature")
    if not cards: break
    for card in cards:
        for a in card.select('a[href]'):
            u=urljoin(rr.url,a["href"]).split('#')[0].split('?')[0]
            m=re.search(r'^/es/([^/]+)/(\d+)(?:-\d+)?-[a-z0-9-]+\.html$',urlparse(u).path)
            if m:
                catalog[m[2]]={"url":u,"category":m[1]}
                break
    if len(cards)<24: break
by=collections.defaultdict(list)
for x in catalog.values():by[x['category']].append(x)
selection=[]
for category,items in sorted(by.items()):
    selection.extend(items[:2])
    if category in ('gourmet','packs','aguacates','frutas'):selection.extend(items[-1:])
print("PROBE_SELECTED",len(selection),"DISCOVERED",len(catalog))
for i,entry in enumerate(selection):
    try:
        u=entry["url"]
        r=s.get(u,timeout=25);soup=BeautifulSoup(r.content,"html.parser")
        h=soup.select_one('h1')
        form=soup.select_one('form#add-to-cart-or-refresh')
        variations=soup.select_one('.product-variants')
        desc=soup.select_one('#description .product-description, .product-description, #description, [itemprop="description"]')
        availability=soup.select_one('#product-availability, .product-availability')
        btn=soup.select_one('button[data-button-action="add-to-cart"]')
        groups=[]
        if variations:
            for g in variations.select('.product-variants-item'):
                name=g.select_one('span.control-label,label.control-label')
                elements=g.select('option,.input-radio,.input-color,li')
                groups.append({"name":name.get_text(' ',strip=True)[:70] if name else '',
                  "values":[x.get_text(' ',strip=True)[:115] for x in elements[:14]]})
        vtext=variations.get_text(' ',strip=True)[:450] if variations else ''
        hjson=soup.select_one('script[type="application/ld+json"]')
        scripts=len(soup.select('script[type="application/ld+json"]'))
        micro=soup.select('[itemprop="weight"],[itemprop="availability"],[itemprop="price"]')
        details={
          "category":entry['category'],"url":u,"status":r.status_code,"title":h.get_text(' ',strip=True)[:80] if h else '',
          "selector":vtext, "groups":groups,
          "button":(btn.get_text(' ',strip=True)[:60] if btn else None),"disabled":bool(btn and btn.has_attr('disabled')),
          "availability":availability.get_text(' ',strip=True)[:90] if availability else None,
          "description_len":len(desc.get_text(' ',strip=True)) if desc else 0,
          "image_count":len(soup.select('.product-cover img[data-image-large-src]')),
          "ldjson":scripts,"microattrs":[(t.get('itemprop'),t.get('content')) for t in micro[:4]],
          "data_product":list((form.attrs if form else {}).keys())[:15],
        }
        print("TROPITOP_PAGE_PROBE="+json.dumps(details,ensure_ascii=False,separators=(",",":")))
        time.sleep(.4)
    except Exception as exc: print("TROPITOP_PAGE_PROBE_ERROR="+json.dumps({"url":entry["url"],"error":str(exc)[:140]}))
