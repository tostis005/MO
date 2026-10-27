#!/usr/bin/env python3
"""Conservative read-only OCR of supplier footer crops; count confirmed URLs/copyright."""
from pathlib import Path
from PIL import Image, ImageOps, ImageEnhance, ImageDraw
import json, subprocess, sys, re, tempfile, shutil
from concurrent.futures import ThreadPoolExecutor, as_completed
root=Path(sys.argv[1]); info=json.loads((root/'manifest.json').read_text())
photos=info['photos']
supplier=re.compile(r'latitud(?:es|e[s3]|ez)?',re.I)
copyright=re.compile(r'c[o0]pyright|©|reserved rights|todos los derechos',re.I)
domain=re.compile(r'(?:https?://|www[.])\S+|[a-z0-9_-]+\.(?:com|es|net|org)',re.I)
def scan(item):
    p=root/item['bottom_crop'] if item.get('bottom_crop') else None
    if p is None or not p.exists(): return {'image_id':item['image_id'],'status':'NO_CROP'}
    im=Image.open(p).convert('L');im=ImageEnhance.Contrast(im).enhance(1.5)
    if im.width<1000:
        z=min(3,1000/im.width); im=im.resize((round(im.width*z),round(im.height*z)))
    texts=[]
    with tempfile.NamedTemporaryFile(suffix='.png') as tmp:
        im.save(tmp.name)
        for mode in ('6','11'):
            out=subprocess.run(['tesseract',tmp.name,'stdout','--psm',mode,'-l','eng'],
                stdout=subprocess.PIPE,stderr=subprocess.DEVNULL,timeout=26)
            texts.append(out.stdout.decode(errors='replace'))
    txt='\n'.join(texts); islat=bool(supplier.search(txt));iscopy=bool(copyright.search(txt))
    domains=[m.group() for m in domain.finditer(txt) if 'elmercadodeorigen' not in m.group().lower()]
    match=islat or iscopy or bool(domains)
    return {'image_id':item['image_id'],'status':('LATITUDES_TEXT' if islat else 'COPYRIGHT_TEXT' if iscopy else 'EXTERNAL_DOMAIN' if domains else 'NO_FOOTER_DETECTED'),
        'match':match,'source_file':item['basename'],'products':item['products'],'evidence':txt.strip()[:1100],'domains':domains}
rows=[]
with ThreadPoolExecutor(max_workers=6) as pool:
    futs={pool.submit(scan,x):x for x in photos}
    for f in as_completed(futs):
        try:rows.append(f.result())
        except Exception as e:rows.append({'image_id':futs[f]['image_id'],'status':'OCR_ERROR','error':str(e)})
rows.sort(key=lambda x:x['image_id'])
affected=[x for x in rows if x.get('match')]
by_status={}
for x in rows:by_status[x['status']]=by_status.get(x['status'],0)+1
summary={'supplier_products':info['supplier_product_count'],'total_unique_product_images':info['unique_media_count'],
    'scanned_images':len(rows),'confirmed_footer_or_external_url_images':len(affected),
    'confirmed_latitudes_text_images':sum(x['status']=='LATITUDES_TEXT' for x in rows),
    'no_crops_or_ocr_errors':sum(x['status'] in ('NO_CROP','OCR_ERROR') for x in rows),
    'counts_by_status':by_status,'note':'OCR preliminary; tiny or stylized footer text may require manual validation.'}
(root/'results.json').write_text(json.dumps({'summary':summary,'matches':affected,'all_results':rows},ensure_ascii=False,indent=2))
(root/'footer-matches').mkdir(exist_ok=True)
(root/'contact-sheets').mkdir(exist_ok=True)
for x in affected:
    f=root/'bottom'/f"{x['image_id']:06d}.jpg"
    if f.exists():shutil.copy2(f,root/'footer-matches'/f.name)
allrows=[x for x in rows if (root/'bottom'/f"{x['image_id']:06d}.jpg").exists()]
for pageidx in range(0,len(allrows),35):
    group=allrows[pageidx:pageidx+35];cw=300;ch=211;cols=5
    out=Image.new('RGB',(cols*cw,((len(group)+cols-1)//cols)*ch),'white');d=ImageDraw.Draw(out)
    for i,x in enumerate(group):
        im=Image.open(root/'bottom'/f"{x['image_id']:06d}.jpg").convert('RGB'); im.thumbnail((290,170))
        px=(i%cols)*cw;py=(i//cols)*ch
        out.paste(im,(px+(cw-im.width)//2,py+3))
        d.text((px+8,py+177),f"{x['image_id']} | {x['status'][:15]}",fill='#aa0000' if x.get('match') else '#222222')
    out.save(root/'contact-sheets'/f"sheet-{pageidx//35+1:02d}.jpg",quality=85)
print("LATITUDES_FOOTER_AUDIT "+json.dumps(summary,ensure_ascii=False))
for x in affected:print('AFFECTED_IMAGE '+json.dumps({'image_id':x['image_id'],'name':x['source_file'],'products':x['products'],'evidence':x['evidence'][:250]},ensure_ascii=False))
