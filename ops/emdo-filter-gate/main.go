package main

import (
 "crypto/hmac"
 "crypto/rand"
 "crypto/sha256"
 "encoding/base64"
 "encoding/hex"
 "fmt"
 "html"
 "io"
 "log"
 "net"
 "net/http"
 "net/url"
 "os"
 "strconv"
 "strings"
 "sync"
 "time"
)

const cookieName="emdo_filter_pass"
const gateEndpoint="/__emdo_filtergate/verify"
const internalPrefix="/__emdo_filtergate/upstream"

type visitorBucket struct { timestamps []time.Time; updated time.Time }
type gate struct { secret []byte; mu sync.Mutex; buckets map[string]*visitorBucket; lastGC time.Time }

func sign(secret []byte,data string)string{ h:=hmac.New(sha256.New,secret);h.Write([]byte(data));return base64.RawURLEncoding.EncodeToString(h.Sum(nil)) }
func randomID()string{ b:=make([]byte,16);if _,err:=rand.Read(b);err!=nil{panic(err)};return hex.EncodeToString(b) }
func identity(r *http.Request)string{
 ip:=r.Header.Get("X-Real-IP")
 if net.ParseIP(ip)==nil {host,_,err:=net.SplitHostPort(r.RemoteAddr);if err==nil{ip=host}}
 sum:=sha256.Sum256([]byte(r.UserAgent()))
 return ip+"|"+hex.EncodeToString(sum[:8])
}
func (g *gate)ticket(kind,id string,exp int64,binding string)string{
 base:=kind+"."+id+"."+strconv.FormatInt(exp,10)
 return base+"."+sign(g.secret,base+"|"+binding)
}
func(g *gate)parseTicket(raw,kind,binding string)(string,bool){
 parts:=strings.Split(raw,".")
 if len(parts)!=4||parts[0]!=kind||len(parts[1])!=32{return "",false}
 exp,err:=strconv.ParseInt(parts[2],10,64)
 if err!=nil||exp<time.Now().Unix()||exp>time.Now().Add(time.Hour).Unix(){return "",false}
 base:=strings.Join(parts[:3],".")
 if !hmac.Equal([]byte(parts[3]),[]byte(sign(g.secret,base+"|"+binding))){return "",false}
 return parts[1],true
}
// The token scope is the actual storefront/category, not a blanket site pass.
// A Referer alone is not proof of human navigation: signature + scoped quota +
// Nginx aggregate rate limits are also enforced before Apache/PHP.
type filterTarget struct {
 uri string
 scope string
 clean string
 depth int
 filtered bool
}
func validSlug(s string) bool {
 if len(s)<1||len(s)>120{return false}
 for _,c:=range s {if !((c>='a'&&c<='z')||(c>='0'&&c<='9')||c=='-'){return false}}
 return s[0]!='-' && s[len(s)-1]!='-'
}
func targetFromPath(path string)(scope,clean string,ok bool){
 p:=strings.ToLower(strings.TrimSuffix(path,"/"))
 seg:=strings.Split(strings.Trim(p,"/"),"/")
 if len(seg)==1 && seg[0]=="tienda"{return "shop:es","/tienda/",true}
 if len(seg)==2 && seg[0]=="en" && (seg[1]=="store"||seg[1]=="tienda"){return "shop:en","/en/store/",true}
 base,idx,lang:="",0,"es"
 if len(seg)>=1 && seg[0]=="tienda" {base="store";idx=1
 } else if len(seg)>=2&&seg[0]=="en"&&(seg[1]=="store"||seg[1]=="tienda"){base="store";idx=2;lang="en"
 } else if len(seg)>=1&&seg[0]=="categoria-producto"{base="category";idx=1
 } else if len(seg)>=2&&seg[0]=="en"&&seg[1]=="product-category"{base="category";idx=2;lang="en"
 } else{return "","",false}
 if idx>=len(seg) {return "","",false}
 // A shop pagination URL is not a vendor named 'page'.
 if base=="store"&&seg[idx]=="page" {
  if len(seg)!=idx+2||!validPage(seg[idx+1]){return "","",false}
  return "shop:"+lang,shopRoot(lang),true
 }
 slug:=seg[idx]
 if !validSlug(slug){return "","",false}
 if len(seg)>idx+1 {
  if len(seg)!=idx+3||seg[idx+1]!="page"||!validPage(seg[idx+2]){return "","",false}
 }
 if base=="store"{
  return "vendor:"+slug,shopRoot(lang)+slug+"/",true
 }
 prefix:="/categoria-producto/"
 if lang=="en"{prefix="/en/product-category/"}
 return "category:"+lang+":"+slug,prefix+slug+"/",true
}
func validPage(s string)bool{
 n,e:=strconv.Atoi(s)
 return e==nil&&n>=1&&n<=9999&&strconv.Itoa(n)==s
}
func shopRoot(lang string)string{
 if lang=="en"{return "/en/store/"}
 return "/tienda/"
}
func canonicalTarget(uri string)(filterTarget,bool){
 if len(uri)>4096||!strings.HasPrefix(uri,"/")||strings.HasPrefix(uri,"//"){return filterTarget{},false}
 u,err:=url.ParseRequestURI(uri);if err!=nil{return filterTarget{},false}
 scope,clean,ok:=targetFromPath(u.Path);if !ok{return filterTarget{},false}
 q,err:=url.ParseQuery(u.RawQuery);if err!=nil{return filterTarget{},false}
 depth:=0;filtered:=false
 for k:=range q {
  k=strings.ToLower(k)
  if strings.HasPrefix(k,"filter_"){depth++;filtered=true}
  if strings.HasPrefix(k,"query_type_")||k=="emo_vendor_cat"||k=="min_price"||k=="max_price"||k=="orderby"{filtered=true}
 }
 return filterTarget{uri:uri,scope:scope,clean:clean,depth:depth,filtered:filtered},true
}
func permittedReferrer(r *http.Request,t filterTarget)bool{
 u,err:=url.Parse(r.Referer())
 if err!=nil||u==nil||(u.Scheme!="https"&&u.Scheme!="http")||u.User!=nil{return false}
 hostname:=strings.ToLower(u.Hostname())
 if hostname!="www.elmercadodeorigen.com"&&hostname!="elmercadodeorigen.com"{return false}
 // Do not accept a custom port, a deceptive host, or a different vendor.
 if port:=u.Port();port!=""&&port!="443"&&port!="80"{return false}
 originScope,_,ok:=targetFromPath(u.Path)
 if !ok{return false}
 switch {
 case strings.HasPrefix(t.scope,"vendor:"):
  return originScope==t.scope
 case strings.HasPrefix(t.scope,"category:"):
  // Category filters can be entered from the matching category or shop.
  return originScope==t.scope||strings.HasPrefix(originScope,"shop:")
 case strings.HasPrefix(t.scope,"shop:"):
  return originScope==t.scope
 default:return false
 }
}
func(g *gate)reserve(id string,cap int)(bool,int){
 g.mu.Lock();defer g.mu.Unlock()
 now:=time.Now()
 if now.Sub(g.lastGC)>time.Minute {
  for k,b:=range g.buckets{if now.Sub(b.updated)>25*time.Minute{delete(g.buckets,k)}}
  g.lastGC=now
 }
 b:=g.buckets[id];if b==nil{b=&visitorBucket{};g.buckets[id]=b}
 kept:=b.timestamps[:0]
 for _,t:=range b.timestamps{if now.Sub(t)<time.Minute{kept=append(kept,t)}}
 b.timestamps=kept;b.updated=now
 if len(kept)>=cap{
  sec:=int(time.Minute.Seconds()-now.Sub(kept[0]).Seconds())+1
  if sec<1{sec=1}
  return false,sec
 }
 b.timestamps=append(b.timestamps,now)
 return true,0
}
func issueChallenge(w http.ResponseWriter,r *http.Request,g *gate,t filterTarget){
 c:=g.ticket("c",randomID(),time.Now().Add(75*time.Second).Unix(),identity(r)+"|"+t.scope)
 w.Header().Set("Content-Type","text/html; charset=utf-8")
 w.Header().Set("Cache-Control","no-store, private")
 w.Header().Set("X-Robots-Tag","noindex, nofollow")
 w.Header().Set("Referrer-Policy","same-origin")
 w.Header().Set("X-Content-Type-Options","nosniff")
 fmt.Fprintf(w,`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Verificando navegación</title><style>body{font:16px system-ui,sans-serif;max-width:34rem;margin:12vh auto;padding:1.5rem;color:#282828}a{color:#236c54}</style></head><body><h2>Verificando acceso a los filtros…</h2><p>Esta comprobación automática protege nuestra tienda frente a solicitudes masivas.</p><p id="status">Un momento, por favor.</p><p><a href="%s">Volver a la tienda sin filtros</a></p><script>fetch('%s%s',{credentials:'same-origin',cache:'no-store'}).then(x=>{if(!x.ok)throw Error('No se ha podido validar la sesión');return x.json()}).then(()=>location.replace(%s)).catch(()=>{document.getElementById('status').textContent='No hemos podido verificar la navegación. Vuelve a la tienda para reintentar.'})</script></body></html>`,html.EscapeString(t.clean),gateEndpoint+"?scope="+url.QueryEscape(t.scope)+"&c="+url.QueryEscape(c),"",strconv.Quote(t.uri))
}
func(g *gate)verify(w http.ResponseWriter,r *http.Request){
 w.Header().Set("Cache-Control","no-store, private")
 if r.Method!="GET"{http.Error(w,"Method not allowed",405);return}
 scope:=r.URL.Query().Get("scope")
 _,valid:=g.parseTicket(r.URL.Query().Get("c"),"c",identity(r)+"|"+scope)
 if !valid{http.Error(w,"Invalid navigation challenge",403);return}
 pass:=g.ticket("p",randomID(),time.Now().Add(20*time.Minute).Unix(),identity(r)+"|"+scope)
 http.SetCookie(w,&http.Cookie{Name:cookieName,Value:pass,Path:"/",MaxAge:1200,Secure:true,HttpOnly:true,SameSite:http.SameSiteLaxMode})
 w.Header().Set("Content-Type","application/json; charset=utf-8")
 io.WriteString(w,`{"ok":true}`)
}
func(g *gate)handle(w http.ResponseWriter,r *http.Request){
 if r.URL.Path==gateEndpoint{g.verify(w,r);return}
 if r.Method!="GET"&&r.Method!="HEAD"{http.Error(w,"Method not allowed",405);return}
 t,ok:=canonicalTarget(r.Header.Get("X-Original-URI"))
 if !ok||!t.filtered{http.Error(w,"Invalid filter request",403);return}
 w.Header().Set("Cache-Control","no-store, private")
 c,_:=r.Cookie(cookieName)
 id,valid:="",false
 if c!=nil{id,valid=g.parseTicket(c.Value,"p",identity(r)+"|"+t.scope)}
 if !valid {
  if !permittedReferrer(r,t){http.Redirect(w,r,t.clean,http.StatusFound);return}
  issueChallenge(w,r,g,t);return
 }
 cap,key:=12,"simple:"+id
 if t.depth>=2{cap,key=4,"complex:"+id}
 if allowed,sec:=g.reserve(key,cap);!allowed{
  w.Header().Set("Retry-After",strconv.Itoa(sec))
  w.Header().Set("Content-Type","text/html; charset=utf-8")
  w.Header().Set("X-Robots-Tag","noindex, nofollow")
  w.WriteHeader(429)
  fmt.Fprintf(w,`<!doctype html><html lang="es"><meta charset="utf-8"><title>Demasiadas consultas</title><body style="font:16px system-ui;margin:10vh auto;max-width:35rem;padding:1rem"><h2>Has realizado varias búsquedas seguidas</h2><p>Vuelve a intentarlo en %d segundos, o <a href="%s">consulta la tienda sin filtros</a>.</p></body></html>`,sec,html.EscapeString(t.clean))
  return
 }
 w.Header().Set("X-Accel-Redirect",internalPrefix+t.uri)
 w.WriteHeader(200)
}
func main(){
 secret,err:=os.ReadFile("/etc/emdo-filter-gate/secret")
 if err!=nil||len(secret)<32{log.Fatal("missing secret")}
 g:=&gate{secret:secret,buckets:make(map[string]*visitorBucket),lastGC:time.Now()}
 srv:=&http.Server{Addr:"127.0.0.1:18427",Handler:http.HandlerFunc(g.handle),ReadHeaderTimeout:4*time.Second,ReadTimeout:10*time.Second,WriteTimeout:10*time.Second,IdleTimeout:20*time.Second,MaxHeaderBytes:24<<10}
 log.Print("EMDO local signed filter admission listening")
 log.Fatal(srv.ListenAndServe())
}