#!/usr/bin/env python3
from pathlib import Path
import shutil
import sys

if len(sys.argv) != 2:
    raise SystemExit('usage: mdo-production-fix-english-shop-observers.py <themes-dir>')

themes = Path(sys.argv[1]).resolve()
if not themes.is_dir():
    raise SystemExit(f'missing themes dir: {themes}')


def locate(name: str, required: bool = True):
    matches = [p for p in themes.rglob(name) if p.is_file() and 'elmercadodeorigen-child' in str(p)]
    if not matches and not required:
        return None
    if len(matches) != 1:
        raise SystemExit(f'expected exactly one active-theme {name}, found {len(matches)}: {matches}')
    return matches[0]


def backup(path: Path):
    dst = path.with_name(path.name + '.pre-mdo-en-shop-observer-fix-20260815')
    if not dst.exists():
        shutil.copy2(path, dst)


def replace_once(text: str, old: str, new: str, label: str):
    count = text.count(old)
    if count == 0 and new in text:
        print(f'{label}: already patched')
        return text
    if count != 1:
        raise SystemExit(f'{label}: expected 1 occurrence, found {count}')
    print(f'{label}: patched')
    return text.replace(old, new, 1)

# Production does not currently have catalog-query-parity-010224.php. If it is
# added later, make it language-aware and remove its competing count observer.
query = locate('catalog-query-parity-010224.php', required=False)
if query:
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
        "\t\t\t\t// The final catalogue loader owns subsequent count updates. No competing observer.",
        'query-parity count observer',
    )
    query.write_text(text, encoding='utf-8')
else:
    print('query-parity: not present in production (expected)')

# The mobile polish code already has deterministic immediate/rAF/timed/resize
# passes. Observing the whole body causes it to react to translations and its
# own DOM changes, so remove that global observer.
polish = locate('catalog-mobile-controls-polish-010237.php')
backup(polish)
text = polish.read_text(encoding='utf-8')
text = replace_once(
    text,
    "\t\t\tnew MutationObserver(() => requestAnimationFrame(polish)).observe(document.body,{childList:true,subtree:true});",
    "\t\t\t// No global body MutationObserver: deterministic initial/timed/resize passes avoid translation feedback loops.",
    'mobile polish body observer',
)
polish.write_text(text, encoding='utf-8')

# The final loader is the single owner of the result count. It writes language-
# aware UI directly and updates the count at startup and after a batch append;
# it must not observe that same text node forever while TranslatePress observes
# DOM changes too.
scroll = locate('catalog-filter-scroll-final-010234.php')
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
    'final loader language-aware UI',
)
text = replace_once(
    text,
    "\t\t\tstate.innerHTML='<span class=\"emo-catalog-spinner\" aria-hidden=\"true\"></span><span class=\"emo-catalog-load-message\"></span><button type=\"button\" class=\"emo-catalog-load-button\" hidden>Cargar más productos</button>';",
    "\t\t\tstate.innerHTML='<span class=\"emo-catalog-spinner\" aria-hidden=\"true\"></span><span class=\"emo-catalog-load-message\"></span><button type=\"button\" class=\"emo-catalog-load-button\" hidden></button>';",
    'final loader button markup',
)
button_marker = "\t\t\tconst button=state.querySelector('.emo-catalog-load-button');"
assignment = "\t\t\tbutton.textContent=uiCopy.loadMore;"
if assignment not in text:
    text = replace_once(text, button_marker, button_marker + "\n" + assignment, 'final loader button copy')
else:
    # Normalize accidental duplicates from repeated maintenance runs.
    lines = text.splitlines()
    seen = False
    normalized = []
    for line in lines:
        if line == assignment:
            if seen:
                continue
            seen = True
        normalized.append(line)
    text = "\n".join(normalized) + ("\n" if text.endswith("\n") else "")
    print('final loader button copy: already patched')
text = replace_once(
    text,
    "\t\t\texactCountNodes().forEach(node=>new MutationObserver(lockCounts).observe(node,{childList:true,characterData:true,subtree:true}));",
    "\t\t\t// No permanent count observer: lockCounts() runs at startup and after each appended batch.",
    'final loader count observer',
)
text = text.replace("setState('loading','Cargando más productos…')", "setState('loading',uiCopy.loading)")
text = text.replace("setState('failure','No se ha podido continuar la carga automática.')", "setState('failure',uiCopy.failure)")
text = text.replace("setState('failure','Pulsa para cargar más productos.')", "setState('failure',uiCopy.retry)")
scroll.write_text(text, encoding='utf-8')

print('patched production files:')
for p in [x for x in (query, polish, scroll) if x]:
    print(p)
