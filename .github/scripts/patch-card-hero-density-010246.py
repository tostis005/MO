from pathlib import Path

card = Path('elmercadodeorigen-child/inc/product-card-density-final-010162.php')
text = card.read_text()
needle = '''\t\t\tbody.elmercado-child-theme ul.products li.product .button {
\t\t\t\tmargin-top: 0.6rem !important;
\t\t\t}

\t\t\t@media (max-width: 767px) {'''
replacement = '''\t\t\tbody.elmercadodeorigen-child-theme ul.products li.product .button {
\t\t\t\tmargin-top: 0.6rem !important;
\t\t\t}

\t\t\t/* 0.10.246: compactamos la zona de texto real de Woostify/WCFM. */
\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-content {
\t\t\t\tpadding-bottom: 6px !important;
\t\t\t}
\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-content > .woocommerce-loop-product__title {
\t\t\t\tmin-height: 0 !important;
\t\t\t\theight: auto !important;
\t\t\t\tmax-height: none !important;
\t\t\t\tmargin: 0 0 3px !important;
\t\t\t\tpadding: 0 !important;
\t\t\t\tline-height: 1.27 !important;
\t\t\t}
\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-meta,
\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-meta .animated-meta {
\t\t\t\tmin-height: 0 !important;
\t\t\t\theight: auto !important;
\t\t\t\tmargin: 0 !important;
\t\t\t\tpadding: 0 !important;
\t\t\t}
\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-meta .animated-meta > .price,
\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-meta .price {
\t\t\t\tmargin: 0 !important;
\t\t\t\tpadding: 0 !important;
\t\t\t\tline-height: 1.18 !important;
\t\t\t}
\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-wrapper > .wcfmmp_sold_by_container {
\t\t\t\tmin-height: 0 !important;
\t\t\t\theight: auto !important;
\t\t\t\tmargin: 4px 0 0 !important;
\t\t\t\tpadding: 0 !important;
\t\t\t\tline-height: 1.12 !important;
\t\t\t}

\t\t\t@media (max-width: 767px) {'''
# Fix a typo in the leading unchanged rule while keeping the inserted selectors correct.
replacement = replacement.replace('body.elmercadodeorigen-child-theme', 'body.elmercado-child-theme', 1)
if needle not in text:
    raise SystemExit('Product-card insertion target not found')
text = text.replace(needle, replacement, 1)
old_mobile = '''\t\t\t\tbody.elmercado-child-theme ul.products li.product .price {
\t\t\t\t\tpadding-top: 0.35rem !important;
\t\t\t\t}'''
new_mobile = '''\t\t\t\tbody.elmercado-child-theme ul.products li.product .price {
\t\t\t\t\tpadding-top: 0 !important;
\t\t\t\t}
\t\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-content {
\t\t\t\t\tpadding-bottom: 5px !important;
\t\t\t\t}
\t\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-content > .woocommerce-loop-product__title {
\t\t\t\t\tmargin-bottom: 2px !important;
\t\t\t\t\tline-height: 1.23 !important;
\t\t\t\t}
\t\t\t\tbody.elmercado-child-theme ul.products li.product .product-loop-wrapper > .wcfmmp_sold_by_container {
\t\t\t\t\tmargin-top: 3px !important;
\t\t\t\t}'''
if old_mobile not in text:
    raise SystemExit('Product-card mobile target not found')
text = text.replace(old_mobile, new_mobile, 1)
card.write_text(text)

home = Path('elmercadodeorigen-child/mu-plugins/elmercado-home-fresh.php')
text = home.read_text()
old = '''body.home .emo-hero {
\tmin-height: min(650px, calc(100svh - 108px)) !important;
\tpadding-top: clamp(2.15rem, 3vw, 3rem) !important;
\tpadding-bottom: clamp(2.6rem, 4.4vw, 4.25rem) !important;
}
body.home .emo-hero__grid { gap: clamp(2.5rem, 5vw, 5rem) !important; }
body.home .emo-hero__copy > p { font-size: clamp(1.07rem, 1.48vw, 1.27rem) !important; }
body.home .emo-hero__proof { margin-top: clamp(1.8rem, 3vw, 2.65rem) !important; }'''
new = '''body.home .emo-hero {
\tmin-height: min(600px, calc(100svh - 108px)) !important;
\tpadding-top: clamp(1.75rem, 2.35vw, 2.35rem) !important;
\tpadding-bottom: clamp(2rem, 3vw, 3rem) !important;
}
body.home .emo-hero__grid { gap: clamp(2rem, 4vw, 4rem) !important; }
body.home .emo-hero h1 {
\tfont-size: clamp(3.75rem, 5.45vw, 4.9rem) !important;
\tline-height: .94 !important;
}
body.home .emo-hero__copy > p {
\tfont-size: clamp(1rem, 1.25vw, 1.12rem) !important;
\tline-height: 1.5 !important;
\tmargin-top: .75rem !important;
\tmargin-bottom: 1rem !important;
}
body.home .emo-hero__proof {
\tmargin-top: clamp(1.2rem, 2vw, 1.75rem) !important;
\tpadding-top: .8rem !important;
\tgap: .65rem !important;
}'''
if old not in text:
    raise SystemExit('Home base density target not found')
text = text.replace(old, new, 1)
old = '''body.home .emo-hero__visual--vendors {
\tgrid-template-columns: repeat(12, minmax(0, 1fr)) !important;'''
new = '''body.home .emo-hero__visual--vendors {
\ttransform: translateY(-34px);
\tgrid-template-columns: repeat(12, minmax(0, 1fr)) !important;'''
if old not in text:
    raise SystemExit('Home visual target not found')
text = text.replace(old, new, 1)
old = '''\tbody.home .emo-hero {
\t\tmin-height: 0 !important;
\t\tpadding-top: 2.15rem !important;
\t\tpadding-bottom: 2.8rem !important;
\t}
\tbody.home .emo-hero__visual--vendors {'''
new = '''\tbody.home .emo-hero {
\t\tmin-height: 0 !important;
\t\tpadding-top: 1.6rem !important;
\t\tpadding-bottom: 2rem !important;
\t}
\tbody.home .emo-hero__grid {
\t\tgap: 1.2rem !important;
\t}
\tbody.home .emo-hero h1 {
\t\tfont-size: 2.55rem !important;
\t\tline-height: .96 !important;
\t}
\tbody.home .emo-hero__copy > p {
\t\tfont-size: .91rem !important;
\t\tline-height: 1.48 !important;
\t\tmargin-top: .6rem !important;
\t\tmargin-bottom: .75rem !important;
\t}
\tbody.home .emo-hero__proof {
\t\tmargin-top: 1rem !important;
\t\tpadding-top: .7rem !important;
\t\tgap: .55rem !important;
\t}
\tbody.home .emo-hero__visual--vendors {
\t\ttransform: translateY(-6px);'''
if old not in text:
    raise SystemExit('Home mobile density target not found')
text = text.replace(old, new, 1)
home.write_text(text)
