#!/usr/bin/env python3
from pathlib import Path
import shutil
import sys

if len(sys.argv) != 2:
    raise SystemExit('usage: mdo-staging-fix-english-shop-observers.py <themes-dir>')

themes = Path(sys.argv[1]).resolve()
if not themes.is_dir():
    raise SystemExit(f'missing themes dir: {themes}')


def one(name: str) -> Path:
    matches = [p for p in themes.rglob(name) if p.is_file()]
    if len(matches) != 1:
        raise SystemExit(f'expected exactly one {name}, found {len(matches)}: {matches}')
    return matches[0]


def backup(path: Path) -> None:
    dst = path.with_name(path.name + '.pre-mdo-en-shop-observer-fix-20260815')
    if not dst.exists():
        shutil.copy2(path, dst)


def replace_once(text: str, old: str, new: str, label: str) -> str:
    count = text.count(old)
    if count == 0 and new in text:
        print(f'{label}: already patched')
        return text
    if count != 1:
        raise SystemExit(f'{label}: expected 1 occurrence, found {count}')
    print(f'{label}: patched')
    return text.replace(old, new, 1)

# 1) Historical query-parity code used a Spanish-only counter plus a permanent
# MutationObserver. On /en/ it fought with the final loader's translated count.
query = one('catalog-query-parity-010224.php')
backup(query)
text = query.read_text(encoding='utf-8')
text = replace_once(
    text,
    "\t\t\tconst label = `${total.toLocaleString('es-ES')} ${total === 1 ? 'resultado' : 'resultados'}`;",
    "\t\t\tconst htmlLang = (document.documentElement.lang || '').toLowerCase();\n"
    "\t\t\tconst isEnglish = htmlLang.startsWith('en');\n"
    "\t\t\tconst label = isEnglish\n"
    "\t\t\t\t? `${total.toLocaleString('en-US')} ${total === 1 ? 'result' : 'results'}`\n"
    "\t\t\t\t: `${total.toLocaleString('es-ES')} ${total === 1 ? 'resultado' : 'resultados'}`;",
    'query-parity language-aware label',
)
text = replace_once(
    text,
    "\t\t\t\tnew MutationObserver(normalize).observe(node, { childList: true, characterData: true, subtree: true });",
    "\t\t\t\t// The final catalog loader owns subsequent count updates. Do not observe\n"
    "\t\t\t\t// this node here: competing count observers can form a translation loop.",
    'query-parity count observer',
)
query.write_text(text, encoding='utf-8')

# 2) The mobile polish pass already runs immediately, on rAF, timed fallbacks,
# pageshow and resize. Observing the entire body made it react to the count loop
# and to its own DOM normalization work.
polish = one('catalog-mobile-controls-polish-010237.php')
backup(polish)
text = polish.read_text(encoding='utf-8')
text = replace_once(
    text,
    "\t\t\tnew MutationObserver(() => requestAnimationFrame(polish)).observe(document.body,{childList:true,subtree:true});",
    "\t\t\t// No global body MutationObserver: the deterministic initial/timed/resize\n"
    "\t\t\t// passes above are sufficient and avoid self-triggering DOM work.",
    'mobile polish body observer',
)
polish.write_text(text, encoding='utf-8')

# 3) The final loader is the single owner of the result count. It updates once
# at startup and after a batch append; a permanent per-node observer is redundant.
scroll = one('catalog-filter-scroll-final-010234.php')
backup(scroll)
text = scroll.read_text(encoding='utf-8')
text = replace_once(
    text,
    "\t\t\tconst exactLabel = <?php echo wp_json_encode( $label ); ?>;",
    "\t\t\tconst sourceLabel = <?php echo wp_json_encode( $label ); ?>;\n"
    "\t\t\tconst htmlLang = (document.documentElement.lang || '').toLowerCase();\n"
    "\t\t\tconst isEnglish = htmlLang.startsWith('en');\n"
    "\t\t\tconst exactLabel = isEnglish\n"
    "\t\t\t\t? `${exactTotal.toLocaleString('en-US')} ${exactTotal === 1 ? 'result' : 'results'}`\n"
    "\t\t\t\t: sourceLabel;\n"
    "\t\t\tconst uiCopy = isEnglish\n"
    "\t\t\t\t? {loadMore:'Load more products',loading:'Loading more products…',retry:'Click to load more products.',failure:'Automatic loading could not continue.'}\n"
    "\t\t\t\t: {loadMore:'Cargar más productos',loading:'Cargando más productos…',retry:'Pulsa para cargar más productos.',failure:'No se ha podido continuar la carga automática.'};",
    'final loader English copy',
)
text = replace_once(
    text,
    "\t\t\tstate.innerHTML='<span class=\"emo-catalog-spinner\" aria-hidden=\"true\"></span><span class=\"emo-catalog-load-message\"></span><button type=\"button\" class=\"emo-catalog-load-button\" hidden>Cargar más productos</button>';",
    "\t\t\tstate.innerHTML='<span class=\"emo-catalog-spinner\" aria-hidden=\"true\"></span><span class=\"emo-catalog-load-message\"></span><button type=\"button\" class=\"emo-catalog-load-button\" hidden></button>';",
    'final loader button markup',
)
text = replace_once(
    text,
    "\t\t\tconst button=state.querySelector('.emo-catalog-load-button');",
    "\t\t\tconst button=state.querySelector('.emo-catalog-load-button');\n\t\t\tbutton.textContent=uiCopy.loadMore;",
    'final loader button copy',
)
text = replace_once(
    text,
    "\t\t\texactCountNodes().forEach(node=>new MutationObserver(lockCounts).observe(node,{childList:true,characterData:true,subtree:true}));",
    "\t\t\t// No permanent count observer. lockCounts() is called at startup and after\n"
    "\t\t\t// each appended batch, making the count deterministic without feedback loops.",
    'final loader count observer',
)
text = text.replace("setState('loading','Cargando más productos…')", "setState('loading',uiCopy.loading)")
text = text.replace("setState('failure','No se ha podido continuar la carga automática.')", "setState('failure',uiCopy.failure)")
text = text.replace("setState('failure','Pulsa para cargar más productos.')", "setState('failure',uiCopy.retry)")
scroll.write_text(text, encoding='utf-8')

print('patched files:')
for p in (query, polish, scroll):
    print(p)
