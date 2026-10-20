#!/usr/bin/env python3
"""Offline Spanish to English product copy translator with field integrity checks.

No external translation API, no server changes. Only valid records enter payload.
"""
import collections
import html
import json
import pathlib
import re
import sys
import unicodedata
from decimal import Decimal, InvalidOperation

import torch
from transformers import MarianMTModel, MarianTokenizer

src=pathlib.Path(sys.argv[1])
dst=pathlib.Path(sys.argv[2])
report_file=pathlib.Path(sys.argv[3])
source=json.loads(src.read_text(encoding='utf-8'))
items=source['products']
torch.set_num_threads(4)
model_name='Helsinki-NLP/opus-mt-es-en'
print('OFFLINE_MODEL',model_name,'PRODUCTS',len(items),flush=True)
tokenizer=MarianTokenizer.from_pretrained(model_name)
model=MarianMTModel.from_pretrained(model_name)
model.eval()

tag_re=re.compile(r'(<[^>]*>)',re.S)
number_re=re.compile(r'(?<![A-Za-z])\d+(?:[.,]\d+)?')
alpha_re=re.compile(r'[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]')
skip_spanish=re.compile(r'\b(?:de|del|una|para|las|los|que|con|al|en|por|el|es|ingredientes|conservación|producto|aproximadamente|peso|envío)\b',re.I)
brand_replacements=[
    (re.compile(r'BIO\s*&\s*FT|BIOFT',re.I),'BIO&FT'),
    (re.compile(r'\bLatitudes\b',re.I),'Latitudes'),
    (re.compile(r'\bMontJam\b',re.I),'MontJam'),
    (re.compile(r'\bHidalgo de la Jara\b',re.I),'Hidalgo de la Jara'),
    (re.compile(r'\bEl Catedrático\b',re.I),'El Catedrático'),
    (re.compile(r'\bPuente Robles\b',re.I),'Puente Robles'),
    (re.compile(r'\bWagyu\b',re.I),'Wagyu'),
]
fixed={
    'sí':'Yes','no':'No','ingredientes':'Ingredients','ingredientes:':'Ingredients:',
    'conservación':'Storage','conservación:':'Storage:',
    'características':'Features','características:':'Features:',
    'envío':'Shipping','envío:':'Shipping:','peso':'Weight','peso:':'Weight:',
    'formato y peso:':'Format and weight:','formato y peso':'Format and weight',
    'cómo prepararlo':'How to prepare it','cómo prepararla':'How to prepare it',
    'cómo prepararlos':'How to prepare them','cómo prepararlas':'How to prepare them',
    'cómo utilizarlo':'How to use it','sal':'Salt','sal:':'Salt:',
    'no contiene gluten':'Gluten-free','sin gluten':'Gluten-free',
}
def plain(s):
    return re.sub(r'\s+',' ',html.unescape(re.sub('<[^>]*>',' ',str(s),flags=re.S))).strip()
def canon_num(s):
    try:
        n=Decimal(s.replace(',','.'))
        return format(n.normalize(),'f') if n else '0'
    except InvalidOperation:return s.replace(',','.')
def numeric(s):
    return collections.Counter(canon_num(x) for x in number_re.findall(plain(s)))
def slugify(s):
    s=unicodedata.normalize('NFKD',s).encode('ascii','ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+','-',s).strip('-')[:170].strip('-')
def sections(value):
    """Return text nodes and preserve HTML tags verbatim."""
    if not value:return []
    out=[];skip=False
    for piece in tag_re.split(value):
        if piece.startswith('<'):
            low=piece.lower()
            if low.startswith(('<script','<style')):skip=True
            elif low.startswith(('</script','</style')):skip=False
            out.append(('tag',piece))
        elif skip or not alpha_re.search(html.unescape(piece)):
            out.append(('literal',piece))
        else:out.append(('text',piece))
    return out
def split_chunks(s,limit=330):
    s=re.sub(r'\s+',' ',s).strip()
    if len(s)<=limit:return [s]
    chunks=[];current=''
    sentences=re.split(r'(?<=[.!?;])\s+',s)
    for sentence in sentences:
        words=sentence.split()
        small=[];seg=''
        for word in words:
            if seg and len(seg)+len(word)+1>limit:small.append(seg);seg=word
            else:seg=(seg+' '+word).strip()
        if seg:small.append(seg)
        for part in small:
            if current and len(current)+len(part)+1>limit:chunks.append(current);current=part
            else:current=(current+' '+part).strip()
    if current:chunks.append(current)
    return chunks
def canonical_brand(text,original):
    if re.search(r'BIO\s*&\s*FT|BIOFT',original,re.I):
        text=re.sub(r'BIO\s*&\s*FT|BIOFT','BIO&FT',text,flags=re.I)
        if 'BIO&FT' not in text:text='BIO&FT '+text
    return text
def normalize_generated(x):
    replacements=[
        (r'\bIberian race\b','Iberian breed'),
        (r'\bpalette\b','shoulder ham'),
        (r'\bhealing time\b','curing time'),
        (r'\bhealing process\b','curing process'),
        (r'\bvacuum packed\b','vacuum-packed'),
        (r'\bdehesa pasture pasture\b','dehesa pasture'),
    ]
    for old,new in replacements:x=re.sub(old,new,x,flags=re.I)
    return x
