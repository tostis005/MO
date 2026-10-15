#!/usr/bin/env python3
"""Read-only heading structure diagnostic. No WordPress modifications."""
import json
from urllib.request import Request,urlopen
from bs4 import BeautifulSoup
from urllib.parse import urlsplit
routes=[
 "/comprar-directamente-al-productor-como-funciona/",
 "/comprar-carne-online-que-mirar-antes-elegir-corte/",
 "/que-significa-aceite-oliva-virgen-extra/",
 "/en/how-to-identify-local-food-and-where-to-buy-it/",
 "/categoria-producto/hortalizas-verduras/",
]
out=[]
for path in routes:
  with urlopen(Request("https://www.elmercadodeorigen.com"+path+"?mdo_h1_diag=20261015",headers={"User-Agent":"MDO-SEO-H1-ReadOnly"}),timeout=75) as r:
    html=r.read().decode("utf8","replace")
  soup=BeautifulSoup(html,"html.parser")
  hits=[]
  for h in soup.find_all("h1"):
    ancestry=[]
    el=h
    for _ in range(5):
      if not el or not getattr(el,"name",None): break
      ancestry.append({"tag":el.name,"id":el.get("id",""),"classes":el.get("class",[])[:5]})
      el=el.parent
    hits.append({"text":h.get_text(" ",strip=True)[:150],"ancestors":ancestry})
  header=soup.select_one("div.page-header")
  header_markup=str(header)[:1350] if header else ""
  print("PARENT_PAGE_HEADER "+json.dumps({"path":path,"html_prefix":header_markup},ensure_ascii=False),flush=True)
  item={"path":path,"h1":hits}
  out.append(item)
  print("H1_DIAGNOSTIC "+json.dumps(item,ensure_ascii=False),flush=True)
with open("h1-diagnostic-20261015.json","w",encoding="utf8") as f:json.dump(out,f,ensure_ascii=False,indent=2)
