#!/usr/bin/env python3
"""Non-mutating public QA for translated archive 404s, 2 catalog products and XML sitemap."""
import html, json, re, urllib.request, urllib.error, xml.etree.ElementTree as ET
from html.parser import HTMLParser
ROOT="https://www.elmercadodeorigen.com"
PATHS=[
"/en/category/hams-and-shoulders/",
"/category/jamones-y-paletas/",
"/category/jamones-paletas/",
"/en/product-category/oranges/",
"/categoria-producto/naranjas/",
"/en/product/buy-6-pay-for-5-100-g-packs-of-50-iberian-acorn-fed-ham/",
"/producto/llevate-6-y-paga-5-sobres-de-100-grs-de-jamon-de-bellota-iberico-50/",
"/en/product/acorn-fed-shoulder-ham-pack/",
"/producto/pack-paleta-bellota/",
"/en/shop/",
"/tienda/",
"/english-sitemap.xml/",
"/english-sitemap.xml",
]
class P(HTMLParser):
 def __init__(self):super().__init__();self.metas=[];self.links=[];self.h1=0
 def handle_starttag(self,t,a):
  d=dict(a)
  if t=="meta":self.metas.append(d)
  if t=="link":self.links.append(d)
  if t=="h1":self.h1+=1
 def desc(self,name):
  return next((d.get("content","") for d in self.metas if d.get("name","").lower()==name), "")
 def alts(self):
  return {d.get("hreflang","").lower():d.get("href","") for d in self.links if d.get("hreflang")}
def get(path):
 url=ROOT+path;req=urllib.request.Request(url+"?emdo_route_probe=20261021",headers={"User-Agent":"Mozilla/5.0 (EMDO public SEO regression QA)"})
 try:
  r=urllib.request.urlopen(req,timeout=45);code=r.status
 except urllib.error.HTTPError as e:
  r=e;code=e.code
 except Exception as e:
  return {"path":path,"network_error":repr(e)}
 with r:
  body=r.read(3000000).decode("utf-8","replace")
  dest=r.geturl()
 x={"path":path,"http":code,"final":dest,"bytes":len(body)}
 if "sitemap.xml" in path:
  try:
   root=ET.fromstring(body)
   x["root_xml"]=root.tag;x["loc_count"]=sum(1 for q in root.iter() if q.tag.endswith("loc"))
  except Exception as e:
   x["xml_parse_error"]=str(e)[:150];x["preview"]=body[:230]
  return x
 d=P()
 try:d.feed(body)
 except Exception as e:x["html_error"]=str(e)
 x.update({"canonical":[y.get("href") for y in d.links if "canonical" in y.get("rel","").split()][:2],
  "alternates":d.alts(),"robots":d.desc("robots"),"h1":d.h1,
  "contains_noindex":"noindex" in d.desc("robots").lower()})
 return x
def main():
 from concurrent.futures import ThreadPoolExecutor
 with ThreadPoolExecutor(max_workers=3) as pool:res=list(pool.map(get,PATHS))
 for x in res:print("GLOBAL_ROUTE_QA",json.dumps(x,ensure_ascii=False),flush=True)
 m={x["path"]:x for x in res}
 cat=m["/en/category/hams-and-shoulders/"]
 valid_archive=cat.get("http")==200 and cat.get("alternates",{}).get("es-es")==ROOT+"/category/jamones-y-paletas/"
 print("BLOG_TRANSLATED_ARCHIVE_PASS",valid_archive)
 for en,es in [("/en/product/buy-6-pay-for-5-100-g-packs-of-50-iberian-acorn-fed-ham/","/producto/llevate-6-y-paga-5-sobres-de-100-grs-de-jamon-de-bellota-iberico-50/"),("/en/product/acorn-fed-shoulder-ham-pack/","/producto/pack-paleta-bellota/")]:
  print("PRODUCT_ROUTE_PARITY",json.dumps({"english":en,"es_http":m[es].get("http"),"en_http":m[en].get("http"),"english_only_404":m[es].get("http")==200 and m[en].get("http")==404},ensure_ascii=False))
 c=m["/en/product-category/oranges/"];s=m["/categoria-producto/naranjas/"]
 print("EMPTY_ORANGES_NOINDEX_PARITY",bool(c.get("contains_noindex")),bool(s.get("contains_noindex")))
 sm=m["/english-sitemap.xml/"];print("PUBLIC_ENGLISH_SITEMAP_STATUS",sm.get("http"),sm.get("loc_count",0),sm.get("root_xml",""))
 if not valid_archive:raise SystemExit("Blog category English URL remains unresolved")
 if any(m[en].get("http")==404 and m[es].get("http")==200 for en,es in [("/en/product/buy-6-pay-for-5-100-g-packs-of-50-iberian-acorn-fed-ham/","/producto/llevate-6-y-paga-5-sobres-de-100-grs-de-jamon-de-bellota-iberico-50/"),("/en/product/acorn-fed-shoulder-ham-pack/","/producto/pack-paleta-bellota/")]):
  raise SystemExit("English-only product 404 requires repair")
 print("GLOBAL_ROUTE_TRANSLATION_QA=PASS")
if __name__=="__main__":main()