def restore_brands(x,original):
    for rx,name in brand_replacements:
        if rx.search(original):
            x=rx.sub(name,x)
            if name.casefold() not in x.casefold() and name!='Wagyu':
                # Some names in running text may be dropped by MT; reject below instead of inventing placement.
                raise ValueError('protected_brand_lost:'+name)
    return x

raws=set()
field_needs={}
for r in items:
    en=r.get('existing_english',{})
    required={}
    required['title']=not plain(en.get('title',''))
    required['excerpt']=bool(plain(r['excerpt'])) and not plain(en.get('excerpt',''))
    required['content']=bool(plain(r['content'])) and not plain(en.get('content',''))
    field_needs[r['id']]=required
    for key,needed in required.items():
        if not needed:continue
        val=r[key]
        for kind,part in sections(val):
            if kind=='text':
                raw=html.unescape(part).strip()
                if raw and alpha_re.search(raw):raws.add(raw)
raws=sorted(raws,key=lambda x:(len(x),x))
mapping={}
work=[]
for raw in raws:
    if raw.casefold() in fixed:
        mapping[raw]=fixed[raw.casefold()]
    elif re.fullmatch(r'[\s\d,.%()/+-]+',raw):
        mapping[raw]=raw
    else:
        segs=split_chunks(raw)
        work.extend((raw,seg) for seg in segs)
print('TEXT_NODES',len(raws),'SEGMENTS',len(work),flush=True)

translated_segments=[]
batch_size=16
for start in range(0,len(work),batch_size):
    block=[seg for _,seg in work[start:start+batch_size]]
    inputs=tokenizer(block,return_tensors='pt',padding=True,truncation=True,max_length=450)
    with torch.inference_mode():
        outputs=model.generate(**inputs,max_new_tokens=450,num_beams=3,early_stopping=True)
    translated_segments.extend(tokenizer.batch_decode(outputs,skip_special_tokens=True))
    if start%160==0:print('MODEL_PROGRESS',min(start+len(block),len(work)),len(work),flush=True)
grouped=collections.defaultdict(list)
for (raw,_seg),translated in zip(work,translated_segments):grouped[raw].append(normalize_generated(translated.strip()))
for raw,parts in grouped.items():mapping[raw]=' '.join(parts)
# Leave mapping errors at the field/product boundary; never publish a partial machine translation.
def translate_field(value):
    if not value:return ''
    out=[]
    for kind,part in sections(value):
        if kind!='text':out.append(part);continue
        decoded=html.unescape(part)
        lead=re.match(r'^\s*',decoded).group(0)
        tail=re.search(r'\s*$',decoded).group(0)
        raw=decoded.strip()
        if not raw or raw not in mapping:
            out.append(part);continue
        val=mapping[raw]
        if raw!=val:
            val=canonical_brand(val,raw)
            val=restore_brands(val,raw)
        if len(raw.split())>=6 and raw.casefold()==val.casefold() and skip_spanish.search(raw):
            raise ValueError('untranslated_spanish_text')
        if numeric(raw)!=numeric(val):
            raise ValueError('numeric_mismatch_in_text')
        out.append(lead+html.escape(val,quote=False)+tail)
    translated=''.join(out)
    if numeric(value)!=numeric(translated):raise ValueError('numeric_mismatch_in_field')
    return translated

ready=[];failed=[]
for i,p in enumerate(items):
    try:
        en=p.get('existing_english',{})
        title=html.unescape(translate_field(p['title'])) if field_needs[p['id']]['title'] else en['title']
        excerpt=translate_field(p['excerpt']) if field_needs[p['id']]['excerpt'] else en['excerpt']
        content=translate_field(p['content']) if field_needs[p['id']]['content'] else en['content']
        slug=en.get('slug') or slugify(title)
        if not plain(title) or not slug:raise ValueError('missing_title_or_slug')
        if plain(p['excerpt']) and not plain(excerpt):raise ValueError('missing_excerpt')
        if plain(p['content']) and not plain(content):raise ValueError('missing_content')
        if numeric(p['title'])!=numeric(title) and field_needs[p['id']]['title']:raise ValueError('title_numbers_differ')
        ready.append({
            'id':p['id'],'author_id':p['author_id'],'vendor':'',
            'source_status':p['status'],'source_title':p['title'],'source_excerpt':p['excerpt'],'source_content':p['content'],
            'title':title,'slug':slug,'excerpt':excerpt,'content':content
        })
    except Exception as e:
        failed.append({'id':p['id'],'reason':str(e)[:240]})
    if i%40==0:print('VALIDATION_PROGRESS',i,len(items),flush=True)
dst.write_text(json.dumps({'products':ready},ensure_ascii=False),encoding='utf-8')
report_file.write_text(json.dumps({'source_count':len(items),'translated_count':len(ready),'translation_failed':failed,'translator':'offline-Helsinki-NLP-opus-mt-es-en'},ensure_ascii=False,indent=2),encoding='utf-8')
print('READY',len(ready),'FAIL',len(failed),flush=True)
