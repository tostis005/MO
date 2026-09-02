from pathlib import Path
import base64, gzip, json, hashlib, sys

parts=[]
for n in (1,2):
    s=''.join(Path(f'.github/scripts/authority-gaps-20260902.part{n}.b64').read_text().split())
    bad=[(i,c) for i,c in enumerate(s) if c not in 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=']
    print(f'PART{n}_LEN={len(s)} MOD4={len(s)%4} EQ={s.count("=")} HEAD={s[:24]} TAIL={s[-48:]} BAD={bad[:10]}')
    if '=' in s:
        print(f'PART{n}_FIRST_EQ={s.find("=")} LAST_EQ={s.rfind("=")}')
    parts.append(s)

def try_decode(label, strings, separate=False):
    try:
        if separate:
            raw=b''.join(base64.b64decode(s, validate=True) for s in strings)
        else:
            raw=base64.b64decode(''.join(strings), validate=True)
        print(label+'_BASE64_OK=1 BYTES='+str(len(raw)))
        data=gzip.decompress(raw)
        print(label+'_GZIP_OK=1 JSON_BYTES='+str(len(data)))
        return data
    except Exception as e:
        print(label+'_ERROR='+repr(e))
        return None

raw=try_decode('SEPARATE',parts,True)
mode='separate'
if raw is None:
    raw=try_decode('JOINED',parts,False)
    mode='joined'
if raw is None:
    sys.exit(7)

data=json.loads(raw)
print('MODE='+mode)
print('COUNT='+str(len(data)))
print('SHA256='+hashlib.sha256(raw).hexdigest())
print('KEYS='+','.join(sorted(set().union(*(set(x.keys()) for x in data)))))
for i,a in enumerate(data,1):
    print(f'ITEM={i}|POS={a.get("pos")}|TOPIC={a.get("topic")}|CATEGORY={a.get("category")}|SLUG={a.get("slug")}|EN={a.get("en_slug")}|TITLE={a.get("title")}')
assert len(data)==21
assert len({a['slug'] for a in data})==21
assert len({a['en_slug'] for a in data})==21
