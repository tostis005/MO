#!/usr/bin/env python3
"""Read-only DOM audit of duplicate Jamones y paletas category description."""
from bs4 import BeautifulSoup
from urllib.request import Request, urlopen
from html import unescape
import os, re
url = 'https://www.elmercadodeorigen.com/categoria-producto/jamones-paletas/?jamones_duplicate_audit='+os.environ.get('GITHUB_RUN_ID','1')
req = Request(url,headers={'Cache-Control':'no-cache','User-Agent':'Mozilla/5.0 SEO-category-audit'})
with urlopen(req,timeout=70) as response:
    assert response.status == 200
    raw=response.read().decode('utf-8','replace')
soup=BeautifulSoup(raw,'html.parser')
needle='Descubre nuestra selección de jamones y paletas de productores españoles'
occurrences=[]
for string in soup.find_all(string=True):
    if needle.lower() not in str(string).lower():
        continue
    path=[]
    parent=string.parent
    for i in range(7):
        if not parent: break
        path.append({'tag':parent.name,'id':parent.get('id',''),'class':parent.get('class',[]),
                     'style':parent.get('style','')[:130]})
        parent=parent.parent
    occurrences.append({'text':str(string)[:190], 'path':path, 'excerpt':str(string.parent)[:500]})
print('JAMONES_DUPLICATION_AUDIT=',{'document_bytes':len(raw),'matches':len(occurrences),'occurrences':occurrences})
assert occurrences, 'Jamones native term description missing'
