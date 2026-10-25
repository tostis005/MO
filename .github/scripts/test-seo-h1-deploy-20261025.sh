#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TEMP="$(mktemp -d)"
trap 'rm -rf "$TEMP"' EXIT
mkdir -p "$TEMP/live" "$TEMP/stage"
cp "$ROOT/elmercadodeorigen-child/functions.php" "$TEMP/live/functions.php"
cp "$ROOT/elmercadodeorigen-child/single.php" "$TEMP/live/single.php"
# Emulate the state where PR #378 is merged but the live WordPress theme
# did not receive its two local theme changes.
python3 - "$TEMP/live/functions.php" "$TEMP/live/single.php" <<'PY'
import sys
from pathlib import Path
functions=Path(sys.argv[1])
single=Path(sys.argv[2])
f=functions.read_text(encoding="utf-8")
line="\t'inc/seo-final-heading-20261025.php',\n"
assert f.count(line)==1
functions.write_text(f.replace(line,''),encoding="utf-8")
s=single.read_text(encoding="utf-8")
start=s.index("if ( function_exists( 'elmercado_blog_final_h1_guard_20261025' ) )")
# Drop full 3-line guard, preserve all other existing changes.
line_begin=s.rfind('\n',0,start)+1
line_end=s.index('\n',s.index('}',start))+1
s=s[:line_begin]+s[line_end:]
assert 'elmercado_blog_final_h1_guard_20261025' not in s
single.write_text(s,encoding="utf-8")
PY
php "$ROOT/.github/scripts/patch-seo-final-h1-production-20261025.php" "$TEMP/live" "$TEMP/stage"
php -l "$TEMP/stage/functions.php"
php -l "$TEMP/stage/single.php"
test "$(grep -Fc "'inc/seo-final-heading-20261025.php'" "$TEMP/stage/functions.php")" = 1
test "$(grep -Fc 'elmercado_blog_final_h1_guard_20261025' "$TEMP/stage/single.php")" = 2
# The patcher must also be idempotent on an already-updated theme.
mkdir -p "$TEMP/updated" "$TEMP/stage2"
cp "$TEMP/stage/functions.php" "$TEMP/updated/functions.php"
cp "$TEMP/stage/single.php" "$TEMP/updated/single.php"
php "$ROOT/.github/scripts/patch-seo-final-h1-production-20261025.php" "$TEMP/updated" "$TEMP/stage2"
cmp "$TEMP/stage/functions.php" "$TEMP/stage2/functions.php"
cmp "$TEMP/stage/single.php" "$TEMP/stage2/single.php"
echo 'PASS H1 deploy patcher: scoped, valid, idempotent'
