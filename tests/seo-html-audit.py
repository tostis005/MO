#!/usr/bin/env python3
"""Auditoría de HTML publicado para cinco artículos GSC (estándar Python).

Uso:
  python3 tests/seo-html-audit.py --self-test
  python3 tests/seo-html-audit.py --base https://dev.elmercadodeorigen.com

Sin escribir en el sitio. La auditoría sirve para validar HTML renderizado después
de desplegar la rama a staging; no prueba los anuncios personalizados por IP.
"""
import argparse
import base64
import html
import os
from html.parser import HTMLParser
import re
from pathlib import Path
from urllib.parse import urlparse, urlencode, urlsplit, urlunsplit, parse_qsl
from uuid import uuid4
from urllib.request import urlopen, Request

SAMPLES = (
    "/en/why-cut-potatoes-turn-black-oxidation-how-to-prevent-it/",
    "/en/white-foam-when-cooking-meat-what-it-is-why-it-appears/",
    "/en/cauliflower-black-brown-spots-oxidation-or-spoilage/",
    "/cuanta-carne-calcular-por-persona-corte-receta/",
    "/aguja-ternera-que-corte-es-como-cocinarla/",
)
SEO_MODULE = Path(__file__).resolve().parents[1] / "elmercadodeorigen-child/inc/seo-gsc-batch-20-20261012.php"


def manifest():
    src = SEO_MODULE.read_text(encoding="utf-8")
    patterns = re.compile(
        r"'(?P<path>/[^']+/)'\s*=>\s*array\(\s*"
        r"'title'\s*=>\s*'(?P<title>[^']+)'\s*,\s*"
        r"'description'\s*=>\s*'(?P<description>[^']+)'",
        re.MULTILINE,
    )
    return {m["path"]: (m["title"] + " | El Mercado de Origen", m["description"])
            for m in patterns.finditer(src)}


def tidy(value):
    return " ".join(html.unescape(value or "").split()).strip()


class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.lang = ""
        self.titles = []
        self.metas = []
        self.canonicals = []
        self.hreflang = []
        self.h1s = []
        self._title = False
        self._h1 = False

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "html":
            self.lang = a.get("lang", "")
        elif tag == "title":
            self._title = True
            self.titles.append("")
        elif tag == "h1":
            self._h1 = True
            self.h1s.append("")
        elif tag == "meta" and a.get("name", "").lower() == "description":
            self.metas.append(a.get("content", ""))
        elif tag == "link":
            rel = set((a.get("rel") or "").lower().split())
            if "canonical" in rel:
                self.canonicals.append(a.get("href", ""))
            if "alternate" in rel and a.get("hreflang"):
                self.hreflang.append((a["hreflang"], a.get("href", "")))

    def handle_endtag(self, tag):
        if tag == "title":
            self._title = False
        elif tag == "h1":
            self._h1 = False

    def handle_data(self, data):
        if self._title:
            self.titles[-1] += data
        if self._h1:
            self.h1s[-1] += data


def inspect(page, expected, html_source):
    d = Document()
    d.feed(html_source)
    errs, warnings = [], []
    en = page.startswith("/en/")
    if len(d.titles) != 1:
        errs.append(f"{len(d.titles)} etiquetas <title> (esperada: 1)")
    elif tidy(d.titles[0]) != tidy(expected[0]):
        errs.append(f"title inesperado: {tidy(d.titles[0])[:110]}")
    if len(d.metas) != 1:
        errs.append(f"{len(d.metas)} meta descriptions (esperada: 1)")
    elif tidy(d.metas[0]) != tidy(expected[1]):
        errs.append("meta description diferente del plan GSC")
    if len(d.h1s) != 1:
        errs.append(f"{len(d.h1s)} encabezados H1 (esperado: 1)")
    if d.lang:
        if en and not d.lang.lower().startswith("en"):
            errs.append(f"html lang={d.lang}, se esperaba inglés")
        if not en and not d.lang.lower().startswith("es"):
            errs.append(f"html lang={d.lang}, se esperaba español")
    else:
        warnings.append("html lang ausente")
    if len(d.canonicals) > 1:
        errs.append("varias URLs canonical")
    elif not d.canonicals:
        warnings.append("canonical no encontrada; investigar plugin SEO")
    elif page != (urlparse(d.canonicals[0]).path.rstrip("/") + "/"):
        errs.append("canonical apunta a otra ruta (riesgo de desindexación EN): " + d.canonicals[0])
    if not d.hreflang:
        warnings.append("sin alternates hreflang; revisar Falang")
    else:
        for lang, href in d.hreflang:
            if not href.startswith("https://") and not href.startswith("http://"):
                warnings.append("href hreflang no absoluto para " + lang)
    return errs, warnings


