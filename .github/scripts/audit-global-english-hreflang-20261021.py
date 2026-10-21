#!/usr/bin/env python3
"""Read-only end-to-end native-language SEO audit for the entire English island.

Two modes: articles (all 24 ES/EN pairs), global (stratified pairs from live
English sitemap). Writes JSON evidence, exits nonzero on verified SEO failures.
Transient transport failures are counted separately from content errors.
"""
import concurrent.futures
import html
import json
import pathlib
import re
import sys
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET
from html.parser import HTMLParser
from urllib.parse import urlparse, urlunparse

ROOT = "https://www.elmercadodeorigen.com"
OUT = pathlib.Path(".audit-output")
OUT.mkdir(exist_ok=True)
AGENT = "Mozilla/5.0 (compatible; EMDO-Native-Language-SEO-Audit/20261021)"
class Parser(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.links = []
        self.meta = []
        self.h1 = 0
        self.lang = ""
    def handle_starttag(self, tag, attrs):
        d = dict(attrs)
        if tag == "html": self.lang = d.get("lang", "")
        if tag == "link": self.links.append(d)
        elif tag == "meta": self.meta.append(d)
        elif tag == "h1": self.h1 += 1
    def alternates(self):
        return {d.get("hreflang", "").lower(): d.get("href", "") for d in self.links if d.get("hreflang")}
    def canonicals(self):
        return [d.get("href", "") for d in self.links if "canonical" in d.get("rel", "").split()]
    def meta_value(self, key):
        for x in self.meta:
            if x.get("name", "").lower() == key.lower() or x.get("property", "").lower() == key.lower():
                return x.get("content", "")
        return ""

def norm(url):
    u = urlparse(html.unescape(url or ""))
    return urlunparse((u.scheme, u.netloc.lower(), u.path.rstrip("/") + "/", "", "", ""))

def get(url, timeout=58):
    req = urllib.request.Request(url, headers={"User-Agent": AGENT, "Cache-Control": "no-cache"})
    errors = []
    for attempt in range(2):
        try:
            with urllib.request.urlopen(req, timeout=timeout if attempt == 0 else 35) as r:
                return {"code": r.status, "final_url": r.geturl(), "page": r.read(5000000).decode("utf-8", "replace"), "errors": errors}
        except Exception as ex:
            errors.append(repr(ex)[:240])
    return {"errors": errors}

def check_one(row):
    wanted_es, wanted_en, language, group = row
    requested = wanted_es if language == "es" else wanted_en
    probe = get(requested + "?emdo_global_seo_check=20261021")
    out = {"group": group, "language": language, "url": requested}
    if "page" not in probe:
        out["transport_errors"] = probe["errors"]
        return out
    p = Parser()
    try: p.feed(probe["page"])
    except Exception as ex:
        out["parse_error"] = str(ex)
        return out
    alts = p.alternates()
    canon = p.canonicals()
    es = alts.get("es-es") or alts.get("es") or ""
    en = alts.get("en") or alts.get("en-us") or ""
    xdef = alts.get("x-default", "")
    out.update({"code": probe["code"], "bytes": len(probe["page"]),
                "canonical": canon[:2], "hreflang_es": es, "hreflang_en": en,
                "hreflang_x_default": xdef, "h1_count": p.h1, "html_lang": p.lang,
                "og_image": bool(p.meta_value("og:image")),
                "description": bool(p.meta_value("description")),
                "robots": p.meta_value("robots")[:90]})
    bugs = []
    if probe["code"] != 200: bugs.append("http_not_200")
    if norm(es) != norm(wanted_es): bugs.append("incorrect_hreflang_es")
    if norm(en) != norm(wanted_en): bugs.append("incorrect_hreflang_en")
    if norm(xdef) != norm(wanted_es): bugs.append("incorrect_hreflang_x_default")
    if not canon or norm(canon[0]) != norm(requested): bugs.append("non_self_canonical")
    if "noindex" in p.meta_value("robots").lower(): bugs.append("noindex")
    if group == "editorial24":
        if p.h1 != 1: bugs.append("unexpected_article_h1_count")
        if not p.meta_value("og:image"): bugs.append("missing_og_image")
        if not p.meta_value("description"): bugs.append("missing_description")
    if bugs: out["seo_issues"] = bugs
    return out

def article_pairs():
    pairs = []
    for id in range(1, 25):
        filename = pathlib.Path(f".github/data/editorial-nuevos-productores-20261020-{id:02}.json")
        a = json.loads(filename.read_text(encoding="utf-8"))
        pairs.append((ROOT+"/"+a["slug"]+"/", ROOT+"/en/"+a["en_slug"]+"/", "editorial24"))
    return pairs

def select_evenly(urls, maximum):
    urls = list(dict.fromkeys(urls))
    if len(urls) <= maximum: return urls
    return [urls[round(i * (len(urls) - 1) / (maximum - 1))] for i in range(maximum)]

def global_pairs():
    exported = OUT / "english-seed-urls.json"
    if exported.exists():
        obj = json.loads(exported.read_text(encoding="utf-8"))
        urls = list(obj.get("urls", []))
        print("GLOBAL_URL_SOURCE=verified_WordPress_database", len(urls), flush=True)
    else:
        urls = []
    sitemap = get(ROOT + "/english-sitemap.xml/", timeout=60) if not urls else {}
    if not urls and "page" not in sitemap:
        print("GLOBAL_SITEMAP_TRANSPORT_FAILURE", sitemap.get("errors"), flush=True)
    elif not urls:
        try:
            root = ET.fromstring(sitemap["page"])
            urls = [(x.text or "").strip() for x in root.iter() if x.tag.rsplit("}",1)[-1] == "loc"]
            if not urls:
                print("GLOBAL_SITEMAP_EMPTY", repr(sitemap["page"][:250]), flush=True)
        except Exception as ex:
            print("GLOBAL_SITEMAP_PARSE_FAILURE", str(ex)[:180], flush=True)
    urls = [u for u in urls if urlparse(u).netloc in ("www.elmercadodeorigen.com", "elmercadodeorigen.com")
            and urlparse(u).path.startswith("/en/")]
    active24 = {en for _es, en, _g in article_pairs()}
    products = [u for u in urls if urlparse(u).path.startswith("/en/product/")]
    categories = [u for u in urls if urlparse(u).path.startswith(("/en/product-category/","/en/category/"))]
    other = [u for u in urls if u not in active24 and u not in products and u not in categories and
             urlparse(u).path not in ("/en/", "/en/shop/")]
    seeds = select_evenly(products, 8) + select_evenly(categories, 8) + select_evenly(other, 12)
    seeds += [ROOT+"/en/",ROOT+"/en/shop/"]
    seeds = list(dict.fromkeys(seeds))
    results = []
    for en in seeds:
        origin_es = None  # The Spanish destination is inferred from the original (unrepaired) output?
        results.append((None,en,"global"))
    return results, {"sitemap_english_urls": len(urls), "sitemap_products": len(products),
                     "sitemap_categories": len(categories), "sitemap_older_posts_pages":len(other)}

def probe_global_url(row):
    _none, en_url, group = row
    received = get(en_url + "?emdo_global_seo_check=20261021")
    basic = {"url": en_url, "group":group}
    if "page" not in received:
        basic["transport_errors"] = received["errors"]
        return basic
    doc = Parser();doc.feed(received["page"])
    alts = doc.alternates()
    es = alts.get("es-es") or alts.get("es") or ""
    eng = alts.get("en") or alts.get("en-us") or ""
    xdef = alts.get("x-default") or ""
    canon = doc.canonicals()
    issues=[]
    esurl = urlparse(es)
    if received["code"] != 200: issues.append("http_not_200")
    if esurl.netloc not in ("elmercadodeorigen.com", "www.elmercadodeorigen.com") or esurl.path.startswith("/en/"):
        issues.append("spanish_hreflang_wrong_locale")
    if norm(xdef) != norm(es): issues.append("x_default_differs_from_es")
    if norm(eng) != norm(en_url): issues.append("english_hreflang_wrong_target")
    if not canon or norm(canon[0]) != norm(en_url): issues.append("canonical_not_self")
    if "noindex" in doc.meta_value("robots").lower():issues.append("noindex")
    basic.update({"code":received["code"],"bytes":len(received["page"]),"es":es,"en":eng,
                  "x_default":xdef,"canonical":canon[:2],"h1_count":doc.h1,"og_image":bool(doc.meta_value("og:image"))})
    if issues:basic["seo_issues"]=issues
    # Also verify the Spanish counterpart actually resolves to a published page and reciprocates.
    if esurl.netloc in ("www.elmercadodeorigen.com","elmercadodeorigen.com") and not esurl.path.startswith("/en/"):
        sp = get(es+"?emdo_global_seo_check=20261021", timeout=38)
        if "page" in sp:
            doc2=Parser();doc2.feed(sp["page"])
            rec=doc2.alternates().get("en") or doc2.alternates().get("en-us") or ""
            basic["spanish_http"]=sp["code"]
            basic["spanish_links_back"]=norm(rec)==norm(en_url)
            if sp["code"]!=200 or not basic["spanish_links_back"]:
                basic.setdefault("seo_issues",[]).append("spanish_reciprocity_failure")
        else:
            basic["spanish_transport_errors"]=sp["errors"]
    return basic

def main():
    mode = sys.argv[1] if len(sys.argv)>1 else "articles"
    if mode not in ("articles","global"):raise SystemExit("Choose articles or global")
    if mode == "articles":
        pairs=article_pairs()
        tasks=[(es,en,lang,group) for es,en,group in pairs for lang in ("es","en")]
        with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
            results=list(pool.map(check_one,tasks))
        details={"sample": "All 24 bilingual article pairs", "pairs":24}
    else:
        samples, details = global_pairs()
        print("GLOBAL_SITEMAP_COUNTS",json.dumps(details),flush=True)
        if len(samples)<8: raise SystemExit("English sitemap yielded too few URLs for a meaningful audit")
        with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
            results=list(pool.map(probe_global_url,samples))
    for x in results:
        if "seo_issues" in x: print("CONFIRMED_SEO_FAILURE",json.dumps(x,ensure_ascii=False),flush=True)
        elif "transport_errors" in x:print("TRANSPORT_LIMIT",json.dumps(x,ensure_ascii=False),flush=True)
        else:print("SEO_VERIFIED",x.get("url"),flush=True)
    issues=[x for x in results if x.get("seo_issues") or x.get("parse_error")]
    transport=[x for x in results if x.get("transport_errors")]
    spanish_transport=[x for x in results if x.get("spanish_transport_errors")]
    summary={"mode":mode,"tested":len(results),"confirmed_seo_failures":len(issues),
             "main_transport_failures":len(transport),"spanish_counterpart_transport_failures":len(spanish_transport),**details}
    evidence={"summary":summary,"results":results}
    (OUT/f"global-english-seo-{mode}-20261021.json").write_text(json.dumps(evidence,ensure_ascii=False,indent=2))
    print("EMDO_GLOBAL_ENGLISH_SEO_AUDIT",json.dumps(summary,ensure_ascii=False),flush=True)
    if issues:raise SystemExit(f"{len(issues)} confirmed SEO failures")
    if transport:raise SystemExit(f"{len(transport)} English/Spanish page fetches could not be verified")
if __name__=="__main__": main()
