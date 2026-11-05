#!/usr/bin/env python3
"""Read-only check: robots and XML sitemap entries; H1 DOM on producer 1957."""
import json
from urllib.request import Request, urlopen
from urllib.parse import urlsplit
from xml.etree import ElementTree as ET
from bs4 import BeautifulSoup

BASE="https://www.elmercadodeorigen.com"
NS={"s":"http://www.sitemaps.org/schemas/sitemap/0.9"}
def fetch(path):
    req=Request(BASE+path,headers={"User-Agent":"Mozilla/5.0 EMDO SEO index QA","Cache-Control":"no-cache"})
    with urlopen(req,timeout=65) as response:
        assert response.status==200,(path,response.status)
        return response.read()
robots=fetch("/robots.txt").decode("utf-8","replace")
assert "Sitemap: https://www.elmercadodeorigen.com/sitemap_index.xml" in robots
index=ET.fromstring(fetch("/sitemap_index.xml"))
children=[x.text.strip() for x in index.findall(".//s:sitemap/s:loc",NS) if x.text]
required=["mdo-sitemap-pages.xml","mdo-sitemap-posts.xml","mdo-sitemap-categories.xml","mdo-sitemap-products.xml"]
for name in required:
    assert any(name in x for x in children),("missing sitemap child",name)
report={"index_children":children,"robots_ok":True,"pages":{}}
for name in ("mdo-sitemap-products.xml","mdo-sitemap-categories.xml"):
    root=ET.fromstring(fetch("/"+name))
    urls={x.text.strip() for x in root.findall(".//s:url/s:loc",NS) if x.text}
    report["pages"][name]={"count":len(urls)}
    if "products" in name:
        paths=[
         "/producto/cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio/",
         "/producto/sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l/",
         "/producto/aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27/",
         "/producto/aceite-de-oliva-virgen-extra-5l/",
         "/producto/jamon-de-bellota-100-iberico/"
        ]
    else:
        paths=["/categoria-producto/aceites/","/categoria-producto/jamones-paletas/","/categoria-producto/setas-y-hongos/"]
    missing=[p for p in paths if BASE+p not in urls]
    report["pages"][name]["missing_priority_paths"]=missing
    assert not missing,(name,missing)
soup=BeautifulSoup(fetch("/tienda/1957/"),"html.parser")
h1s=[]
for h in soup.find_all("h1"):
    path=[]
    el=h
    for _ in range(4):
        if not el:break
        path.append({"tag":el.name,"id":el.get("id",""),"classes":el.get("class",[]),"style":el.get("style","")[:120]})
        el=el.parent
    h1s.append({"text":h.get_text(" ",strip=True)[:120],"dom":path})
report["producer_1957_h1"]=h1s
print("SEO_INDEXABILITY_AUDIT="+json.dumps(report,ensure_ascii=False,separators=(",",":")),flush=True)
