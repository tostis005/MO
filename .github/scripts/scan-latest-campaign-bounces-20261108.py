#!/usr/bin/env python3
import os,sys,json,email,re,datetime,collections
from email import policy
from email.parser import BytesParser,BytesHeaderParser
from email.utils import parsedate_to_datetime
root='/var/qmail/mailnames/elmercadodeorigen.com/hola/Maildir'
target='Aceite recién molturado: descubre el Lechín y la Manzanilla sin filtrar'
def norm(s): return ' '.join(str(s or '').split()).casefold()
def addr(s):
 s=str(s or '').split(';')[-1].strip().strip('<> ').lower()
 return s if re.fullmatch(r'[^\s<>@]+@[^\s<>@]+\.[^\s<>@]+',s) else ''
counts=collections.Counter();events=[];subjects=collections.Counter();codes=collections.Counter()
for directory,dirs,files in os.walk(root):
 if os.path.basename(directory) not in ('cur','new'): continue
 for name in files:
  path=os.path.join(directory,name)
  try:
   if os.path.islink(path) or os.path.getsize(path)>12000000:continue
   with open(path,'rb') as f: raw=f.read()
   h=BytesHeaderParser(policy=policy.default).parsebytes(raw)
   try:
    dt=parsedate_to_datetime(str(h.get('Date','')))
    if dt.tzinfo is None:dt=dt.replace(tzinfo=datetime.timezone.utc)
    epoch=int(dt.timestamp())
   except Exception:continue
   if epoch<1793232000:continue
   sender=str(h.get('From','')).lower();subj=str(h.get('Subject',''))
   if not ('mailer-daemon' in sender or 'postmaster' in sender or h.get_content_type()=='multipart/report' or re.search('undeliver|delivery|failure|returned mail',subj,re.I)):continue
   counts['recent_notices']+=1
   m=BytesParser(policy=policy.default).parsebytes(raw)
   ss=[];blocks=[]
   for p in m.walk():
    if p is not m and p.get('Subject'):ss.append(str(p.get('Subject')))
    if p.get_content_type()=='text/rfc822-headers':
     hh=BytesHeaderParser(policy=policy.default).parsebytes(p.get_payload(decode=True) or b'')
     if hh.get('Subject'):ss.append(str(hh.get('Subject')))
    if p.get_content_type()=='message/delivery-status':
     blocks.extend(b for b in (p.get_payload() or []) if hasattr(b,'get') and (b.get('Final-Recipient') or b.get('Original-Recipient')))
   matched=any(norm(s)==norm(target) for s in ss)
   if not matched:
    counts['other_or_unidentified_campaign']+=1
    continue
   counts['latest_campaign_notices']+=1
   if not blocks:counts['latest_unstructured']+=1
   for b in blocks:
    a=addr(b.get('Final-Recipient') or b.get('Original-Recipient'))
    action=str(b.get('Action','')).strip().lower();status=str(b.get('Status','')).strip()
    if not a:counts['invalid_recipient']+=1;continue
    codes[action+':'+status]+=1
    events.append({'email':a,'epoch':epoch,'action':action,'status':status})
  except Exception:counts['parse_errors']+=1
out=sys.argv[1]
fd=os.open(out,os.O_WRONLY|os.O_CREAT|os.O_EXCL,0o600)
with os.fdopen(fd,'w') as f:json.dump(events,f)
print('LATEST_DSN_COUNTS='+json.dumps(counts))
print('LATEST_DSN_CODES='+json.dumps(codes))
print('BOUNCE_MAILDIR_SCAN_COMPLETE')
