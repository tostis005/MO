#!/usr/bin/env python3
"""Read-only audit of the actual public Google Merchant ES RSS feed."""
import json
import os
import re
from collections import defaultdict
from urllib.parse import urlsplit, parse_qs
from urllib.request import Request, urlopen
from xml.etree import ElementTree as ET

URL="https://www.elmercadodeorigen.com/emdo-feed/es.xml?merchant_audit="+os.environ.get("GITHUB_RUN_ID","1")
request=Request(URL,headers={"Cache-Control":"no-cache","User-Agent":"Mozilla/5.0 EMDO-Merchant-Audit"})
with urlopen(request,timeout=105) as response:
    status=response.status
    body=response.read()
    content_type=response.headers.get("Content-Type","")
assert status==200,("feed http status",status)
assert b"<rss" in body[:1000],("feed invalid root",content_type,body[:100])
root=ET.fromstring(body)
ns={"g":"http://base.google.com/ns/1.0"}
items=root.findall("./channel/item")
assert len(items)>0,"empty Google Merchant ES catalog"
grouped=defaultdict(list)
warnings=[]
for item in items:
    def g(key):
        return (item.findtext("g:"+key,default="",namespaces=ns) or "").strip()
    link=g("link")
    path=urlsplit(link).path
    title=g("title")
    offer={
        "id":g("id"),
        "title":title[:180],
        "link":link,
        "availability":g("availability"),
        "price":g("price"),
        "sale_price":g("sale_price"),
        "group":g("item_group_id"),
        "image":bool(g("image_link")),
        "brand":g("brand"),
        "shipping_countries":[(s.findtext("g:country",default="",namespaces=ns) or "") for s in item.findall("g:shipping",ns)]
    }
    grouped[path].append(offer)
    if not re.match(r"^\d+(?:\.\d+)? EUR$",offer["price"]):
        warnings.append({"id":offer["id"],"reason":"price-not-EUR-or-invalid","price":offer["price"]})
    if offer["availability"] not in ("in_stock","out_of_stock","preorder"):
        warnings.append({"id":offer["id"],"reason":"invalid-availability","value":offer["availability"]})
    if not offer["image"]:
        warnings.append({"id":offer["id"],"reason":"missing-image"})
    if "ES" not in offer["shipping_countries"]:
        warnings.append({"id":offer["id"],"reason":"missing-Spain-shipping"})
paths=[
 "/producto/cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio/",
 "/producto/sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l/",
 "/producto/aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27/",
 "/producto/aceite-de-oliva-virgen-extra-5l/",
 "/producto/aceite-de-oliva-virgen-extra-1l/",
 "/producto/aceite-de-oliva-virgen-extra-500ml-2/",
]
report={
  "feed_status":status,
  "items_total":len(items),
  "distinct_urls":len(grouped),
  "xml_bytes":len(body),
  "offers_by_1957_product":{path:grouped.get(path,[]) for path in paths},
  "warnings_total":len(warnings),
  "warnings_sample":warnings[:15],
}
print("EMDO_MERCHANT_ES_AUDIT="+json.dumps(report,ensure_ascii=False,separators=(",",":")),flush=True)
fail=[]
for path in paths[:3]:
    active=[o for o in grouped.get(path,[]) if o["availability"]=="in_stock"]
    if not active:
        fail.append({"path":path,"reason":"new-harvest-missing-or-no-active-offer"})
print("EMDO_MERCHANT_ES_RESULT="+json.dumps({"missing_new_harvest":fail,"all_offers":len(items),"warnings":len(warnings)},ensure_ascii=False),flush=True)
if fail or warnings:
    raise SystemExit(3)