def build_request(url, username="", password=""):
    """HTTP basic auth only for HTTPS staging; never embed credentials in URL."""
    headers = {
        "User-Agent": "ELMERCADO-SEO-QA/1.0",
        "Cache-Control": "no-cache",
        "Pragma": "no-cache",
    }
    if username and password:
        if urlparse(url).scheme != "https":
            raise ValueError("HTTPS required for staging Basic auth")
        encoded = base64.b64encode(f"{username}:{password}".encode("utf-8")).decode("ascii")
        headers["Authorization"] = "Basic " + encoded
    return Request(url, headers=headers)


def bust_cache(url, token):
    """Append a harmless query parameter without changing the article path."""
    parts = urlsplit(url)
    query = list(parse_qsl(parts.query, keep_blank_values=True))
    query.append(("_seo_html_audit", token))
    return urlunsplit((parts.scheme, parts.netloc, parts.path, urlencode(query), parts.fragment))


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--base", default="https://dev.elmercadodeorigen.com")
    p.add_argument("--self-test", action="store_true")
    p.add_argument("--timeout", type=int, default=20)
    p.add_argument("--cache-bust", action="store_true", help="Avoid stale HTML on deployed pages")
    args = p.parse_args()
    expected = manifest()
    if len(expected) != 20:
        raise SystemExit(f"ERROR: se esperaban 20 entradas, encontradas {len(expected)}")
    if any(url not in expected for url in SAMPLES):
        raise SystemExit("ERROR: faltan ejemplos requeridos")
    if args.self_test:
        for page in SAMPLES:
            title, desc = expected[page]
            lang = "en-US" if page.startswith("/en/") else "es-ES"
            fake = (f'<html lang="{lang}"><head><title>{html.escape(title)}</title>'
                    f'<meta name="description" content="{html.escape(desc, quote=True)}">'
                    f'<link rel="canonical" href="https://example.com{page}"></head>'
                    '<body><h1>Test heading</h1></body></html>')
            errs, _ = inspect(page, expected[page], fake)
            if errs:
                raise SystemExit(f"ERROR: auditoría no pasa fixture {page}: {errs}")
            # A translation pointing at the Spanish canonical must fail, not warn.
            invalid = fake.replace(f'https://example.com{page}', 'https://example.com/canonical-equivocada/')
            bad, _ = inspect(page, expected[page], invalid)
            if not any("canonical" in e for e in bad):
                raise SystemExit(f"ERROR: no se detecta canonical incorrecta: {page}")
        header = build_request("https://dev.example.test/post/", "demo", "secret").get_header("Authorization")
        if header != "Basic ZGVtbzpzZWNyZXQ=":
            raise SystemExit("ERROR: autenticación básica de staging mal codificada")
        try:
            build_request("http://dev.example.test/post/", "demo", "secret")
            raise SystemExit("ERROR: credenciales en HTTP sin cifrar permitidas")
        except ValueError:
            pass
        tagged = bust_cache("https://example.com/en/path/?x=1", "smoke")
        if tagged != "https://example.com/en/path/?x=1&_seo_html_audit=smoke":
            raise SystemExit("ERROR: cache bust altered article path")
        print("OK: manifiesto, canonical EN y parser HTML, 20 entradas / 5 páginas de muestra")
        return

    failed = 0
    for page in SAMPLES:
        full_url = args.base.rstrip("/") + page
        if args.cache_bust:
            full_url = bust_cache(full_url, uuid4().hex)
        try:
            req = build_request(
                full_url,
                os.getenv("SEO_STAGING_BASIC_USER", ""),
                os.getenv("SEO_STAGING_BASIC_PASSWORD", ""),
            )
            with urlopen(req, timeout=args.timeout) as resp:
                if resp.status != 200:
                    raise ValueError(f"HTTP {resp.status}")
                source = resp.read(3_000_000).decode("utf-8", errors="replace")
            errs, warns = inspect(page, expected[page], source)
        except Exception as exc:
            errs, warns = [f"no accesible: {exc}"], []
        print(("FAIL" if errs else "PASS") + ": " + full_url)
        for e in errs:
            print("  ERROR: " + e)
        for w in warns:
            print("  AVISO: " + w)
        failed += int(bool(errs))
    print(f"Resultado: {len(SAMPLES)-failed}/{len(SAMPLES)} páginas OK")
    if failed:
        raise SystemExit(1)


if __name__ == "__main__":
    main()
