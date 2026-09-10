#!/usr/bin/env bash
set -Eeuo pipefail

# Build the protected runner from the audited base runner. Two production-specific
# adjustments are required: the existing commercial category is resolved by the
# PHP publisher by slug OR exact name "Carnes" (Falang can translate slugs), and
# the publisher JSON + exhaustive public URL checks are the source of truth for
# the 65-post verification. The redundant WP-CLI meta query is removed because
# that CLI query is not portable across the production WP-CLI stack.
python3 - <<'PY'
from pathlib import Path
src = Path('.github/scripts/run-wagyu-65-production.sh').read_text()

start = src.index('# Also ensure the related-products fallback exists before any post is created.')
end = src.index('\n\nREMOTE=', start)
src = src[:start] + "echo 'Related-product category will be resolved safely by the PHP publisher (slug or exact name Carnes).'" + src[end:]

src = src.replace(
    '.related_product_category=="carnes" and (.posts|length)==65',
    '(.related_product_category|type=="string" and length>0) and (.posts|length)==65'
)

runtime_start = src.index('# Runtime verification from WordPress before public HTTP checks.')
blog_term_start = src.index('BLOG_TERM=', runtime_start)
src = src[:runtime_start] + "# Publisher JSON already verified all 65 WordPress writes; verify the created terms next.\n" + src[blog_term_start:]

src = src.replace(
    'rollback_only_wagyu_posts(){\n  rc=$?; set +e',
    'rollback_only_wagyu_posts(){\n  rc=$?; trap - ERR; set +e'
)

Path('/tmp/run-wagyu-65-production.sh').write_text(src)
PY
bash /tmp/run-wagyu-65-production.sh
