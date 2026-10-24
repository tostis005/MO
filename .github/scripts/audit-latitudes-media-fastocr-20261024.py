#!/usr/bin/env python3
"""Auditoría OCR no destructiva de imágenes Latitudes exportadas por WP-CLI."""
import csv
import io
import json
import os
import re
import subprocess
import sys
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path
from urllib.parse import urlsplit
import requests
from PIL import Image, ImageDraw, ImageOps

Image.MAX_IMAGE_PIXELS = 35_000_000
BASE = Path("latitudes-audit-fast")
BASE.mkdir(exist_ok=True)
manifest = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
media = manifest.get("media", [])
print("MEDIA_INVENTORY", json.dumps({
    k: manifest.get(k) for k in
    ["source_product_count","associated_product_count","unique_media_count",
     "source_original_urls_count","media_placements","vendor_id"]
}, ensure_ascii=False), flush=True)
client = requests.Session()
client.headers.update({"User-Agent":"Mozilla/5.0 (compatible; MDO Media Audit; read-only)"})

def extract_text(im, psm):
    fp = BASE / ("tmp-" + str(os.getpid()) + "-" + str(__import__("threading").get_ident()) + ".png")
    try:
        im.save(fp)
        r = subprocess.run(
            ["tesseract", str(fp), "stdout", "-l", "spa+eng", "--psm", str(psm)],
            capture_output=True, text=True, timeout=35)
        return r.stdout[:30000] if r.returncode == 0 else ""
    except Exception:
        return ""
    finally:
        fp.unlink(missing_ok=True)

def logo_match(txt):
    upper = txt.upper()
    simplified = re.sub(r"[^A-Z0-9]+", "", upper)
    return "LATITUDES" in simplified or "LAT1TUDES" in simplified or "LATlTUDES" in simplified

def examine(idx, m):
    row = {**m, "status":"", "ocr_match":False, "ocr_excerpt":"",
           "error":"", "width":None, "height":None}
    try:
        url = str(m["url"])
        if urlsplit(url).scheme != "https":
            row["status"]="not_https"; return row
        resp = client.get(url, timeout=(10,35), headers={"Accept":"image/*"})
        resp.raise_for_status()
        if len(resp.content) > 19_000_000:
            row["status"]="too_large"; return row
        img = Image.open(io.BytesIO(resp.content))
        img.load()
        row["width"],row["height"] = img.size
        img = ImageOps.exif_transpose(img).convert("RGB")
        # Reconocer texto a resolución útil; primero vista completa y después bordes.
        img.thumbnail((1400,1400))
        options = [img]
        gray = ImageOps.autocontrast(ImageOps.grayscale(img))
        options.append(gray.resize((gray.width*2,gray.height*2)) if max(gray.size)<1150 else gray)
        parts = []  # fastest pixel-wide two-pass OCR; visual mosaic handles ambiguous branding
        texts = []
        for i, part in enumerate(options):
            text = extract_text(part, 11 if i==0 else 6)
            texts.append(text)
            if logo_match(text):
                row["ocr_match"]=True
                break
        if not row["ocr_match"]:
            for part in parts:
                text=extract_text(part, 11)
                texts.append(text)
                if logo_match(text):
                    row["ocr_match"]=True
                    break
        total = " | ".join(t.strip().replace("\n"," ") for t in texts if t.strip())
        row["ocr_excerpt"] = total[:280]
        row["status"] = "ocr_detected" if row["ocr_match"] else "ocr_not_detected"
        # Conservar miniatura de cada imagen para revisión visual manual.
        thumb = ImageOps.contain(img, (230,165))
        thumb_path = BASE / ("thumb-%04d.jpg" % idx)
        thumb.save(thumb_path,quality=69,optimize=True)
        row["thumbnail"]=str(thumb_path)
    except Exception as exc:
        row["status"]="unreadable"
        row["error"]=str(exc)[:180]
    return row

rows=[None]*len(media)
with ThreadPoolExecutor(max_workers=8) as pool:
    futs={pool.submit(examine,i,m):i for i,m in enumerate(media)}
    for num,f in enumerate(as_completed(futs),1):
        i=futs[f]
        rows[i]=f.result()
        if num % 25 == 0 or num==len(media):
            print("OCR_PROGRESS",num,"/",len(media),flush=True)

matched = [x for x in rows if x["ocr_match"]]
unreadable = [x for x in rows if x["status"] not in ("ocr_detected","ocr_not_detected")]
valid = len(rows)-len(unreadable)
summary = {
    "source_products":manifest.get("source_product_count"),
    "associated_products":manifest.get("associated_product_count"),
    "unique_images":len(rows),
    "image_placements":manifest.get("media_placements"),
    "source_original_urls":manifest.get("source_original_urls_count"),
    "downloaded_and_ocr_examined":valid,
    "ocr_latitudes_detected":len(matched),
    "not_ocr_detected_review_needed":sum(x["status"]=="ocr_not_detected" for x in rows),
    "unreadable":len(unreadable),
    "visual_review_required":True,
    "notes":"OCR positivo es indicio de palabra Latitudes visible; negativos no descartan logotipos gráficos."
}
report={"summary":summary,"images":rows,"products":manifest.get("products",[])}
(BASE/"report.json").write_text(json.dumps(report,indent=2,ensure_ascii=False),encoding="utf-8")
with (BASE/"all-images.csv").open("w",newline="",encoding="utf-8") as handle:
    w=csv.writer(handle)
    w.writerow(["attachment_id","url","products","roles","status","ocr_match","ocr_excerpt","error"])
    for x in rows:
        w.writerow([x.get("attachment_id"),x.get("url"),
                    ";".join(map(str,x.get("product_ids",[]))),
                    ";".join(x.get("roles",[])),x["status"],x["ocr_match"],
                    x["ocr_excerpt"],x["error"]])
# Mosaicos de todas las imágenes: 4 columnas, 5 filas/página.
for start in range(0,len(rows),20):
    sheet=Image.new("RGB",(1000,1050),"white")
    draw=ImageDraw.Draw(sheet)
    for n,x in enumerate(rows[start:start+20]):
        col=n%4; line=n//4
        xx=col*250; yy=line*210
        path=x.get("thumbnail")
        if path and Path(path).is_file():
            try:
                with Image.open(path) as thumb:
                    sheet.paste(thumb,(xx+8,yy+10))
            except Exception:
                pass
        short=str(x.get("attachment_id") or "") + " " + str(x.get("filename",""))[:25]
        draw.text((xx+8,yy+177),short,fill="black")
        draw.text((xx+8,yy+191),x["status"],fill="black")
    sheet.save(BASE/("contact-sheet-%03d.jpg"%(1+start//20)),quality=82)
print("LATITUDES_IMAGE_AUDIT_SUMMARY="+json.dumps(summary,ensure_ascii=False),flush=True)
print("LATITUDES_IMAGE_AUDIT_MATCHES="+json.dumps(
    [{"id":x.get("attachment_id"),"url":x.get("url"),
      "product_ids":x.get("product_ids"),"ocr_excerpt":x["ocr_excerpt"]} for x in matched],
    ensure_ascii=False),flush=True)
print("LATITUDES_IMAGE_AUDIT_ERRORS="+json.dumps(
    [{"url":x.get("url"),"error":x.get("error")} for x in unreadable[:40]],
    ensure_ascii=False),flush=True)
