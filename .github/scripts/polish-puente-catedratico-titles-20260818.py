import json,re,sys,unicodedata

SRC=sys.argv[1]
TR=sys.argv[2]
DST=sys.argv[3]
with open(SRC,encoding='utf-8') as f: src=json.load(f)
with open(TR,encoding='utf-8') as f: tr=json.load(f)
source={int(p['id']):p for p in src['products']}
rows={int(p['id']):p for p in tr['products']}

def slugify(s):
    s=unicodedata.normalize('NFKD',s).encode('ascii','ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+','-',s).strip('-')[:175].strip('-')

def clean(s):
    s=re.sub(r'\s+',' ',s).strip()
    s=re.sub(r'\s+([,.;:!?])',r'\1',s)
    return s

EXACT={
'Taco de jamón cesáreo selección gourmet':'Cesáreo Gourmet Selection Ham Chunk',
'Jamón cesáreo selección gourmet':'Cesáreo Gourmet Selection Ham',
'Paleta cesáreo selección gourmet':'Cesáreo Gourmet Selection Shoulder Ham',
'Jamón reserva':'Reserve Ham',
'Taco de jamón reserva':'Reserve Ham Chunk',
'Codillo de jamón curado':'Cured Ham Hock',
'Lomo embuchado extra':'Extra Cured Pork Loin',
'Cabecero lomo extra':'Extra Cured Loin Head',
'Chorizo cular de bellota ¡mejor chorizo del mundo!':"World's Best Acorn-fed Cular Chorizo",
'Sobres chorizo cular bellota. ¡mejor chorizo del mundo!':"Packs of World's Best Acorn-fed Cular Chorizo",
'Chorizo extra bellota. declarado mejor del mundo 2025':"Acorn-fed Extra Chorizo – Declared World's Best 2025",
'Chorizo cular extra':'Extra Cular Chorizo',
'Chorizo extra':'Extra Chorizo',
'Chorizo extra picante':'Extra Spicy Chorizo',
'Salchichón cular de bellota':'Acorn-fed Cular Salchichón',
'Sobres de salchichón cular de bellota':'Packs of Acorn-fed Cular Salchichón',
'Salchichón ibérico bellota extra declarado mejor salchichon bellota del mundo 2025 frankfurt':"Extra Iberian Acorn-fed Salchichón – World's Best 2025, Frankfurt",
'Salchichón cular extra':'Extra Cular Salchichón',
'Chorizo cular picante':'Spicy Cular Chorizo',
'Salchichón extra':'Extra Salchichón',
'Lote puente robles':'Puente Robles Selection',
'Lote ibérico al corte':'Sliced Iberian Selection',
'Pack degustación puente robles':'Puente Robles Tasting Pack',
'Lote ibérico':'Iberian Selection',
'Lote ibérico cebo':'Grain-fed Iberian Selection',
'Lote bellota':'Acorn-fed Selection',
'Pack gourmet jamón y queso':'Gourmet Ham and Cheese Pack',
'Lote bellota ibérica':'Iberian Acorn-fed Selection',
'Lote paleta ibérica':'Iberian Shoulder Ham Selection',
'Lote paleta bellota':'Acorn-fed Shoulder Ham Selection',
'Lote paleta campo':'Free-range Shoulder Ham Selection',
'Lote raza ibérica':'Iberian Breed Selection',
'Pack paleta cebo':'Grain-fed Shoulder Ham Pack',
'Pack paleta bellota':'Acorn-fed Shoulder Ham Pack',
'La caja del estudiante':'The Student Box',
'Loncheados degustación':'Tasting Sliced Selection',
'Cátedra ibérica':'Iberian Masterclass',
'Catedrático degustación':'El Catedrático Tasting Selection',
'Lote gourmet tradición ibérica':'Iberian Tradition Gourmet Selection',
'Pack sabor artesano':'Artisan Flavour Pack',
'El catedrático selección':'El Catedrático Selection',
'Universidad ibérica':'Iberian University',
'La cátedra del sabor':'The Flavour Masterclass',
'El catedrático gourmet':'El Catedrático Gourmet',
'Chorizo extra bellota':'Extra Acorn-fed Chorizo',
'Tabla jamonera modelo góndola':'Gondola Model Ham Stand',
'Tabla jamonera modelo bellota 3':'Bellota Model 3 Ham Stand',
'Tabla jamonera modelo basculante en acero inoxidable':'Stainless Steel Tilting Ham Stand',
'Blíster de cuchillo y afilador':'Knife and Sharpener Blister Pack',
'Chorizo para asar':'Grilling Chorizo',
'Costilla adobada curada':'Cured Marinated Pork Ribs',
'Lomo adobado semicurado':'Semi-cured Marinated Pork Loin',
'Panceta adobada curada':'Cured Marinated Pork Belly',
'Virutas de jamón premium':'Premium Ham Shavings',
}

