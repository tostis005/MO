from pathlib import Path
import base64, gzip, json, hashlib, sys

parts=[]
for n in (1,2):
    s=''.join(Path(f'.github/scripts/authority-gaps-20260902.part{n}.b64').read_text().split())
    bad=[(i,c) for i,c in enumerate(s) if c not in 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=']
    print(f'PART{n}_LEN={len(s)} MOD4={len(s)%4} EQ={s.count("=")} HEAD={s[:24]} TAIL={s[-48:]} BAD={bad[:10]}')
    parts.append(s)

joined=''.join(parts)
padded=joined + ('=' * ((-len(joined)) % 4))
print(f'JOINED_LEN={len(joined)} PADDED_LEN={len(padded)} ADDED_PADDING={len(padded)-len(joined)}')
try:
    raw=base64.b64decode(padded, validate=True)
    print('BASE64_OK=1 BYTES='+str(len(raw)))
    decoded=gzip.decompress(raw)
    print('GZIP_OK=1 JSON_BYTES='+str(len(decoded)))
except Exception as e:
    print('DECODE_ERROR='+repr(e))
    sys.exit(7)

data=json.loads(decoded)
print('COUNT='+str(len(data)))
print('SHA256='+hashlib.sha256(decoded).hexdigest())
print('KEYS='+','.join(sorted(set().union(*(set(x.keys()) for x in data)))))
for i,a in enumerate(data,1):
    print(f'ITEM={i}|POS={a.get("pos")}|TOPIC={a.get("topic")}|CATEGORY={a.get("category")}|SLUG={a.get("slug")}|EN={a.get("en_slug")}|TITLE={a.get("title")}')
assert len(data)==21
assert len({a['slug'] for a in data})==21
assert len({a['en_slug'] for a in data})==21
