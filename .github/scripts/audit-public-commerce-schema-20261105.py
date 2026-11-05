#!/usr/bin/env python3
"""Read-only SEO audit of public indexability and WooCommerce Product/Offer JSON-LD."""
from __future__ import annotations
import json
import os
from html import unescape
from urllib.request import Request, urlopen
from urllib.parse import urlsplit
from bs4 import BeautifulSoup

BASE = "https://www.elmercadodeorigen.com"
PRODUCTS = [
    "cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio",
    "sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l",
    "aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27",
    "aceite-de-oliva-virgen-extra-5l",
    "aceite-de-oliva-virgen-extra-1l",
    "aceite-de-oliva-virgen-extra-500ml-2",
    "jamon-de-bellota-100-iberico",
    "jamon-cebo-de-campo-iberico-50-montjam",
]
PAGES = [
    "/categoria-producto/aceites/",
    "/categoria-producto/jamones-paletas/",
    "/categoria-producto/setas-y-hongos/",
    "/tienda/1957/",
]
REPORT = []
CRITICAL = []

def walk(obj):
    if isinstance(obj, list):
        for child in obj:
            yield from walk(child)
    elif isinstance(obj, dict):
        yield obj
        for child in obj.values():
            if isinstance(child, (dict, list)):
                yield from walk(child)

def fetch(path):
    url = BASE + path + "?seo_product_audit=" + os.environ.get("GITHUB_RUN_ID", "1")
    request = Request(url, headers={"User-Agent": "Mozilla/5.0 (EMDO public SEO audit)", "Cache-Control": "no-cache"})
    with urlopen(request, timeout=70) as response:
        html = response.read().decode("utf-8", "replace")
        return response.status, html

for path in ["/producto/" + slug + "/" for slug in PRODUCTS] + PAGES:
    row = {"path": path, "kind": "product" if path.startswith("/producto/") else "archive"}
    try:
        status, html = fetch(path)
        soup = BeautifulSoup(html, "html.parser")
        canonical = soup.find("link", rel=lambda value: value and "canonical" in value)
        canonical_url = canonical.get("href", "") if canonical else ""
        row.update({
            "status": status,
            "canonical": canonical_url,
            "canonical_matches_url": urlsplit(canonical_url).path == path,
            "title": soup.title.get_text(" ", strip=True)[:135] if soup.title else "",
            "h1_count": len(soup.select("h1")),
            "robots": [m.get("content", "") for m in soup.find_all("meta", attrs={"name": "robots"})],
            "metadescription": bool(soup.find("meta", attrs={"name": "description"})),
        })
        schema_nodes = []
        for script in soup.find_all("script", attrs={"type": "application/ld+json"}):
            raw = script.string or script.get_text()
            try:
                schema_nodes.extend(walk(json.loads(raw)))
            except (ValueError, TypeError):
                pass
        candidates = []
        for node in schema_nodes:
            types = node.get("@type", [])
            if isinstance(types, str):
                types = [types]
            if any(t in ("Product", "ProductGroup") for t in types):
                offers = node.get("offers", [])
                if isinstance(offers, dict):
                    offers = [offers]
                candidates.append({
                    "type": types,
                    "name": str(node.get("name", ""))[:150],
                    "sku": str(node.get("sku", "")),
                    "offers_count": len(offers) if isinstance(offers, list) else 0,
                    "offers": [{"type": x.get("@type", ""), "price": x.get("price"), "lowPrice": x.get("lowPrice"),
                                "highPrice": x.get("highPrice"), "availability": x.get("availability"),
                                "priceCurrency": x.get("priceCurrency")}
                               for x in offers if isinstance(x, dict)][:6],
                })
        row["product_schema"] = candidates[:4]
        row["schema_types"] = sorted(set(str(node.get("@type")) for node in schema_nodes if "@type" in node))[:24]
        if status != 200 or not row["canonical_matches_url"] or any("noindex" in x.lower() for x in row["robots"]):
            CRITICAL.append({"path": path, "reason": "unavailable, noncanonical, or noindex"})
        if row["kind"] == "product" and not candidates:
            CRITICAL.append({"path": path, "reason": "missing Product/ProductGroup JSON-LD"})
        if row["kind"] == "product" and candidates and not any(x["offers_count"] for x in candidates):
            CRITICAL.append({"path": path, "reason": "no offers in Product JSON-LD"})
    except Exception as error:
        row["error"] = repr(error)
        CRITICAL.append({"path": path, "reason": repr(error)})
    REPORT.append(row)
    print("SEO_SCHEMA_PAGE=" + json.dumps(row, ensure_ascii=False, separators=(",", ":")), flush=True)

print("SEO_SCHEMA_SUMMARY=" + json.dumps({"checked": len(REPORT), "critical": CRITICAL}, ensure_ascii=False), flush=True)
if CRITICAL:
    # Audit intentionally does not change production. Follow up with targeted fixes.
    raise SystemExit(2)