SUFFIXES=[
(r'\s*\((?:Deshuesado|Deshuesada)\)\s*$',' (Boneless)'),
(r'\s*\((?:Cortado|Cortada) a cuchillo\)\s*$',' (Hand-sliced)'),
(r'\s*\((?:Cortado|Cortada) a máquina\)\s*$',' (Machine-sliced)'),
(r'\s*\(En tubo\)\s*$',' (Presentation Tube)'),
(r'\s*\(En caja de madera\)\s*$',' (Wooden Box)'),
]

def strip_suffix(s):
    for pat,en in SUFFIXES:
        if re.search(pat,s,re.I): return re.sub(pat,'',s,flags=re.I).strip(),en
    return s,''

def pct_pattern(base):
    patterns=[
      (r'^Jamón de bellota(?: ibérico)?\s+(\d+)%\s+(?:raza ibérica|ibérico)$',lambda n:f'{n}% Iberian Acorn-fed Ham'),
      (r'^Jamón de cebo de campo ibérico\s+(\d+)%\s+raza ibérica$',lambda n:f'{n}% Iberian Free-range Grain-fed Ham'),
      (r'^Jamón de cebo ibérico\s+(\d+)%\s+raza ibérica$',lambda n:f'{n}% Iberian Grain-fed Ham'),
      (r'^Paleta de bellota(?: ibérica)?\s+(\d+)%\s+(?:raza ibérica|ibérica)$',lambda n:f'{n}% Iberian Acorn-fed Shoulder Ham'),
      (r'^Paleta de cebo de campo ibérica\s+(\d+)%\s+raza ibérica$',lambda n:f'{n}% Iberian Free-range Grain-fed Shoulder Ham'),
      (r'^Paleta de cebo ibérica\s+(\d+)%\s+raza ibérica$',lambda n:f'{n}% Iberian Grain-fed Shoulder Ham'),
      (r'^Lomo(?: de)? bellota\s+(\d+)%\s+ibérico$',lambda n:f'{n}% Iberian Acorn-fed Cured Loin'),
      (r'^Lomo de bellota\s+(\d+)%\s+ibérico$',lambda n:f'{n}% Iberian Acorn-fed Cured Loin'),
      (r'^Lomo de bellota ibérico\s+(\d+)%\s+raza ibérico$',lambda n:f'{n}% Iberian Acorn-fed Cured Loin'),
      (r'^Caña de lomo de cebo de campo\s+(\d+)%\s+raza ibérico$',lambda n:f'{n}% Iberian Free-range Grain-fed Cured Loin'),
      (r'^Lomo de cebo de campo\s+(\d+)%\s+raza ibérico$',lambda n:f'{n}% Iberian Free-range Grain-fed Cured Loin'),
      (r'^Lomito de bellota\s+(\d+)%\s+ibérico$',lambda n:f'{n}% Iberian Acorn-fed Presa Loin'),
    ]
    for pat,fn in patterns:
        m=re.match(pat,base,re.I)
        if m:return fn(m.group(1))
    return None

