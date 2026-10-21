#!/usr/bin/env python3
"""Public, read-only smoke QA of 13 newly translated buyable product routes."""
import html
import json
import re
import time
import urllib.error
import urllib.request
from urllib.parse import urlparse

BASE="https://www.elmercadodeorigen.com/en/product/"
SLUGS=[
  "bean-to-bar-chocolate-making-kit-isabel",
  "organic-fairtrade-white-chocolate-quinoa-cracker",
  "diy-cocoa-bean-to-chocolate-kit-isabel",
  "chocobone-fairtrade-white-chocolate-caramelised-almonds",
  "first-cut-zebra-steak-approx-200g",
  "ostrich-burger-meat-pack-two-150g",
  "bison-burger-meat-150g",
  "bison-burger-meat-pack-2x150g",
  "buffalo-burger-meat-150g",
  "buffalo-burger-meat-pack-2x150g",
  "crocodile-burger-meat-150g",
  "buffalo-burger-meat-500g",
  "black-angus-burger-meat-pack-2x150g"
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
            heading=re.search(r"<h1\\b[^>]*>(.*?)</h1>",body,re.I|re.S)
            title=html.unescape(re.sub(r"<[^>]*>"," ",heading.group(1))) if heading else ""
            title=re.sub(r"\\s+"," ",title).strip()
            canonical=re.search(r"<link\\b(?=[^>]*rel=[\\\"']canonical[\\\"'])(?=[^>]*href=[\\\"']([^\\\"']+))[^^>]*>",body,re.I|re.S)
            if not canonical:
                canonical=re.search(r'<link[^>]*rel=["\\']canonical["\\'][^>]*href=["\\']([^"\\']+)',body,re.I)
            noindex=bool(re.search(r"<meta[^>]*name=[\\\"']robots[\\\"'][^>]*content=[\\\"'][^\\\"']*noindex",body,re.I))
            result.update({"h1":title[:160],"canonical":canonical.group(1) if canonical else "","noindex":noindex})
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
    print("EMDO_13_EN_PUBLIC_QA="+json.dumps(result,ensure_ascii=False),flush=True)
summary={"tested":len(results),"passed":sum(bool(r["passed"]) for r in results),"failures":[r["slug"] for r in results if not r["passed"]]}
print("EMDO_13_EN_PUBLIC_SUMMARY="+json.dumps(summary),flush=True)
raise SystemExit(0 if summary["passed"]==13 else 1)
