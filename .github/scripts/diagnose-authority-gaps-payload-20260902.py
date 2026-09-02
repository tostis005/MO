from pathlib import Path
import base64, json, hashlib, zlib, sys

parts=[]
for n in (1,2):
    s=''.join(Path(f'.github/scripts/authority-gaps-20260902.part{n}.b64').read_text().split())
    parts.append(s)
joined=''.join(parts)
padded=joined + ('=' * ((-len(joined)) % 4))
raw=base64.b64decode(padded, validate=True)
print(f'BASE64_BYTES={len(raw)}')
obj=zlib.decompressobj(31)
out=b''
try:
    out += obj.decompress(raw)
    out += obj.flush()
except Exception as e:
    print('ZLIB_ERROR='+repr(e))
print(f'DECOMPRESSED_BYTES={len(out)} EOF={obj.eof} UNUSED={len(obj.unused_data)} UNCONSUMED={len(obj.unconsumed_tail)}')
text=out.decode('utf-8',errors='replace')
print('START='+text[:200].replace('\n','\\n'))
print('END='+text[-2000:].replace('\n','\\n'))
try:
    data=json.loads(text)
    print('JSON_COMPLETE=1 COUNT='+str(len(data)))
    for i,a in enumerate(data,1): print(f'ITEM={i}|POS={a.get("pos")}|SLUG={a.get("slug")}|EN={a.get("en_slug")}|TITLE={a.get("title")}')
except Exception as e:
    print('JSON_COMPLETE=0 ERROR='+repr(e))
    # Recover complete top-level objects from an array prefix.
    dec=json.JSONDecoder(); idx=0; items=[]
    while idx < len(text) and text[idx].isspace(): idx+=1
    if idx < len(text) and text[idx]=='[': idx+=1
    while True:
        while idx < len(text) and (text[idx].isspace() or text[idx]==','): idx+=1
        if idx>=len(text) or text[idx]==']': break
        try:
            val,end=dec.raw_decode(text,idx); items.append(val); idx=end
        except Exception as err:
            print('RECOVERY_STOP_INDEX='+str(idx)+' ERROR='+repr(err)); break
    print('RECOVERED_COMPLETE_ITEMS='+str(len(items)))
    for i,a in enumerate(items,1): print(f'RECOVERED={i}|POS={a.get("pos")}|SLUG={a.get("slug")}|EN={a.get("en_slug")}|TITLE={a.get("title")}')
    print('PARTIAL_OBJECT_PREFIX='+text[idx:idx+4000].replace('\n','\\n'))
