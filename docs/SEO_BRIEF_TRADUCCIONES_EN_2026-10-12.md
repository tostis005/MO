# Editorial sprint: repair English duck and foie category hubs

**Observed 12 October 2026** on the public site, before deployment of PR #237.

## Priority and scope

1. **Duck hub**: https://www.elmercadodeorigen.com/en/category/carnes/pato/
2. **Foie/pâtés hub**: https://www.elmercadodeorigen.com/en/category/foie-pates-untables/
3. English Meat category: https://www.elmercadodeorigen.com/en/category/meat/

The first two public EN URLs currently show Spanish headings and/or descriptions; the Duck and Foie category listings are primarily Spanish. On the Meat category the main category copy is English but duck/foie article cards remain Spanish. This is an editorial/Falang problem, not fixed by translating theme button labels.

## Ready-to-review replacement English category copy

### 1. Duck category (`pato`)

**Suggested displayed heading:** Duck: Cuts, Cooking and Serving

**Suggested introduction:**

> Explore guides to duck meat and its main cuts, from duck breast (magret) and confit to foie gras and cured duck ham. Learn how to choose and prepare each cut, handle it safely, cook it to the right temperature and serve it with suitable sauces, sides and pairings. Our detailed guides cover storage, freezing, reheating, nutrition and recipes for different occasions.

**Editorial notes:** Keep existing navigational sections (duck meat and cuts, magret, foie, confit, duck ham, sauces and pairing) but translate their headings and each editorial anchor label. Check every outgoing link resolves to the intended English article. Translate the term title and description *inside Falang* rather than patching the theme.

### 2. Foie, pâtés and spreads category (`foie-pates-untables`)

**Suggested displayed heading:** Foie Gras, Pâtés, Mousses and Spreads

**Suggested introduction:**

> Understand the differences between foie gras, pâté, mousse, parfait, bloc, mi-cuit and rillettes. Explore how their ingredients, preparation, texture and storage requirements differ, what product labels mean and which format suits different ways of serving. This section covers both duck products and related pâtés or spreads made with other ingredients.

**Editorial notes:** Preserve the distinction between foie gras and other pâtés/mousses; do not imply they are nutritionally or legally interchangeable. Translate related article titles/excerpts/bodies before presenting the hub as entirely English. Review original links to product and Duck category; no new fabricated product promise.

## Immediate content worklist

| Priority | English public section | Observed issue | Required edit source |
| --- | --- | --- | --- |
| P0 | Duck hub header and index | Heading, description and introductory links are Spanish | WordPress/Falang translation for category term |
| P0 | Foie hub header and index | Heading and descriptive copy Spanish | WordPress/Falang translation for category term |
| P0 | Duck cards in Meat listing | Spanish article titles/excerpts inside EN category | WordPress/Falang translated post fields |
| P1 | Duck and foie individual guides | Original Spanish posts served via /en/ | Translate actual body, titles, SEO fields; verify publication flags |
| P1 | Category links and UI | UI labels are mixed in EN | Theme PR #237 already addresses reading time, categories count, product-block language |

## Publication gates

- Audit Falang's actual EN publish flags for terms and posts; preserve the original ES content.
- Do not change permalinks, canonical tags, hreflang, or remove listings blindly. Some URLs have organic traffic.
- Check every translated heading/title against its article body; no misleading English snippet with Spanish-only landing.
- Render-check EN/ES category pages in staging. No merge of PR #237 until staging is accessible and its output is validated.
- Compare URL impressions, clicks and query targeting in GSC once the page×query connection/export is available.

**Tracking:** [#239](https://github.com/tostis005/MO/issues/239), [master #238](https://github.com/tostis005/MO/issues/238); branch is not live.