def natural(src_title,fallback):
    base,suffix=strip_suffix(src_title)
    if base in EXACT:return EXACT[base]+suffix
    core=pct_pattern(base)
    if core:return core+suffix

    m=re.match(r'^Taco(?: de)? jamón de bellota(?: ibérico)?\s+(\d+)%\s+(?:raza ibérica|ibérico)$',base,re.I)
    if m:return f'{m.group(1)}% Iberian Acorn-fed Ham Chunk'+suffix
    m=re.match(r'^Taco(?: de)? jamón de cebo de campo ibérico\s+(\d+)%\s+raza ibérica$',base,re.I)
    if m:return f'{m.group(1)}% Iberian Free-range Grain-fed Ham Chunk'+suffix
    m=re.match(r'^Taco(?: de)? jamón de cebo ibérico\s+(\d+)%\s+raza ibérica$',base,re.I)
    if m:return f'{m.group(1)}% Iberian Grain-fed Ham Chunk'+suffix

    m=re.match(r'^Sobres de?\s*jamón de bellota ibérico\s+(\d+)%\s+raza ibérica$',base,re.I)
    if m:return f'Packs of {m.group(1)}% Iberian Acorn-fed Ham'+suffix
    m=re.match(r'^Sobres de?\s*jamón de cebo de campo ibérico\s+(\d+)%\s+raza ibérica$',base,re.I)
    if m:return f'Packs of {m.group(1)}% Iberian Free-range Grain-fed Ham'+suffix
    m=re.match(r'^Sobres de?\s*jamón de cebo ibérico\s+(\d+)%\s+raza ibérica$',base,re.I)
    if m:return f'Packs of {m.group(1)}% Iberian Grain-fed Ham'+suffix
    m=re.match(r'^Sobres de lomo(?: de)? bellota\s+(\d+)%\s*(?:ibérico)?$',base,re.I)
    if m:return f'Packs of {m.group(1)}% Iberian Acorn-fed Cured Loin'+suffix

    m=re.match(r'^Pack (\d+):\s*(\d+) sobres de paleta de bellota ibérica 100% raza ibérica cortada a máquina\.?$',base,re.I)
    if m:return f'Pack {m.group(1)}: {m.group(2)} Packs of 100% Iberian Acorn-fed Shoulder Ham (Machine-sliced)'
    m=re.match(r'^Pack (\d+):\s*(\d+) sobres de paleta de cebo de campo ibérica 50% raza ibérica cortada a máquina\.?$',base,re.I)
    if m:return f'Pack {m.group(1)}: {m.group(2)} Packs of 50% Iberian Free-range Grain-fed Shoulder Ham (Machine-sliced)'
    m=re.match(r'^2 Paletas de cebo de campo ibérica 50% raza ibérica$',base,re.I)
    if m:return '2 × 50% Iberian Free-range Grain-fed Shoulder Hams'
    m=re.match(r'^Paleta de bellota ibérica 75% raza ibérica de 5 kg \+ jamonero y cuchillo gratis$',base,re.I)
    if m:return '75% Iberian Acorn-fed Shoulder Ham, 5 kg + Free Ham Stand and Knife'
    m=re.match(r'^Jamón de cebo ibérico 50% de (\d+(?:[.,]\d+)?) kg a (\d+(?:[.,]\d+)?) kg \+ jamonero y cuchillo gratis$',base,re.I)
    if m:return f'50% Iberian Grain-fed Ham, {m.group(1).replace(",", ".")}–{m.group(2).replace(",", ".")} kg + Free Ham Stand and Knife'
    m=re.match(r'^Jamón de cebo ibérico 50% de (\d+(?:[.,]\d+)?) kg \+ jamonero y cuchillo gratis$',base,re.I)
    if m:return f'50% Iberian Grain-fed Ham, {m.group(1).replace(",", ".")} kg + Free Ham Stand and Knife'
    m=re.match(r'^Jamón de cebo de campo ibérico 50% raza ibérica (\d+(?:[.,]\d+)?) kg \+ jamonero$',base,re.I)
    if m:return f'50% Iberian Free-range Grain-fed Ham, {m.group(1).replace(",", ".")} kg + Ham Stand'
    m=re.match(r'^Llévate 6 y paga 5:?\s*sobres de 100 grs\.? de?\s*jamón de bellota ibérico 50%$',base,re.I)
    if m:return 'Buy 6, Pay for 5: 100 g Packs of 50% Iberian Acorn-fed Ham'
    m=re.match(r'^Llévate 6 y paga 5:?\s*sobres de 100 grs\.?\s*jamón de cebo ibérico 50%$',base,re.I)
    if m:return 'Buy 6, Pay for 5: 100 g Packs of 50% Iberian Grain-fed Ham'

    if re.match(r'^Pack jamón bellota: 15 sobres de jamón de bellota 100% ibérico$',base,re.I):
        return 'Acorn-fed Ham Pack: 15 Packs of 100% Iberian Acorn-fed Ham'+suffix
    if re.match(r'^Pack cebo campo: 15 sobres de jamón de cebo de campo 50% ibérico$',base,re.I):
        return 'Free-range Grain-fed Ham Pack: 15 Packs of 50% Iberian Free-range Grain-fed Ham'+suffix
    if re.match(r'^Pack bellota: 15 sobres de jamón de bellota 100% ibérico$',base,re.I):
        return 'Acorn-fed Pack: 15 Packs of 100% Iberian Acorn-fed Ham'+suffix
    if re.match(r'^Pack campo: 15 sobres de jamón de cebo de campo ibérico 50% raza ibérica$',base,re.I):
        return 'Free-range Grain-fed Pack: 15 Packs of 50% Iberian Free-range Grain-fed Ham'+suffix

    t=fallback
    fixes=[
      (r'\b(?:cesarean|caesarean) section\b','Cesáreo'),
      (r'\bHam reservation\b','Reserve Ham'),
      (r'\bHam reserve\b','Reserve Ham'),
      (r'\bHam chunk reservation\b','Reserve Ham Chunk'),
      (r'\bHam chunk reserve\b','Reserve Ham Chunk'),
      (r'\bHam hock from ham cured\b','Cured Ham Hock'),
      (r'\bCured loin headboard extra\b','Extra Cured Loin Head'),
      (r'\bPacks (?:from|by)\b','Packs of'),
      (r'\bHam shavings premium\b','Premium Ham Shavings'),
      (r'\bIberian Iberian breed\b','Iberian breed'),
      (r'\bAcorn Pack\b','Acorn-fed Pack'),
      (r'\bGrain-fed field pack\b','Free-range Grain-fed Pack'),
    ]
    for pat,rep in fixes:t=re.sub(pat,rep,t,flags=re.I)
    return clean(t)

for pid,p in rows.items():
    s=source[pid]['title']
    title=natural(s,p['title'])
    p['title']=title
    p['slug']=slugify(title)

out={'source_site':tr.get('source_site'),'products':[rows[int(p['id'])] for p in src['products']]}
with open(DST,'w',encoding='utf-8') as f:json.dump(out,f,ensure_ascii=False,separators=(',',':'))
print(json.dumps({'count':len(out['products']),'cesarean':sum(bool(re.search(r'cesarean|caesarean',p['title'],re.I)) for p in out['products']),'reservation':sum(bool(re.search(r'\breservation\b',p['title'],re.I)) for p in out['products'])},ensure_ascii=False))
