#!/usr/bin/env python3
"""Read-only public HTML attribution/GA4 signal audit without collecting user data."""
import json, re
from urllib.error import HTTPError,URLError
from urllib.request import Request, urlopen
from bs4 import BeautifulSoup
BASE='https://www.elmercadodeorigen.com'
SITES={
 'home':'/',
 'product_new_oil':'/producto/cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio/',
 'guide_oil':'/aove-filtrado-o-sin-filtrar-diferencias/',
 'english_padron':'/en/product/padron-peppers-kg/',
}
for label,path in SITES.items():
  url=BASE+path
  try:
    request=Request(url,headers={'User-Agent':'Mozilla/5.0 (EMDO QA)','Cache-Control':'no-cache'})
    with urlopen(request,timeout=60) as res:
      html=res.read().decode('utf-8','replace')
      code=res.status
      resolved=res.geturl()
    soup=BeautifulSoup(html,'html.parser')
    sources=[s.get('src','') for s in soup.find_all('script',src=True)]
    payload=' '.join((x.get_text() or '') for x in soup.find_all('script') if not x.get('src'))
    flags={
      'gtag_js':any('googletagmanager.com/gtag/js' in s for s in sources),
      'gtm_js':any('googletagmanager.com/gtm.js' in s for s in sources),
      'gla_gtag_events_js':any('google-listings-and-ads' in s and 'gtag' in s for s in sources),
      'ga4_measurement_id_present':bool(re.search(r'\bG-[A-Z0-9]{5,}\b',html)),
      'dataLayer_present':('dataLayer' in html),
      'gtag_event_view_item_inline':bool(re.search(r'gtag\s*\(\s*[\"\\\']event[\"\\\']\s*,\s*[\"\\\']view_item',payload)),
      'gtag_event_add_to_cart_inline':bool(re.search(r'gtag\s*\(\s*[\"\\\']event[\"\\\']\s*,\s*[\"\\\']add_to_cart',payload)),
      'gtag_event_purchase_inline':bool(re.search(r'gtag\s*\(\s*[\"\\\']event[\"\\\']\s*,\s*[\"\\\']purchase',payload)),
    }
    tags=soup.select('meta[name="description"]')
    canonical=soup.select_one('link[rel="canonical"]')
    print('EMDO_HTML_MEASUREMENT_AUDIT='+json.dumps({
      'name':label,'status':code,'resolved_path':resolved.split(BASE)[-1],
      'meta_description_count':len(tags),
      'canonical_path':canonical.get('href','').split(BASE)[-1] if canonical else '',
      'signals':flags},ensure_ascii=False),flush=True)
  except HTTPError as e:
    print('EMDO_HTML_MEASUREMENT_AUDIT='+json.dumps({'name':label,'status':e.code,'resolved_path':e.geturl().split(BASE)[-1]}),flush=True)
  except (URLError,TimeoutError) as e:
    print('EMDO_HTML_MEASUREMENT_AUDIT='+json.dumps({'name':label,'error':str(e)[:220]}),flush=True)
