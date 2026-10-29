import re,urllib.request,html,json
from urllib.parse import urljoin
urls=["https://quesospajarete.com/","https://quesospajarete.com/tienda/"]
for url in urls:
 try:
  req=urllib.request.Request(url,headers={"User-Agent":"Mozilla/5.0"})
  response=urllib.request.urlopen(req,timeout=25)
  txt=response.read(950000).decode("utf-8","replace")
  print("SOURCE_PAGE",url,"url",response.url,"bytes",len(txt),flush=True)
  imgs=re.findall(r'<img\b[^>]*>',txt,re.I|re.S)
  for img in imgs:
   klass=re.search(r'class\s*=\s*["\']([^"\']+)["\']',img,re.I)
   alt=re.search(r'alt\s*=\s*["\']([^"\']+)["\']',img,re.I)
   src=re.search(r'(?:src|data-src)\s*=\s*["\']([^"\']+)["\']',img,re.I)
   title=re.search(r'title\s*=\s*["\']([^"\']+)["\']',img,re.I)
   haslogo="logo" in img.lower() or "pajarete" in img.lower()
   if haslogo:
    print("SOURCE_LOGO_CANDIDATE",json.dumps({
     "tag":html.unescape(img[:650]),"class":klass.group(1) if klass else None,
     "alt":alt.group(1) if alt else None,"src":urljoin(url,html.unescape(src.group(1))) if src else None,
     "title":title.group(1) if title else None},ensure_ascii=False),flush=True)
  for x in re.findall(r'<link\b[^>]*>',txt,re.I|re.S):
   if "icon" in x.lower() or "apple-touch" in x.lower():
    if "stylesheet" not in x.lower():print("SOURCE_ICON_LINK",x[:500],flush=True)
  for x in re.findall(r'url\(["\']?([^)"\']+\.((?:svg)|(?:png)|(?:webp)))(?:\?[^)"\']*)?["\']?\)',txt,re.I):
   if "logo" in x[0].lower():print("SOURCE_CSS_LOGO",x[0],flush=True)
 except Exception as err:print("SOURCE_PAGE_ERROR",url,str(err),flush=True)
