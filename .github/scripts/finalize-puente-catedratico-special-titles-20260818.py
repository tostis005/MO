import json,re,sys,unicodedata
SRC,INP,DST=sys.argv[1:4]
with open(SRC,encoding='utf-8') as f:source={int(p['id']):p for p in json.load(f)['products']}
with open(INP,encoding='utf-8') as f:data=json.load(f)

def slugify(s):
    s=unicodedata.normalize('NFKD',s).encode('ascii','ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+','-',s).strip('-')[:175].strip('-')

def suffix(src):
    rules=[
      (r'\s*\((?:Deshuesado|Deshuesada)\)\s*$',' (Boneless)'),
      (r'\s*\((?:Cortado|Cortada) a cuchillo\)\s*$',' (Hand-sliced)'),
      (r'\s*\((?:Cortado|Cortada) a máquina\)\s*$',' (Machine-sliced)'),
      (r'\s*\(En tubo\)\s*$',' (Presentation Tube)'),
      (r'\s*\(En caja de madera\)\s*$',' (Wooden Box)'),
    ]
    for pat,en in rules:
        if re.search(pat,src,re.I):return re.sub(pat,'',src,flags=re.I).strip(),en
    return src,''

SPECIAL={
'Chorizo bellota ibérico 100%. el mejor del mundo':"100% Iberian Acorn-fed Chorizo – World's Best",
'Sobres de chorizo bellota ibérico 100%. el mejor del mundo.':"Packs of 100% Iberian Acorn-fed Chorizo – World's Best",
'Salchichón bellota ibérico 100%. mejor salchichon 2025.':'100% Iberian Acorn-fed Salchichón – Best Salchichón 2025',
'Sobres de salchichón bellota ibérico 100%. mejor salchichon 2025.':'Packs of 100% Iberian Acorn-fed Salchichón – Best Salchichón 2025',
'Salchichón bellota ibérico 100% herradura':'100% Iberian Acorn-fed Salchichón (Horseshoe-shaped)',
}
for row in data['products']:
    src=source[int(row['id'])]['title'];base,suf=suffix(src)
    title=None
    if base in SPECIAL:title=SPECIAL[base]+suf
    if title is None:
        m=re.match(r'^Chorizo(?: de)? bellota ibérico\s+(\d+)%$',base,re.I)
        if m:title=f'{m.group(1)}% Iberian Acorn-fed Chorizo'+suf
    if title is None:
        m=re.match(r'^Salchichón(?: de)? bellota ibérico\s+(\d+)%$',base,re.I)
        if m:title=f'{m.group(1)}% Iberian Acorn-fed Salchichón'+suf
    if title is not None:
        row['title']=title;row['slug']=slugify(title)
with open(DST,'w',encoding='utf-8') as f:json.dump(data,f,ensure_ascii=False,separators=(',',':'))
print('FINALIZED',len(data['products']))
