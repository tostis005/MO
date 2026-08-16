import html, http.cookiejar, re, time, urllib.parse, urllib.request
base='https://www.elmercadodeorigen.com'
jar=http.cookiejar.CookieJar(); opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
opener.addheaders=[('User-Agent','MDO-English-Visible-Text-Audit/3.0')]
def req(path,data=None):
    url=base+path
    payload=urllib.parse.urlencode(data).encode() if isinstance(data,dict) else data
    t=time.monotonic(); r=opener.open(url,data=payload,timeout=45); raw=r.read(); body=raw.decode('utf-8','replace')
    print(('POST' if payload else 'GET'),path,'HTTP',r.status,'SEC',round(time.monotonic()-t,3),'FINAL',r.geturl(),'LEN',len(raw)); return body
def visible(label,body):
    s=re.sub(r'(?is)<script\b.*?</script>|<style\b.*?</style>|<noscript\b.*?</noscript>|<svg\b.*?</svg>',' ',body)
    s=re.sub(r'(?is)<!--.*?-->',' ',s); s=re.sub(r'(?s)<[^>]+>','\n',s); s=html.unescape(s)
    lines=[]
    for line in s.splitlines():
        line=re.sub(r'\s+',' ',line).strip()
        if len(line)<2 or len(line)>240: continue
        if line not in lines: lines.append(line)
    rx=re.compile(r'\b(?:carrito|vac[ií]o|volver|tienda|seguir|comprando|producto|productos|precio|cantidad|subtotal|total|finalizar|compra|pedido|env[ií]o|gastos|direcci[oó]n|facturaci[oó]n|nombre|apellidos|empresa|pa[ií]s|provincia|poblaci[oó]n|c[oó]digo postal|tel[eé]fono|correo|notas|cup[oó]n|aplicar|actualizar|m[eé]todo de pago|transferencia|tarjeta|privacidad|condiciones|aceptar|rechazar|guardar|suscr[ií]bete|descuento|primera compra|introduzca|no gracias|b[uú]squeda|buscar|inicio|productores|contacto|acceder|registro|mi cuenta|cerrar|mostrar|m[ií]nimo|importe|falta|faltan|a[nñ]adir|eliminar|selecci[oó]n|segura|transparente|preparados|cuidado|ventajas|navegaci[oó]n|abrir|idioma|cambiar|disponibles|siempre activado|escribe para buscar)\b',re.I)
    flagged=[x for x in lines if rx.search(x)]
    print('===',label,'SPANISH_OR_SUSPECT_VISIBLE_TEXT',len(flagged),'===')
    for x in flagged[:400]: print(x)
    print('=== END',label,'===')
empty=req('/en/cart/'); visible('EMPTY_CART',empty)
account=req('/en/my-account/'); visible('MY_ACCOUNT',account)
data={'attribute_pa_tamano':'envase-de-5l-de-pet','attribute_pa_variedad':'sin-filtrar-de-martena-picual','add-to-cart':'1056','product_id':'1056','variation_id':'2486','quantity':'1'}
req('/producto/aceite-de-oliva-virgen-extra-5l/',data=data)
filled=req('/en/cart/'); visible('FILLED_CART',filled)
checkout=req('/en/checkout/'); visible('CHECKOUT',checkout)
print('AUDIT_DONE=YES'); print('ORDER_CREATED=NO')
