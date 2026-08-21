#!/usr/bin/env python3
# Workflow activation 2026-08-21
from html.parser import HTMLParser
from pathlib import Path
import json
import sys

class ProductParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.count = 0
        self.in_first = False
        self.first_href = ""

    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        classes = data.get("class", "").split()
        if tag == "li" and "product" in classes:
            self.count += 1
            if self.count == 1:
                self.in_first = True
        elif self.in_first and tag == "a" and not self.first_href and data.get("href"):
            self.first_href = data["href"]

    def handle_endtag(self, tag):
        if tag == "li" and self.in_first:
            self.in_first = False

if len(sys.argv) != 2:
    raise SystemExit("usage: check-producer-store-html-20260821.py FILE")
parser = ProductParser()
parser.feed(Path(sys.argv[1]).read_text(errors="ignore"))
print(json.dumps({"count": parser.count, "first_href": parser.first_href}, ensure_ascii=False))
