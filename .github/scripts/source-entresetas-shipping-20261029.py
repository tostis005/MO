import requests, re, json
from html import unescape
from urllib.parse import urljoin
base='https://www.entresetas.com/'
session=requests.Session()
session.headers['User-Agent']='Mozilla/5.0 (EMDO price and shipping verification; https://www.elmercadodeorigen.com/)'
r=session.get(base+'product/seta-de-ostra-ecologica/',timeout=35)
print('SOURCE_PRODUCT',r.status_code,r.url,len(r.content),flush=True)
raw=r.text
forms=re.findall(r'<form\b[^>]*\bcart\b[^>]*>',raw,re.I)
print('CART_FORM',forms[:2],flush=True)
items=re.findall(r'<(?:input|button)\b[^>]*(?:add-to-cart|product_id|woocommerce-cart-nonce)[^>]*>',raw,re.I)
print('PRODUCT_FORM_CONTROLS',[x[:300] for x in items[:6]],flush=True)
ids=re.findall(r'name=["\']add-to-cart["\'][^>]*value=["\'](\d+)',raw,re.I)
if not ids:ids=re.findall(r'value=["\'](\d+)["\'][^>]*name=["\']add-to-cart',raw,re.I)
print('ORIGINAL_WOO_IDS',ids,flush=True)
for u in ['wp-json/wc/store/v1/cart','carrito/','cart/','checkout/','finalizar-compra/']:
 try:
  p=session.get(base+u,timeout=28)
  kw=[x for x in ['Envío','Gastos de envío','shipping_method','woocommerce-shipping-calculator'] if x.casefold() in p.text.casefold()]
  print('CHECK_ROUTE',u,p.status_code,p.url,len(p.content),p.headers.get('Content-Type','')[:40],kw,flush=True)
 except Exception as ex:print('ERROR_ROUTE',u,str(ex)[:100],flush=True)
if ids:
 id=ids[0]
 p=session.get(base+'?add-to-cart='+id,timeout=35)
 print('ADD_ANONYMOUS_CART',p.status_code,p.url,flush=True)
 cart=session.get(base+'wp-json/wc/store/v1/cart',timeout=35)
 print('SOURCE_CART_RESULT',cart.status_code,cart.text[:1500],flush=True)
 if cart.status_code==200:
  j=cart.json()
  print('SOURCE_CART_SHIPPING_FIELDS',json.dumps({k:v for k,v in j.items() if 'shipp' in k or 'address' in k or 'needs_' in k},ensure_ascii=False)[:2200],flush=True)
  print('SOURCE_ITEM_TYPES',[(i.get('name'),i.get('quantity'),i.get('prices'),i.get('extensions')) for i in j.get('items',[])][:2],flush=True)
  for route in ['carrito/','finalizar-compra/']:
   pg=session.get(base+route,timeout=25); body=pg.text
   print('AFTER_ADD_ROUTE',route,pg.status_code,pg.url,len(body),flush=True)
   for key in ['shipping_method','woocommerce-shipping-totals','shipping-calculator','cart_totals','Gastos de envío','Envío gratuito','Envío','checkout-button']:
    pos=body.casefold().find(key.casefold())
    if pos>=0:print('AFTER_ADD_MATCH',route,key,re.sub(r'\s+',' ',body[max(0,pos-140):pos+300])[:440],flush=True)

 headers={}
 for k in ['Cart-Token','Nonce']:
  if k in cart.headers:headers[k]=cart.headers[k]
 if cart.status_code==200:
  for country,postal in [('ES','28001'),('ES','34001'),('ES','07001'),('PT','1000-001')]:
   try:
    q=session.post(base+'wp-json/wc/store/v1/cart/update-customer',headers=headers,json={'shipping_address':{'country':country,'postcode':postal},'billing_address':{'country':country,'postcode':postal}},timeout=32)
    j=q.json()
    print('SOURCE_RATE',country,postal,q.status_code,json.dumps({'rates':j.get('shipping_rates'),'total':j.get('totals'),'message':j.get('message')},ensure_ascii=False)[:2200],flush=True)
   except Exception as e:print('SOURCE_RATE_ERROR',country,postal,str(e)[:150],flush=True)
