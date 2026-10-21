#!/usr/bin/env python3
"""Read-only QA for exactly three English blog archives previously redirect-looping."""
import html
import json
import re
import urllib.error
import urllib.request
from urllib.parse import urlparse

HOST = "https://www.elmercadodeorigen.com"
SLUGS = ["meat", "preserves", "hams-and-shoulders"]
class LimitRedirect(urllib.request.HTTPRedirectHandler):
    def __init__(self):
        super().__init__()
        self.jumps = []
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        self.jumps.append({"code": code, "url": newurl})
        if len(self.jumps) > 4 or newurl in [item["url"] for item in self.jumps[:-1]]:
            raise urllib.error.HTTPError(newurl, 399, "redirect loop", headers, fp)
        return super().redirect_request(req, fp, code, msg, headers, newurl)

def check(slug):
    target = f"{HOST}/en/category/{slug}/"
    redirect = LimitRedirect()
    opener = urllib.request.build_opener(redirect)
    req = urllib.request.Request(target + "?seo3q=20261021",
                headers={"User-Agent": "EMDO-3-English-Blog-Category-SEO-QA/20261021"})
    data = {"slug": slug, "requested": target}
    try:
        with opener.open(req, timeout=45) as response:
            body = response.read(1_800_000).decode("utf-8", "replace")
            data["code"] = response.status
            data["final_url"] = response.geturl()
        data["redirects"] = redirect.jumps
        path = urlparse(data["final_url"]).path.rstrip("/")
        data["ok"] = (data["code"] == 200
                      and path == urlparse(target).path.rstrip("/")
                      and "<h1" in body.lower()
                      and len(redirect.jumps) < 3
                      and 'rel="canonical"' in body.lower())
    except Exception as exc:
        data["ok"] = False
        data["error"] = str(exc)[:240]
        data["redirects"] = redirect.jumps
    print("EMDO_ENGLISH_CATEGORY_QA " + json.dumps(data, ensure_ascii=False), flush=True)
    return data

results = [check(slug) for slug in SLUGS]
print("EMDO_ENGLISH_CATEGORY_SUMMARY " + json.dumps({
  "checked": len(results), "passed": sum(int(x["ok"]) for x in results),
  "failed": [x for x in results if not x["ok"]]
}, ensure_ascii=False), flush=True)
raise SystemExit(0 if all(x["ok"] for x in results) else 1)
