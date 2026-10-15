#!/usr/bin/env python3
"""Read-only live SEO audit for the 12-action evergreen commerce roadmap.
Does not call GA4/GSC or claim organic conversions; JSON is a technical baseline.
"""
import json
import re
import sys
import time
from datetime import datetime, timezone
from urllib.parse import urlsplit
from urllib.request import Request, urlopen

from bs4 import BeautifulSoup

BASE = "https://www.elmercadodeorigen.com"
PAGES = [
    "/",
    "/categoria-producto/hortalizas-verduras/",
    "/categoria-producto/carnes/",
    "/comprar-carne-online-que-mirar-antes-elegir-corte/",
    "/como-comprar-alimentos-gourmet-online-que-mirar-ademas-precio/",
    "/verduras-temporada-espana-calendario-meses-que-comprar/",
    "/que-significa-aceite-oliva-virgen-extra/",
    "/comprar-directamente-al-productor-como-funciona/",
    "/comprar-verduras-hortalizas-online-elegir-frescas-temporada/",
    "/productos-proximidad-que-son-como-reconocerlos-comprarlos/",
]
NEW_EN = [
    "/en/buy-directly-from-food-producers-how-it-works/",
    "/en/buying-fresh-vegetables-online-seasonality-origin-delivery/",
    "/en/how-to-identify-local-food-and-where-to-buy-it/",
]
LINKS = {
    "/comprar-carne-online-que-mirar-antes-elegir-corte/":
      "/comprar-directamente-al-productor-como-funciona/",
    "/como-comprar-alimentos-gourmet-online-que-mirar-ademas-precio/":
      "/productos-proximidad-que-son-como-reconocerlos-comprarlos/",
    "/verduras-temporada-espana-calendario-meses-que-comprar/":
      "/comprar-verduras-hortalizas-online-elegir-frescas-temporada/",
    "/que-significa-aceite-oliva-virgen-extra/":
      "/productos-proximidad-que-son-como-reconocerlos-comprarlos/",
}
NEW_ES = set(PAGES[-3:])
NEW_ALL = NEW_ES | set(NEW_EN)
HEADINGS = {
    "/comprar-carne-online-que-mirar-antes-elegir-corte/":
      "Cuando la compra es a un ganadero o a un especialista",
    "/como-comprar-alimentos-gourmet-online-que-mirar-ademas-precio/":
      "Explora por familias de alimentos, no solo por etiquetas comerciales",
    "/verduras-temporada-espana-calendario-meses-que-comprar/":
      "Del calendario orientativo al pedido real",
    "/que-significa-aceite-oliva-virgen-extra/":
      "Qué cambia al comprar a una almazara",
}

def fetch(route):
    url = BASE + route + ("&" if "?" in route else "?") + "mdo_seo_commerce_audit=" + str(int(time.time()))
    request = Request(url, headers={"Cache-Control": "no-cache", "User-Agent": "MDO-SEO-Commercial-QA/2026"})
    with urlopen(request, timeout=85) as response:
        return response.status, response.read().decode("utf-8", "replace")

def audit(route):
    status, html = fetch(route)
    soup = BeautifulSoup(html, "html.parser")
    title = soup.title.get_text(" ", strip=True) if soup.title else ""
    meta = soup.find("meta", attrs={"name": "description"})
    description = meta.get("content", "").strip() if meta else ""
    can = soup.find("link", rel=lambda v: v and "canonical" in v)
    canonical = can.get("href", "") if can else ""
    h1s = [x.get_text(" ", strip=True) for x in soup.find_all("h1")]
    alts = [str(img.get("alt", "")) for img in soup.find_all("img")]
    doc_links = [urlsplit(a["href"]).path for a in soup.find_all("a", href=True)
                 if urlsplit(a["href"]).hostname in (None, "www.elmercadodeorigen.com", "elmercadodeorigen.com")]
    english_alternate = [
        a.get("href", "") for a in soup.find_all("link", href=True)
        if str(a.get("hreflang", "")).lower().startswith("en")
    ]
    errors, warnings = [], []
    if status != 200: errors.append("http_not_200")
    if urlsplit(canonical).path != route: errors.append("canonical_path_mismatch")
    if not title: errors.append("title_missing")
    if not description: errors.append("description_missing")
    if not h1s: errors.append("h1_missing")
    if len(h1s) > 1: warnings.append("multiple_h1")
    if any("imagen provisional" in alt.lower() for alt in alts):
        warnings.append("provisional_image_alt_detected")
    if route == "/categoria-producto/hortalizas-verduras/":
        text = soup.get_text(" ", strip=True)
        if text.count("Si buscas comprar verduras y hortalizas online") != 1:
            errors.append("vegetable_copy_missing_or_duplicated")
    if route in HEADINGS:
        rendered = soup.get_text(" ", strip=True)
        if rendered.count(HEADINGS[route]) != 1:
            errors.append("editorial_update_missing_or_duplicated")
    if route in LINKS and LINKS[route] not in doc_links:
        errors.append("context_link_missing")
    if route in NEW_ALL:
        main = soup.find("article") or soup.find("main") or soup
        if len(main.get_text(" ", strip=True).split()) < 425:
            errors.append("new_guide_too_short")
        if any("provisional" in x.lower() for x in alts):
            errors.append("new_guide_provisional_image")
        if len(soup.find_all("h2")) < 4: errors.append("new_guide_lacks_structure")
        if not any(x.strip() and "logo" not in x.lower() for x in alts):
            errors.append("new_guide_descriptive_image_missing")
    return {
        "route": route, "http_status": status,
        "title": title, "description": description, "canonical": canonical,
        "h1_count": len(h1s), "h1_first": h1s[0] if h1s else "",
        "image_count": len(alts), "internal_links": len(doc_links),
        "english_alternate": english_alternate[:2],
        "errors": errors, "warnings": warnings,
    }

def main():
    report = {
        "project": "El Mercado de Origen — evergreen commercial SEO",
        "generated_utc": datetime.now(timezone.utc).isoformat(),
        "scope": "public SEO health only; not Google Search Console or GA4",
        "ga4_conversion_data": "not_connected",
        "gsc_page_query_country_data": "not_connected",
        "pages": [], "errors": [],
    }
    queue = PAGES + NEW_EN
    for route in queue:
        try:
            row = audit(route)
        except Exception as exc:
            row = {"route": route, "http_status": None,
                   "errors": ["fetch_failure:" + type(exc).__name__],
                   "warnings": []}
        report["pages"].append(row)
        if row["errors"]:
            report["errors"].append({"route": route, "errors": row["errors"]})
        print(route, "status=", row["http_status"], "errors=", row["errors"],
              "warnings=", row.get("warnings", []), flush=True)
    report["checked"] = len(report["pages"])
    report["passed"] = len(report["errors"]) == 0
    with open("seo-commercial-live-audit-20261015.json", "w", encoding="utf-8") as fp:
        json.dump(report, fp, indent=2, ensure_ascii=False)
    print("SEO_COMMERCIAL_AUDIT", json.dumps({
        "passed": report["passed"], "checked": report["checked"], "failures": len(report["errors"]),
        "gsc": report["gsc_page_query_country_data"], "ga4": report["ga4_conversion_data"],
    }, ensure_ascii=False))
    return 0 if report["passed"] else 1

if __name__ == "__main__":
    sys.exit(main())
