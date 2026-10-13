#!/usr/bin/env python3
"""Read-only real-HTML audit of the 20 GSC batch 1 URLs.

Expected metadata comes directly from the PHP module in the repository.
Checks HTTP 200, normalized SEO title, exactly one H1 and meta description.
No authentication, no modification of WordPress, no private information.
"""
from concurrent.futures import ThreadPoolExecutor, as_completed
from html.parser import HTMLParser
from html import unescape
import json
import os
import re
import subprocess
import sys
import urllib.error
import urllib.request

PHP_SOURCE = r"""
define('ABSPATH', __DIR__);
function add_action($name, $callback, $priority=10, $n=1) {}
function add_filter($name, $callback, $priority=10, $n=1) {}
require 'elmercadodeorigen-child/inc/seo-gsc-batch-20-20261012.php';
echo json_encode(elmercado_gsc_batch_20_20261012(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
"""
HOST = "https://www.elmercadodeorigen.com"
NONCE = os.getenv("GITHUB_RUN_ID", "local")


def norm(s):
    return " ".join(unescape(str(s)).split())


class Metadata(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.title_bits = []
        self.in_title = False
        self.meta_descriptions = []
        self.h1s = 0

    def handle_starttag(self, tag, attrs):
        if tag == "title":
            self.in_title = True
        elif tag == "h1":
            self.h1s += 1
        elif tag == "meta":
            a = dict(attrs)
            if (a.get("name") or "").lower() == "description":
                self.meta_descriptions.append(a.get("content") or "")

    def handle_endtag(self, tag):
        if tag == "title":
            self.in_title = False

    def handle_data(self, data):
        if self.in_title:
            self.title_bits.append(data)


def audit(index, path, target):
    suffix = f"?seo_batch1_public_audit={NONCE}-{index}"
    url = HOST + path + suffix
    req = urllib.request.Request(url, headers={
        "User-Agent": "ElMercadoDeOrigen-GSC-ReadOnlyAudit/1.0",
        "Cache-Control": "no-cache",
        "Pragma": "no-cache",
        "Accept-Language": "en-US,en;q=0.9" if path.startswith("/en/") else "es-ES,es;q=0.9",
    })
    try:
        with urllib.request.urlopen(req, timeout=20) as res:
            status = res.status
            content_type = res.headers.get("Content-Type", "")
            # This site includes large inline assets. Read in bounded chunks
            # rather than rejecting valid documents at 1 MB.
            charset = res.headers.get_content_charset() or "utf-8"
            from codecs import getincrementaldecoder
            decoder = getincrementaldecoder(charset)("replace")
            parser = Metadata()
            total_bytes = 0
            while True:
                chunk = res.read(65_536)
                if not chunk:
                    break
                total_bytes += len(chunk)
                if total_bytes > 25_000_000:
                    return {"path": path, "status": status, "title": False, "h1": -1,
                            "desc": False, "desc_count": -1, "error": "response_above_25_mb"}
                parser.feed(decoder.decode(chunk))
            parser.feed(decoder.decode(b"", final=True))
    except Exception as exc:
        return {"path": path, "status": None, "title": False, "h1": -1,
                "desc": False, "desc_count": -1, "error": type(exc).__name__}
    try:
        expected_title = norm(target["title"] + " | El Mercado de Origen")
        expected_description = norm(target["description"])
        actual_title = norm("".join(parser.title_bits))
        desc_matches = (
            len(parser.meta_descriptions) == 1
            and norm(parser.meta_descriptions[0]) == expected_description
        )
        return {"path": path, "status": status,
                "title": actual_title == expected_title,
                "h1": parser.h1s,
                "desc": desc_matches,
                "desc_count": len(parser.meta_descriptions),
                "error": "" if status == 200 and "html" in content_type.lower() else "unexpected_http"}
    except Exception as exc:
        return {"path": path, "status": status, "title": False, "h1": -1,
                "desc": False, "desc_count": -1, "error": type(exc).__name__}


def main():
    extracted = subprocess.run(["php", "-r", PHP_SOURCE],
                               capture_output=True, text=True, check=True)
    targets = json.loads(extracted.stdout)
    if len(targets) != 20 or not all(path.startswith("/") for path in targets):
        raise SystemExit("Unexpected number of batch 1 targets; audit aborted")
    work = list(targets.items())
    results = [None] * len(work)
    with ThreadPoolExecutor(max_workers=3) as pool:
        jobs = {pool.submit(audit, i, path, meta): i
                for i, (path, meta) in enumerate(work)}
        for job in as_completed(jobs):
            results[jobs[job]] = job.result()

    print("GSC_BATCH1_PUBLIC_HTML_AUDIT: status title h1 meta_description desc_tags path")
    for r in results:
        print("{} {} {} {} {} {} {}".format(
            str(r["status"] or "ERR"), "OK" if r["title"] else "FAIL",
            r["h1"], "OK" if r["desc"] else "FAIL", r["desc_count"],
            r["path"], "error=" + r["error"] if r["error"] else ""))
    summary = {
        "total": len(results),
        "http_200": sum(r["status"] == 200 for r in results),
        "title_ok": sum(r["title"] for r in results),
        "h1_one": sum(r["h1"] == 1 for r in results),
        "description_ok": sum(r["desc"] for r in results),
        "english_total": sum(r["path"].startswith("/en/") for r in results),
        "english_description_ok": sum(r["desc"] and r["path"].startswith("/en/") for r in results),
    }
    print("GSC_BATCH1_PUBLIC_SUMMARY " + json.dumps(summary, sort_keys=True))
    if summary["http_200"] == 0:
        print("ERROR: No public page was accessible; not a credible audit.", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
