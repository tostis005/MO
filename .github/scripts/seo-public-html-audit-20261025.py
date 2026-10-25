#!/usr/bin/env python3
"""Read-only public SEO checks. Collect evidence, never mutate WordPress."""
import json
import re
import sys
import time
import urllib.request
from html.parser import HTMLParser
from urllib.parse import urlsplit
from uuid import uuid4

BASE = "https://www.elmercadodeorigen.com"
PAGES = {
    "/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/": {"type":"post"},
    "/cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias/": {"type":"post"},
    "/cuanta-proteina-tiene-carne-ternera/": {"type":"post"},
    "/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/": {"type":"post"},
    "/cuanto-dura-carne-cocinada-nevera-conservacion-segura/": {"type":"post"},
    "/categoria-producto/legumbres/": {
        "type":"category","needle":"Alubias, garbanzos y lentejas en distintas variedades, seleccionadas"
    },
    "/categoria-producto/aceites/": {
        "type":"category","needle":"El aceite de oliva virgen extra es un zumo 100% natural"
    },
    "/categoria-producto/jamones-paletas/": {
        "type":"category","needle":"Descubre nuestra selección de jamones y paletas de productores españoles"
    },
}

class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.in_body = False
        self.exclude = 0
        self.h1 = []
        self.in_h1 = False
        self.titles = []
        self.in_title = False
        self.meta = []
        self.canonical = []
        self.description_markers = 0
        self.body_text = []
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if tag == "body": self.in_body = True
        if tag in ("script","style"): self.exclude += 1
        if tag == "h1":
            self.h1.append("")
            self.in_h1 = True
        if tag == "title":
            self.titles.append("")
            self.in_title = True
        if tag == "meta" and (a.get("name") or "").lower() == "description":
            self.meta.append(a.get("content") or "")
        if tag == "link" and (a.get("rel") or "").lower() == "canonical":
            self.canonical.append(a.get("href") or "")
        if a.get("data-emdo-category-description") == "1":
            self.description_markers += 1
    def handle_endtag(self, tag):
        if tag == "body": self.in_body = False
        if tag in ("script","style"): self.exclude=max(0,self.exclude-1)
        if tag == "h1": self.in_h1 = False
        if tag == "title": self.in_title = False
    def handle_data(self, text):
        if self.in_h1 and self.h1: self.h1[-1] += text
        if self.in_title and self.titles: self.titles[-1] += text
        if self.in_body and not self.exclude: self.body_text.append(text)

def tidy(s):
    return re.sub(r"\s+"," ",s.replace("\u00a0"," ")).strip()

out={"generated_utc":time.strftime("%Y-%m-%dT%H:%M:%SZ",time.gmtime()),
     "source":"Fresh public HTML via GitHub runner, no GSC live API",
     "release_under_test":"3d85eb687168115579ce39692ab1d1d4353ae16b",
     "rows":[], "all_healthy":True}
for path,cfg in PAGES.items():
    url=BASE+path+"?seo_public_audit="+uuid4().hex
    row={"path":path,"type":cfg["type"]}
    try:
        request=urllib.request.Request(url,headers={
            "User-Agent":"EMDO-public-SEO-validator/2026-10-25",
            "Cache-Control":"no-cache",
            "Pragma":"no-cache"})
        with urllib.request.urlopen(request,timeout=30) as response:
            html=response.read(3_000_000).decode("utf-8","replace")
            row["http_status"]=response.status
            row["response_headers"]={k:v[:130] for k,v in response.headers.items()
                if any(t in k.lower() for t in ("cache","age","server","vary","x-el-mercado","x-rocket","litespeed"))}
            row["final_url"]=response.url.split("?")[0]
            row["body_bytes"]=len(html.encode())
        parsed=Document(); parsed.feed(html)
        body=tidy(" ".join(parsed.body_text))
        row["title"]=parsed.titles[0][:120] if parsed.titles else ""
        row["h1_count"]=len(parsed.h1)
        row["h1_text"]=[tidy(x)[:120] for x in parsed.h1[:4]]
        row["meta_description_count"]=len(parsed.meta)
        row["canonical"]=parsed.canonical[0] if parsed.canonical else ""
        row["canonical_count"]=len(parsed.canonical)
        row["category_extra_copy_blocks"]=parsed.description_markers
        if "needle" in cfg:
            needle=cfg["needle"]
            row["description_prefix_count"]=body.count(needle)
            at=body.find("El aceite de oliva")
            if path.endswith("/aceites/"):
                row["aceites_content_excerpt"]=body[max(0,at-120):at+350] if at>=0 else body[:650]
        if cfg["type"]=="post":
            hits=list(re.finditer(r"<h1\\b[^>]*>.*?</h1\\s*>",html,re.I|re.S))
            row["h1_markup_context"]=[
                re.sub(r"\\s+"," ",html[max(0,m.start()-250):min(len(html),m.end()+250)])[:950]
                for m in hits[:3]]
            row["cache_markers"]={
                "static_v2": "elmercado-blog-static-v2" in html,
                "seo_h1_marker": "elmercado_blog_final_h1_guard_20261025" in html,
            }
        bad = row["http_status"]!=200 or len(parsed.h1)!=1
        if "needle" in cfg and row["description_prefix_count"]!=1: bad=True
        row["ok"]=not bad
    except Exception as exc:
        row["error"]=type(exc).__name__+": "+str(exc)[:150]
        row["ok"]=False
    out["rows"].append(row)
    if not row["ok"]: out["all_healthy"]=False
print(json.dumps(out,indent=2,ensure_ascii=False))
