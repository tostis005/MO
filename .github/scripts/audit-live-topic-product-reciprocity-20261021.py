#!/usr/bin/env python3
"""Read-only live checks for article/product/category reciprocal topic clusters."""
import re,urllib.request,time,html,json,concurrent.futures
from html.parser import HTMLParser
BASE="https://www.elmercadodeorigen.com"
checks=[
 ("coffee_article_es","/como-elegir-mejor-cafe-en-grano-para-casa/",'data-emdo-editorial-cluster="1"'),
 ("coffee_article_en","/en/how-to-choose-the-best-whole-bean-coffee-for-home/",'data-emdo-editorial-cluster="1"'),
 ("mango_article_es","/tipos-de-mango-variedades-diferencias-cual-elegir/",'data-emdo-editorial-cluster="1"'),
 ("mango_article_en","/en/types-of-mango-varieties-differences-which-to-choose/",'data-emdo-editorial-cluster="1"'),
 ("avocado_article_es","/tipos-de-aguacate-hass-bacon-fuerte-diferencias/",'data-emdo-editorial-cluster="1"'),
 ("avocado_article_en","/en/avocado-varieties-hass-bacon-fuerte-differences/",'data-emdo-editorial-cluster="1"'),
 ("citrus_article_es","/tipos-naranjas-variedades-sabor-cual-elegir/",'data-emdo-editorial-cluster="1"'),
 ("citrus_article_en","/en/types-of-oranges-varieties-flavour-which-to-choose/",'data-emdo-editorial-cluster="1"'),
 ("chocolate_article_es","/cremas-de-cacao-como-elegir-ingredientes-diferencias/",'data-emdo-editorial-cluster="1"'),
 ("chocolate_article_en","/en/chocolate-spreads-how-to-choose-ingredients-and-differences/",'data-emdo-editorial-cluster="1"'),
 ("coffee_product","/producto/cafe-ikore-kofi-geisha/",'data-emdo-editorial-product-guides="1"'),
 ("mango_product","/producto/pack-chirimoyas-y-mangos-espanoles/",'data-emdo-editorial-product-guides="1"'),
 ("avocado_product","/producto/mix-aguacates-piel-lisa-y-guayabas/",'data-emdo-editorial-product-guides="1"'),
 ("citrus_product","/producto/mix-mandarinas-y-naranjas/",'data-emdo-editorial-product-guides="1"'),
 ("chocolate_cream_product","/producto/crema-bioft-de-chocolate-negro-con-45-de-avellanas-y-cafe-arabica-200-g/",'data-emdo-editorial-product-guides="1"'),
 ("coffee_category","/categoria-producto/cafe-de-especialidad/",'data-emdo-editorial-category-guides="1"'),
 ("avocado_category","/categoria-producto/frutas/aguacates/",'data-emdo-editorial-category-guides="1"'),
 ("tropical_category","/categoria-producto/frutas/frutas-tropicales/",'data-emdo-editorial-category-guides="1"'),
 ("cream_category","/categoria-producto/chocolates-artesanos/cremas-de-cacao/",'data-emdo-editorial-category-guides="1"'),
 ("coffee_blog_category","/category/cafe/",'emdo-editorial-blog-hub-link'),
 ("mango_blog_category","/category/frutas/mangos/",'emdo-editorial-blog-hub-link'),
 ("avocado_blog_category","/category/frutas/aguacates/",'emdo-editorial-blog-hub-link'),
 ("empty_orange_category","/categoria-producto/naranjas/",'noindex'),
]
class H(HTMLParser):
    def __init__(self):
        super().__init__();self.h1=0;self.meta=[];self.links=[]
    def handle_starttag(self,t,attrs):
        d=dict(attrs)
        if t=="h1":self.h1+=1
        if t=="meta":self.meta.append(d)
        if t=="link":self.links.append(d)
def test(row):
    label,path,marker=row;url=BASE+path
    for attempt in range(2):
        try:
            req=urllib.request.Request(url,headers={"User-Agent":"Mozilla/5.0 EMDO-Clusters-live-QA"})
            with urllib.request.urlopen(req,timeout=48) as res:body=res.read(5500000).decode("utf-8","replace");code=res.status
            h=H();h.feed(body)
            main=(code==200 and marker in body)
            seo={}
            if "_article_" in label:
                alts={x.get('hreflang',''):x.get('href','') for x in h.links if x.get('hreflang')}
                metadata={x.get('property') or x.get('name'):x.get('content') for x in h.meta}
                main=main and h.h1==1 and bool(metadata.get('description')) and bool(metadata.get('og:image')) and all(k in alts for k in ('es-ES','en','x-default'))
                seo={'h1':h.h1,'hreflang':alts.get('es-ES'),'og_image':bool(metadata.get('og:image'))}
            if "_product" in label and label!="chocolate_cream_product":
                main=main and bool(re.search(r'/como-elegir|/tipos-de-|/como-saber|/naranjas-de-',body))
            return {'label':label,'url':url,'ok':bool(main),'status':code,'marker':marker in body,**seo}
        except Exception as ex:
            error=str(ex)
            if attempt==0:time.sleep(2)
    return {'label':label,'url':url,'ok':False,'error':error}
def main():
    with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
        results=list(pool.map(test,checks))
    for row in results:print('EMDO_RECIPROCAL_LIVE',json.dumps(row,ensure_ascii=False),flush=True)
    bad=[r for r in results if not r.get('ok')]
    print('EMDO_RECIPROCAL_SUMMARY',json.dumps({'checked':len(results),'ok':len(results)-len(bad),'failed':bad},ensure_ascii=False))
    if bad:raise SystemExit(1)
if __name__=="__main__":main()
