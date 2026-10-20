#!/usr/bin/env python3
"""Read-only audit: 24 approved bilingual articles, historical topics, and all 48 public URLs."""
import concurrent.futures, difflib, html, json, pathlib, re, sys, time, urllib.request, urllib.error, collections
from html.parser import HTMLParser
from urllib.parse import urlparse
ROOT=pathlib.Path(".")
PREFIX=".github/data/editorial-nuevos-productores-20261020-"
SITE="https://www.elmercadodeorigen.com"
class HTMLAudit(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.meta=[]; self.links=[]; self.images=[]; self.h1=[]; self.h2=[]; self.lang=""
        self.capture=None; self.buf=[]; self.title=""; self.jsonlds=0; self.ogimage=""
    def handle_starttag(self,t,attrs):
        d=dict(attrs)
        if t=="html":self.lang=d.get("lang","")
        if t=="meta":self.meta.append(d)
        if t=="link":self.links.append(d)
        if t=="img":self.images.append(d)
        if t=="script" and "ld+json" in d.get("type",""):self.jsonlds+=1
        if t in ("h1","h2","title") and self.capture is None:self.capture=t;self.buf=[]
    def handle_data(self,v):
        if self.capture:self.buf.append(v)
    def handle_endtag(self,t):
        if self.capture==t:
            v=" ".join("".join(self.buf).split())
            if t=="h1":self.h1.append(v)
            elif t=="h2":self.h2.append(v)
            elif t=="title":self.title=v
            self.capture=None;self.buf=[]
    def meta_value(self,name):
        for d in self.meta:
            if d.get("name","").lower()==name.lower() or d.get("property","").lower()==name.lower():return d.get("content","")
        return ""
    def rel(self,r):
        return [x for x in self.links if r in x.get("rel","").lower().split()]
def words(x):
    x=html.unescape(re.sub(r"<[^>]*>"," ",x))
    return re.findall(r"[\w]+",x,re.UNICODE)
def source_fields(p,lang):
    return (p["title"] if lang=="es" else p["en_title"],p["seo_title"] if lang=="es" else p["en_seo_title"],
            p["seo_description"] if lang=="es" else p["en_seo_description"],p["slug"] if lang=="es" else p["en_slug"])
def source_audit(ps):
    problems=[];stats=[]; headings=collections.defaultdict(list); introductions=collections.defaultdict(list)
    for p in ps:
        id=p["id"]
        for l in ("es","en"):
            body=p[l]; title,seo,desc,slug=source_fields(p,l)
            wc=len(words(body));heads=re.findall(r"<h2[^>]*>(.*?)</h2>",body,flags=re.S|re.I)
            src_links=re.findall(r'<a\b[^>]*href=["\x27]([^"\x27]+)',body,re.I)
            ext=[x for x in src_links if not x.startswith(SITE) and x.startswith("http")]
            for tag in ("p","h2","a","strong","em","table","thead","tbody","tr","th","td"):
                op=len(re.findall(fr"<{tag}(?:\s[^>]*)?>",body,re.I));cl=len(re.findall(fr"</{tag}>",body,re.I))
                if op!=cl:problems.append({"severity":"critical","id":id,"lang":l,"issue":"unbalanced_source_markup","tag":tag,"open":op,"close":cl})
            if wc < (750 if l=="es" else 700):problems.append({"severity":"high","id":id,"lang":l,"issue":"short_content","words":wc})
            if len(desc)>165:problems.append({"severity":"medium","id":id,"lang":l,"issue":"meta_description_long","length":len(desc)})
            if len(desc)<100:problems.append({"severity":"medium","id":id,"lang":l,"issue":"meta_description_short","length":len(desc)})
            if len(seo)>65:problems.append({"severity":"low","id":id,"lang":l,"issue":"meta_title_long","length":len(seo)})
            if not ext:problems.append({"severity":"medium","id":id,"lang":l,"issue":"no_external_sources_in_body"})
            first=" ".join(words(body)[:35]); introductions[l].append((id,first))
            for h in heads:headings[(l,re.sub(r"<[^>]*>","",h).strip().lower())].append(id)
            stats.append({"id":id,"lang":l,"title":title,"words":wc,"headings":len(heads),"links":len(src_links),"external":len(ext),"seo_title_len":len(seo),"seo_desc_len":len(desc)})
    for (l,h),ids in headings.items():
        if len(set(ids))>1:problems.append({"severity":"medium","issue":"identical_h2_across_articles","lang":l,"heading":h,"ids":sorted(set(ids))})
    intro_pairs=[]
    for l,items in introductions.items():
        for j,(id,x) in enumerate(items):
            for id2,y in items[j+1:]:
                v=difflib.SequenceMatcher(None,x.lower(),y.lower()).ratio()
                if v>.55:intro_pairs.append({"lang":l,"ids":[id,id2],"similarity":round(v,3)})
    return problems,stats,sorted(intro_pairs,key=lambda x:-x["similarity"])[:15]
def fetch_one(arg):
    id,lang,p=arg
    title,seo,desc,slug=source_fields(p,lang)
    url=SITE+("/en/" if lang=="en" else "/")+slug+"/"
    req=urllib.request.Request(url+"?emdo_audit=20261020",headers={"User-Agent":"Mozilla/5.0 (compatible; EMDOContentAudit/1.0)","Cache-Control":"no-cache"})
    try:
        with urllib.request.urlopen(req,timeout=45) as resp:
            actual=resp.geturl(); status=resp.status; data=resp.read(4_000_000).decode("utf-8",errors="replace")
    except Exception as e:
        return {"id":id,"lang":lang,"url":url,"error":str(e)[:160]}
    d=HTMLAudit()
    try:d.feed(data)
    except Exception as e:return {"id":id,"lang":lang,"url":url,"error":"htmlparse: "+str(e)[:160]}
    canonical=[x.get("href","") for x in d.rel("canonical")]
    alternate=[{"hreflang":x.get("hreflang",""),"href":x.get("href","")} for x in d.rel("alternate") if x.get("hreflang")]
    meta_desc=d.meta_value("description")
    robots=d.meta_value("robots")
    page_title=d.title;headings=d.h1
    has_title=(title.casefold() in html.unescape(data).casefold())
    good_h2=len(d.h2)>=2
    og_title=d.meta_value("og:title");og_desc=d.meta_value("og:description")
    return {"id":id,"lang":lang,"url":url,"status":status,"bytes":len(data),"html_lang":d.lang,"title":page_title[:170],"h1":headings[:3],
        "canonical":canonical[:3],"hreflang":alternate[:8],"meta_description":meta_desc[:220],"robots":robots[:100],
        "og_title":og_title[:140],"og_image":d.meta_value("og:image")[:200],"jsonld":d.jsonlds,"total_images":len(d.images),
        "h1_count":len(headings),"h2_count":len(d.h2),"has_own_article_title":has_title,
        "html_heading_present":good_h2,"contains_english" if lang=="en" else "contains_spanish":has_title}
def check_live(item):
    errs=[]
    if "error" in item:
        errs.append("fetch_failure");return errs
    if item["status"]!=200:errs.append("non_200")
    if item["bytes"]<6000:errs.append("html_too_short")
    if item["h1_count"]!=1:errs.append("h1_count_"+str(item["h1_count"]))
    if not item["has_own_article_title"]:errs.append("missing_article_title")
    if not item["meta_description"]:errs.append("missing_meta_description")
    if not item["canonical"]:errs.append("missing_canonical")
    else:
        if urlparse(item["canonical"][0]).path.rstrip("/")!=urlparse(item["url"]).path.rstrip("/"):errs.append("canonical_to_different_page")
    if "noindex" in item["robots"].lower():errs.append("NOINDEX")
    hs={x["hreflang"].lower() for x in item["hreflang"]}
    if not any(h.startswith("es") for h in hs):errs.append("missing_spanish_hreflang")
    if not any(h.startswith("en") for h in hs):errs.append("missing_english_hreflang")
    if item["jsonld"]==0:errs.append("no_jsonld")
    if not item["og_image"]:errs.append("missing_og_image")
    return errs
def historical_audit(ps):
    file=ROOT/".deploy-status/blog-related-products-inventory-20261019.json"
    if not file.exists():return []
    a=json.loads(file.read_text());old=[]
    def walk(x):
        if isinstance(x,list):
            for y in x:walk(y)
        elif isinstance(x,dict):
            if "title" in x and isinstance(x["title"],str) and ("post_id" in x or "id" in x or "slug" in x):
                old.append(x["title"])
            else:
                for y in x.values():walk(y)
    walk(a.get("all_post_topics",[]));walk(a.get("topics",{}))
    unique=list(dict.fromkeys(old));stop={"cómo","para","qué","cuál","comprar","guía","de","del","la","el","los","las","en","y","por","un","una","con","españa","tipos","mejor","casas"}
    def tok(s):
        s=s.lower();s=re.sub(r"[^\w]+"," ",s)
        return set(x for x in s.split() if len(x)>=4 and x not in stop)
    results=[]
    for p in ps:
        x=tok(p["title"])
        for t in unique:
            y=tok(t);sim=len(x&y)/max(1,len(x|y))
            if sim>=.29 and len(x&y)>=2:results.append({"new_id":p["id"],"new_title":p["title"],"old_title":t,"jaccard":round(sim,2)})
    return sorted(results,key=lambda x:-x["jaccard"])[:40]
def main():
    ps=[]
    for id in range(1,25):
        file=ROOT/f"{PREFIX}{id:02}.json"
        if not file.exists():raise SystemExit(f"Missing {file}")
        p=json.loads(file.read_text())
        if p["id"]!=id:raise SystemExit(f"Invalid plan id {id}")
        ps.append(p)
    problems,source_stats,intros=source_audit(ps)
    jobs=[(p["id"],l,p) for p in ps for l in ("es","en")]
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:live=list(pool.map(fetch_one,jobs))
    issues=[{"id":x["id"],"lang":x["lang"],"url":x["url"],"problems":z} for x in live if (z:=check_live(x))]
    report={"run":"2026-10-20 editorial independent read-only QA","article_count":len(ps),"live_page_count":len(live),
        "live_success":sum(x.get("status")==200 for x in live),"source":source_stats,
        "source_problems":problems,"similar_intros":intros,"historic_overlap":historical_audit(ps),
        "live":live,"live_issues":issues}
    pathlib.Path("/tmp/emdo-editorial-audit.json").write_text(json.dumps(report,ensure_ascii=False,indent=2))
    print("AUDIT_SUMMARY "+json.dumps({"articles":len(ps),"pages":len(live),"live_http_200":report["live_success"],
            "source_issues":len(problems),"live_flagged":len(issues),"historical_overlaps":len(report["historic_overlap"]),"introduction_repeats":len(intros)},ensure_ascii=False))
    for x in source_stats:print("SOURCE_STAT "+json.dumps(x,ensure_ascii=False))
    for x in problems:print("SOURCE_PROBLEM "+json.dumps(x,ensure_ascii=False))
    for x in issues:print("LIVE_PROBLEM "+json.dumps(x,ensure_ascii=False))
    for x in live:
        print("LIVE_META "+json.dumps({k:v for k,v in x.items() if k in ("id","lang","status","url","error","html_lang","h1_count","title","canonical","hreflang","meta_description","robots","og_image","jsonld","total_images")},ensure_ascii=False))
    for x in report["historic_overlap"]:print("HISTORIC_OVERLAP "+json.dumps(x,ensure_ascii=False))
    for x in intros:print("INTRO_SIMILAR "+json.dumps(x,ensure_ascii=False))
    print("AUDIT_COMPLETE")
if __name__=="__main__": main()
