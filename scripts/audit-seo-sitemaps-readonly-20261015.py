#!/usr/bin/env python3
"""Read-only XML sitemap and sampled indexability audit for production."""
import json
import re
import sys
from collections import Counter
from html import unescape
from html.parser import HTMLParser
from urllib.parse import urlparse
from urllib.request import Request, urlopen
from xml.etree import ElementTree as ET

ROOT = "https://www.elmercadodeorigen.com"
findings = {"sitemaps": [], "errors": [], "warnings": [], "html_samples": []}

def fetch(url, cap=6000000):
    req = Request(url, headers={"User-Agent": "EMDO-SEO-Sitemap-Audit/1.0", "Accept": "*/*"})
    with urlopen(req, timeout=35) as r:
        body = r.read(cap + 1)
        if len(body) > cap:
            raise ValueError("oversized response: " + url)
        return r.status, r.url, body

def entries(url):
    status, final, data = fetch(url)
    root = ET.fromstring(data)
    kind = root.tag.split("}")[-1]
    if kind not in ("urlset", "sitemapindex"):
        raise ValueError("Invalid XML sitemap: " + url)
    records = []
    for child in root:
        row = {n.tag.split("}")[-1]: n.text for n in child}
        if row.get("loc"):
            records.append(row)
    findings["sitemaps"].append({"url": url, "redirected_to": final, "status": status, "type": kind, "urls": len(records)})
    return kind, records

class Head(HTMLParser):
    def __init__(self):
        super().__init__()
        self.lang = ""
        self.canonical = []
        self.robots = []
        self.hreflang = []
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "html": self.lang = a.get("lang", "")
        if tag == "link" and "canonical" in a.get("rel", "").lower().split():
            self.canonical.append(a.get("href", ""))
        if tag == "link" and a.get("hreflang"):
            self.hreflang.append(a["hreflang"])
        if tag == "meta" and a.get("name", "").lower() in ("robots", "googlebot"):
            self.robots.append(a.get("content", "").lower())

def clean(url):
    p = urlparse(unescape(url))
    return (p.scheme, p.netloc.lower(), p.path.rstrip("/"))

def sample_page(url):
    try:
        status, final, data = fetch(url, cap=3500000)
        parser = Head()
        parser.feed(data[:900000].decode("utf-8", "replace"))
        record = {"url": url, "http": status, "final": final,
                  "canonical": parser.canonical, "robots": parser.robots,
                  "lang": parser.lang, "hreflang": parser.hreflang}
        findings["html_samples"].append(record)
        if status != 200:
            findings["errors"].append("HTTP not 200: " + url)
        if len(parser.canonical) != 1 or clean(parser.canonical[0]) != clean(url):
            findings["warnings"].append("Canonical mismatch: " + url)
        if any("noindex" in directive for directive in parser.robots):
            findings["warnings"].append("Noindex URL in sitemap: " + url)
        if "/en/" in urlparse(url).path and parser.lang and not parser.lang.lower().startswith("en"):
            findings["warnings"].append("Non-English lang in EN URL: " + url)
    except Exception as exc:
        findings["errors"].append("Sample failed " + url + ": " + str(exc))

def main():
    try:
        _, _, body = fetch(ROOT + "/robots.txt", 180000)
        robots = body.decode("utf-8", "replace")
        findings["robots_sitemaps"] = re.findall(r"(?im)^sitemap:\s*(\S+)", robots)
        if not any("sitemap_index.xml" in x for x in findings["robots_sitemaps"]):
            findings["warnings"].append("robots.txt does not list sitemap_index.xml")
    except Exception as exc:
        findings["errors"].append("robots.txt failed: " + str(exc))
    indexed = []
    try:
        kind, child = entries(ROOT + "/sitemap_index.xml")
        if kind != "sitemapindex":
            findings["warnings"].append("Main sitemap is not an index")
        else:
            findings["child_count"] = len(child)
            findings["audited_children"] = min(30, len(child))
            if len(child) > 30:
                findings["warnings"].append("Child coverage limited to 30 sitemaps")
            for item in child[:30]:
                try:
                    typ, urls = entries(item["loc"])
                    if typ == "urlset": indexed.extend(e["loc"] for e in urls)
                except Exception as exc:
                    findings["errors"].append("Child failed: " + str(exc))
    except Exception as exc:
        findings["errors"].append("Index failed: " + str(exc))
    try:
        typ, en = entries(ROOT + "/english-sitemap.xml/")
        en_urls = [row["loc"] for row in en] if typ == "urlset" else []
        findings["english_urls"] = len(en_urls)
        if typ != "urlset" or ROOT + "/en/shop/" not in en_urls:
            findings["errors"].append("EN sitemap missing required /en/shop/ URL or XML type")
        if len(en_urls) < 50:
            findings["warnings"].append("English sitemap has fewer than 50 URLs")
        indexed.extend(en_urls)
    except Exception as exc:
        en_urls = []
        findings["errors"].append("English sitemap failed: " + str(exc))
    dupe = [(url, n) for url, n in Counter(indexed).items() if n > 1]
    findings["duplicate_urls"] = dupe[:25]
    if dupe:
        findings["warnings"].append(str(len(dupe)) + " overlapping sitemap entries, review canonical index")
    off_domain = [u for u in indexed if urlparse(u).netloc not in
                  ("www.elmercadodeorigen.com", "elmercadodeorigen.com")]
    if off_domain:
        findings["errors"].append(str(len(off_domain)) + " sitemap URLs have external domains")
    categories = sorted(u for u in en_urls if "/en/category/" in u or "/en/product-category/" in u)
    products = sorted(u for u in en_urls if "/en/product/" in u)
    articles = sorted(u for u in en_urls if "/en/" in u and
                      u not in categories and u not in products and
                      urlparse(u).path not in ("/en/", "/en/shop/"))
    selected = [ROOT + "/en/shop/"] + categories[:8] + products[:4] + articles[:8]
    for url in dict.fromkeys(selected):
        if url in en_urls: sample_page(url)
    with open("seo-sitemap-audit.json", "w", encoding="utf-8") as f:
        json.dump(findings, f, ensure_ascii=False, indent=2)
    with open("seo-sitemap-summary.md", "w", encoding="utf-8") as f:
        f.write("### Auditoría SEO técnica: sitemaps (solo lectura)\n")
        f.write(f"- XML examinados: {len(findings['sitemaps'])}\n")
        f.write(f"- URLs inglesas: {findings.get('english_urls', 'sin datos')}\n")
        f.write(f"- Páginas HTML examinadas: {len(findings['html_samples'])}\n")
        f.write(f"- Errores: {len(findings['errors'])}; advertencias: {len(findings['warnings'])}\n")
        for severity in ("errors", "warnings"):
            for msg in findings[severity][:30]:
                f.write(f"- {severity}: {msg}\n")
    print(json.dumps(findings, ensure_ascii=False, indent=2))
    return 1 if findings["errors"] else 0

if __name__ == "__main__":
    sys.exit(main())
