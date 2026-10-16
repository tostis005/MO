#!/usr/bin/env python3
"""Read-only production SEO incident check: origin HTTP headers, bot UA, robots."""
from __future__ import annotations

import html
import json
import re
import sys
import time
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import Request, urlopen

ROOT = "https://www.elmercadodeorigen.com"
PATHS = [
    "/",
    "/en/",
    "/en/shop/",
    "/categoria-producto/hortalizas-verduras/",
    "/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/",
    "/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/",
    "/cuanta-proteina-tiene-carne-ternera/",
    "/cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas/",
    "/en/how-much-protein-in-beef/",
    "/en/why-cut-fruit-vegetables-turn-brown-enzymatic-browning/",
    "/en/kuroge-washu-japanese-black-wagyu/",
]
UAS = {
    "browser": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36",
    "bot_ua_simulated": "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
}
def read(path: str, agent: str) -> dict:
    url = ROOT + path
    req = Request(url, headers={"User-Agent": agent, "Accept": "text/html,application/xhtml+xml,*/*", "Accept-Language": "es,en;q=0.8"})
    start = time.monotonic()
    try:
        with urlopen(req, timeout=15) as resp:
            status = resp.status
            final = resp.geturl()
            headers = resp.headers
            body = resp.read(1600000).decode("utf-8", "replace")
    except HTTPError as exc:
        status = exc.code
        final = exc.geturl()
        headers = exc.headers
        body = exc.read(100000).decode("utf-8", "replace")
    except (URLError, TimeoutError, OSError) as exc:
        return {"path": path, "status": 0, "error": str(exc)[:250]}
    canonical = re.findall(r'<link\b(?=[^>]*\brel\s*=\s*["\']canonical["\'])[^>]*\bhref\s*=\s*["\']([^"\']+)', body, re.I)
    metas = re.findall(r'<meta\b(?=[^>]*\bname\s*=\s*["\'](?:robots|googlebot)["\'])[^>]*>', body, re.I)
    robots = " ".join(html.unescape(m) for m in metas)
    xrobots = headers.get("X-Robots-Tag", "")
    noindex = bool(re.search(r'\b(?:noindex|none)\b', robots + " " + xrobots, re.I))
    return {
        "path": path, "status": status,
        "final": final, "seconds": round(time.monotonic() - start, 2),
        "canonical": [html.unescape(c) for c in canonical],
        "meta_robots": robots[:320], "x_robots_tag": xrobots[:320],
        "noindex": noindex, "cache_control": headers.get("Cache-Control", "")[:180],
        "vary": headers.get("Vary", "")[:120],
        "body_bytes_read": len(body.encode("utf-8")), "title_count": len(re.findall(r'<title\b', body, re.I)),
    }

def main() -> int:
    results = []
    errors = []
    for path in PATHS:
        variants = {k: read(path, ua) for k, ua in UAS.items()}
        results.append(variants)
        expected = ROOT + path
        for name, item in variants.items():
            if item["status"] != 200 or item.get("noindex"):
                errors.append({"path": path, "ua": name, "reason": "bad_http_or_noindex", "status": item["status"], "noindex": item.get("noindex")})
            if item.get("canonical") != [expected]:
                errors.append({"path": path, "ua": name, "reason": "non_self_canonical", "canonical": item.get("canonical")})
        if variants["browser"].get("canonical") != variants["bot_ua_simulated"].get("canonical"):
            errors.append({"path": path, "reason": "different_canonical_per_user_agent"})
        print("SEO_INCIDENT_PATH", path, "browser", variants["browser"]["status"], "bot_simulated", variants["bot_ua_simulated"]["status"], "seconds", variants["browser"].get("seconds"), variants["bot_ua_simulated"].get("seconds"), flush=True)
    summary = {"target": ROOT, "browser_and_simulated_bot": True, "paths_tested": len(PATHS), "error_count": len(errors), "errors": errors, "pages": results, "note": "A Googlebot User-Agent string is NOT verified Googlebot; results cannot prove crawler access from Google IPs."}
    with open("seo-global-incident-headers-20261016.json", "w", encoding="utf8") as f:
        json.dump(summary, f, ensure_ascii=False, indent=2)
    print("SEO_GLOBAL_INCIDENT_SUMMARY", json.dumps({"paths": len(PATHS), "errors": errors}, ensure_ascii=False), flush=True)
    return 1 if errors else 0

if __name__ == "__main__":
    sys.exit(main())
