"""Read-only 7-page catalog inventory for Tropitop; no carts or orders."""
import collections
import json
import re
import time
from urllib.parse import urljoin, urlparse
import requests
from bs4 import BeautifulSoup

BASE = "https://tropitop.com/es/84-disponible"
session = requests.Session()
session.headers.update({"User-Agent":"EMDO public catalog QA (+https://www.elmercadodeorigen.com/)", "Accept-Language":"es-ES,es;q=0.9"})
all_products = {}
page_summaries = []
for page in range(1, 10):
    url = f"{BASE}?page={page}"
    resp = session.get(url, timeout=22)
    if resp.status_code != 200:
        print("CATALOG_PAGE_FAILED", page, resp.status_code)
        break
    soup = BeautifulSoup(resp.content,"html.parser")
    cards = soup.select("article.product-miniature, .product-miniature.js-product-miniature")
    found = {}
    for card in cards:
        anchors = card.select("a[href]")
        title = card.select_one("h2.product-title, h3.product-title, .product-title")
        for a in anchors:
            href = urljoin(resp.url, a.get("href", ""))
            parsed = urlparse(href)
            if parsed.hostname not in {"tropitop.com","www.tropitop.com"}:
                continue
            match = re.search(r"^/es/([^/]+)/([0-9]+)(?:-[0-9]+)?-[a-z0-9-]+[.]html$", parsed.path)
            if not match:
                continue
            key = match.group(2)
            info = {"source_id":int(key), "url":href.split("#")[0].split("?")[0],
                    "category":match.group(1), "title":title.get_text(" ",strip=True) if title else a.get_text(" ",strip=True)}
            found[key]=info
            break
    page_summaries.append({"page":page,"cards":len(cards),"discovered":len(found)})
    print("CATALOG_PAGE",page,"status",resp.status_code,"cards",len(cards),"products",len(found))
    if not found:
        break
    if not (set(found) - set(all_products)) and page > 1:
        print("CATALOG_REPEATED_PAGE", page)
        break
    all_products.update(found)
    if len(cards) < 24:
        break
    time.sleep(.55)
groups = collections.defaultdict(list)
for entry in all_products.values(): groups[entry["category"]].append(entry["title"])
out = {"total_unique":len(all_products),"pages":page_summaries,"categories":
       {cat:{"count":len(titles),"samples":titles[:4]} for cat,titles in sorted(groups.items())},
       "samples":list(all_products.values())[:9]}
print("TROPITOP_CATALOG_DISCOVERY="+json.dumps(out,ensure_ascii=False,separators=(",",":")))
if len(all_products)<50: raise SystemExit("Catalog unexpectedly small; investigate query/pagination before bulk onboarding")
