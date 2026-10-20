import requests
from bs4 import BeautifulSoup
from urllib.parse import urlparse,urljoin
s=requests.Session();s.headers.update({'Accept-Language':'es-ES,es;q=0.9','User-Agent':'EMDO Integration QA'})
for url in ['https://tropitop.com/es/aguacates/33-comprar-aguacate-bacon.html','https://tropitop.com/es/']:
 try:
  r=s.get(url,timeout=15); print('PAGE',r.status_code,r.url,'SIZE',len(r.content))
  soup=BeautifulSoup(r.text,'html.parser')
  for sel in ['meta[property="og:image"]','meta[name="twitter:image"]','.product-cover img','.product-images img','.js-qv-product-cover','.thumb-container img','[data-image-large-src]','#header .logo img','img.logo','header img','link[rel="icon"]']:
   entries=soup.select(sel)
   if entries:
    print('SELECTOR',sel,'COUNT',len(entries))
    for n in entries[:9]:
     values={k:(n.get(k) or '')[:220] for k in ['alt','src','content','data-src','data-image-large-src','data-full-size-image-url','srcset','href','class'] if n.has_attr(k)}
     print('IMAGE',str(values)[:450])
  if '33-' in url:
   for elem in soup.select('.product-images img')[:10]:
    print('GALLERY',elem.get('src'),elem.get('data-image-large-src'),elem.get('data-full-size-image-url'))
   print('SHIPPING_ELEMENTS',[(x.get('id'),x.get('class'),x.get_text(' ',strip=True)[:230]) for x in soup.select('input[name*="postcode"],.shipping-estimate,.delivery-information,.product-delivery')[:12]])
 except Exception as e: print('FAIL',str(e)[:180])
