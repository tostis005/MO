#!/usr/bin/env python3
"""Public, read-only smoke QA of 18 manually translated Tropitop product routes."""
import html
import json
from html.parser import HTMLParser
import re
import time
import urllib.error
import urllib.request
from urllib.parse import urlparse

BASE="https://www.elmercadodeorigen.com/en/product/"
SLUGS=[
  "bacon-avocados-smooth-green-skin-tropitop",
  "organic-mangoes-castellon",
  "valencian-late-season-oranges",
  "fresh-seasonal-lemons-tropitop",
  "oranges-and-lemons-mixed-citrus-box",
  "organic-pitahayas-dragon-fruit",
  "seasonal-valencian-mandarins-clementines",
  "seasonal-fresh-grapefruits",
  "grapefruits-and-oranges-mixed-citrus",
  "mandarins-grapefruits-mixed-selection",
  "mandarin-and-orange-citrus-mix",
  "bacon-avocados-and-grapefruits-box",
  "smooth-skinned-avocado-and-pitahaya-pack",
  "mandarins-and-pitahaya-fresh-fruit-pack",
  "fresh-pomegranates-tropitop",
  "pomegranates-smooth-skinned-avocados-mix",
  "pomegranates-pitahayas-mixed-fruit",
  "pomegranates-mandarins-mixed-fruit-box"
]
results=[]
for slug in SLUGS:
    url=BASE+slug+"/"
    result={"slug":slug,"url":url,"passed":False,"errors":[]}
    for attempt in range(2):
        try:
            req=urllib.request.Request(url,headers={"User-Agent":"EMDO-English-Fiche-Quality-Audit/20261021","Accept-Language":"en"})
            with urllib.request.urlopen(req,timeout=45) as resp:
                body=resp.read(1_800_000).decode("utf-8","replace")
                result["http_code"]=resp.status
                result["final_url"]=resp.geturl()
            class ExtractSEO(HTMLParser):
                def __init__(self):
                    super().__init__()
                    self.in_h1=False
                    self.h1_parts=[]
                    self.canonical=""
                    self.noindex=False
                def handle_starttag(self,tag,attrs):
                    d=dict(attrs)
                    if tag=="h1":self.in_h1=True
                    if tag=="link" and "canonical" in d.get("rel","").lower().split():
                        self.canonical=d.get("href","")
                    if tag=="meta" and d.get("name","").lower()=="robots" and "noindex" in d.get("content","").lower():
                        self.noindex=True
                def handle_endtag(self,tag):
                    if tag=="h1":self.in_h1=False
                def handle_data(self,data):
                    if self.in_h1:self.h1_parts.append(data)
            page=ExtractSEO()
            page.feed(body)
            title=" ".join(html.unescape(" ".join(page.h1_parts)).split())
            canonical=page.canonical
            noindex=page.noindex
            result.update({"h1":title[:160],"canonical":canonical,"noindex":noindex})
            if result["http_code"]!=200:result["errors"].append("not_200")
            if urlparse(result["final_url"]).path.rstrip("/")!=urlparse(url).path.rstrip("/"):
                result["errors"].append("redirected_elsewhere")
            if not title:result["errors"].append("missing_h1")
            if not canonical:result["errors"].append("missing_canonical")
            if noindex:result["errors"].append("noindex")
            result["passed"]=not result["errors"]
            break
        except Exception as exc:
            result["errors"]=[str(exc)[:150]]
            if attempt==0:time.sleep(1)
    results.append(result)
    print("EMDO_TROPITOP_18_EN_PUBLIC_QA="+json.dumps(result,ensure_ascii=False),flush=True)
summary={"tested":len(results),"passed":sum(bool(r["passed"]) for r in results),"failures":[r["slug"] for r in results if not r["passed"]]}
print("EMDO_TROPITOP_18_EN_PUBLIC_SUMMARY="+json.dumps(summary),flush=True)
raise SystemExit(0 if summary["passed"]==18 else 1)
