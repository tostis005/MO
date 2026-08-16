import re
import sys
from pathlib import Path

path = Path(sys.argv[1])
text = path.read_text(encoding="utf-8")
pattern = r"/\* Keep the Home HTML fresh across devices\. \*/\s*add_action\(\s*'template_redirect',.*?\n\);\n"
replacement = "/* Production stability: keep the theme native Home cache enabled. */\n"
updated, count = re.subn(pattern, replacement, text, count=1, flags=re.S)
if count != 1:
    raise SystemExit(f"Expected one Home cache-bypass block, found {count}")
path.write_text(updated, encoding="utf-8")
