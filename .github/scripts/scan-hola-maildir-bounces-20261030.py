#!/usr/bin/env python3
"""Inspect Plesk Maildir DSNs. Read-only. No recipients are printed."""
import os,re,sys,json,hashlib,collections,email,datetime
from email.utils import parsedate_to_datetime
from email import policy
from email.parser import BytesHeaderParser,BytesParser
root='/var/qmail/mailnames/elmercadodeorigen.com/hola/Maildir'
folders=['cur','new']
allowed_statuses={'5.1.1','5.1.0','5.1.10','5.2.1'}
emails={}
hist=collections.Counter()
subjects=collections.Counter()
counters=collections.Counter()
samples=collections.defaultdict(list)
header_parser=BytesHeaderParser(policy=policy.default)
def norm(v):
    if not v:return ''
    x=str(v).strip()
    if ';' in x:x=x.split(';',1)[1].strip()
    x=x.strip('<> ').lower()
    return x if re.fullmatch(r'[a-z0-9._%+~-]+@[a-z0-9.-]+\.[a-z]{2,}',x) else ''
for f in folders:
    directory=os.path.join(root,f)
    try: entries=os.scandir(directory)
    except (OSError,PermissionError): counters['maildir_missing_'+f]+=1; continue
    with entries:
      for ent in entries:
        if not ent.is_file(follow_symlinks=False):continue
        counters['total']+=1
        try:
          size=ent.stat().st_size
          if size>12_000_000: counters['large_skipped']+=1;continue
          with open(ent.path,'rb') as fh:
            head=fh.read(40000)
          hdr=header_parser.parsebytes(head.split(b'\n\n',1)[0]+b'\n\n')
          sender=str(hdr.get('From','')).lower()
          subject=str(hdr.get('Subject','')).lower()
          contenttype=str(hdr.get('Content-Type','')).lower()
          returnpath=str(hdr.get('Return-Path','')).strip()
          is_candidate=('mailer-daemon' in sender or 'postmaster' in sender or
                        'delivery status' in subject or 'delivery failure' in subject or
                        'undeliver' in subject or 'failure notice' in subject or
                        'failure' in subject and 'delivered' in subject or
                        'multipart/report' in contenttype or
                        'returned mail' in subject or 'mail delivery' in subject)
          if not is_candidate:continue
          counters['possible_notice']+=1
          if counters['possible_notice']<5:subjects['example-subject:'+subject[:60]]+=1
          if returnpath=='<>':counters['null_return_path']+=1
          with open(ent.path,'rb') as fh:
            msg=BytesParser(policy=policy.default).parse(fh)
          structured=False
          for part in msg.walk():
            if part.get_content_type()!='message/delivery-status':continue
            structured=True
            for block in part.get_payload() or []:
              if not hasattr(block,'get'):continue
              addr=norm(block.get('Final-Recipient') or block.get('Original-Recipient'))
              action=str(block.get('Action','')).lower().strip()
              status=str(block.get('Status','')).strip()
              diag=str(block.get('Diagnostic-Code',''))
              if not addr: continue
              counters['dsn_recipient_blocks']+=1
              hist[status or '(missing)']+=1
              if addr.endswith('@elmercadodeorigen.com'):
                  counters['own_domain_targets']+=1
                  continue
              if action=='failed' and status in allowed_statuses:
                  counters['hard_candidate_events']+=1
                  item=emails.setdefault(addr,{'statuses':set(),'messages':0,'first_ts':None,'last_ts':None,'diagnostics':set()})
                  item['statuses'].add(status)
                  item['messages']+=1
                  try:
                    date=parsedate_to_datetime(str(msg.get('Date','')))
                    if date.tzinfo is None:date=date.replace(tzinfo=datetime.timezone.utc)
                    epoch=int(date.timestamp())
                    if 946684800 <= epoch <= int(datetime.datetime.now(datetime.timezone.utc).timestamp())+172800:
                      if item['first_ts'] is None or epoch<item['first_ts']:item['first_ts']=epoch
                      if item['last_ts'] is None or epoch>item['last_ts']:item['last_ts']=epoch
                  except (ValueError,TypeError,OverflowError):
                    pass
                  item['diagnostics'].add(diag[:110])
              elif action=='failed':
                  counters['other_failure_events']+=1
              elif action=='delayed':
                  counters['temporary_delay_events']+=1
              else:
                  counters['other_dsn_actions']+=1
          if structured:counters['structured_notices']+=1
          else:
              counters['unstructured_notices']+=1
        except Exception as exc:
          counters['parse_error']+=1
          if len(samples['errors'])<4:samples['errors'].append(type(exc).__name__)
# Structured outputs for audit, without personal data. Keep manifest on remote server only.
if len(sys.argv)>1:
    output=sys.argv[1]
    if not output.startswith('/tmp/emdo-bounces-') or not output.endswith('.json'):
        raise SystemExit('Invalid private manifest path')
    data=[{'email':address,'statuses':sorted(v['statuses']),'messages':v['messages'],
           'first_epoch':v['first_ts'],'last_epoch':v['last_ts'],'diagnostics':sorted(v['diagnostics'])}
          for address,v in sorted(emails.items())]
    fd=os.open(output,os.O_WRONLY|os.O_CREAT|os.O_EXCL,0o600)
    with os.fdopen(fd,'w',encoding='utf-8') as outfile:
        json.dump(data,outfile,ensure_ascii=False)
    print('BOUNCE_PRIVATE_MANIFEST_WRITTEN='+str(len(data)))
print('BOUNCE_MAILDIR_COUNTS='+json.dumps(counters,sort_keys=True))
print('BOUNCE_DSN_STATUS_COUNTS='+json.dumps(hist.most_common(40)))
print('BOUNCE_CONFIRMED_UNIQUE='+str(len(emails)))
print('BOUNCE_HARD_COUNTS_BY_CODE='+json.dumps(collections.Counter(s for e in emails.values() for s in e['statuses'])))
print('BOUNCE_MAILDIR_SCAN_COMPLETE')
