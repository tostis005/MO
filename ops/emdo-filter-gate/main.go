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
func permittedReferrer(r *http.Request)bool{
 u,err:=url.Parse(r.Referer())
 if err!=nil||(u.Scheme!="https"&&u.Scheme!="http"){return false}
 host:=strings.ToLower(u.Hostname())
 if host!="www.elmercadodeorigen.com"&&host!="elmercadodeorigen.com"{return false}
 p:=strings.ToLower(u.Path)
 for _,prefix:=range []string{"/tienda/","/en/store/","/en/tienda/","/categoria-producto/","/en/product-category/"}{
  if strings.HasPrefix(p,prefix){return true}
 }
 return p=="/tienda"||p=="/en/store"
}
func storeRoot(p string)string{
 slug:="hidalgo-de-la-jara";if strings.Contains(p,"montjam"){slug="montjam"}
 if strings.HasPrefix(p,"/en/"){return "/en/store/"+slug+"/"}
 return "/tienda/"+slug+"/"
}
func canonicalTarget(uri string)(string,bool,int){
 if len(uri)>4096||!strings.HasPrefix(uri,"/")||strings.HasPrefix(uri,"//"){return "",false,0}
 u,err:=url.ParseRequestURI(uri);if err!=nil{return "",false,0}
 p:=strings.ToLower(u.Path)
 allowed:=false
 for _,prefix:=range []string{"/tienda/","/en/store/","/en/tienda/"}{if strings.HasPrefix(p,prefix){allowed=true;break}}
 if !allowed||(!strings.Contains(p,"/hidalgo-de-la-jara/")&&!strings.Contains(p,"/montjam/")){return "",false,0}
 q,err:=url.ParseQuery(u.RawQuery);if err!=nil{return "",false,0}
 count:=0;has:=false
 for k:=range q {if strings.HasPrefix(k,"filter_"){count++;has=true};if k=="emo_vendor_cat"{has=true}}
 return uri,has,count
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
func issueChallenge(w http.ResponseWriter,r *http.Request,g *gate,uri string){
 c:=g.ticket("c",randomID(),time.Now().Add(75*time.Second).Unix(),identity(r))
 w.Header().Set("Content-Type","text/html; charset=utf-8")
 w.Header().Set("Cache-Control","no-store, private")
 w.Header().Set("X-Robots-Tag","noindex, nofollow")
 w.Header().Set("Referrer-Policy","same-origin")
 w.Header().Set("X-Content-Type-Options","nosniff")
 fmt.Fprintf(w,`<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Verificando navegación</title><style>body{font:16px system-ui,sans-serif;max-width:34rem;margin:12vh auto;padding:1.5rem;color:#282828}a{color:#236c54}</style></head><body><h2>Verificando acceso a los filtros…</h2><p>Esta comprobación automática protege nuestra tienda frente a solicitudes masivas.</p><p id="status">Un momento, por favor.</p><p><a href="%s">Volver a la tienda sin filtros</a></p><script>fetch('%s?c=%s',{credentials:'same-origin',cache:'no-store'}).then(x=>{if(!x.ok)throw Error('No se ha podido validar la sesión');return x.json()}).then(()=>location.replace(%s)).catch(()=>{document.getElementById('status').textContent='No hemos podido verificar la navegación. Vuelve a la tienda para reintentar.'})</script></body></html>`,html.EscapeString(storeRoot(uri)),gateEndpoint,c,strconv.Quote(uri))
}
func(g *gate)verify(w http.ResponseWriter,r *http.Request){
 w.Header().Set("Cache-Control","no-store, private")
 if r.Method!="GET"{http.Error(w,"Method not allowed",405);return}
 _,valid:=g.parseTicket(r.URL.Query().Get("c"),"c",identity(r))
 if !valid{http.Error(w,"Invalid navigation challenge",403);return}
 pass:=g.ticket("p",randomID(),time.Now().Add(20*time.Minute).Unix(),identity(r))
 http.SetCookie(w,&http.Cookie{Name:cookieName,Value:pass,Path:"/",MaxAge:1200,Secure:true,HttpOnly:true,SameSite:http.SameSiteLaxMode})
 w.Header().Set("Content-Type","application/json; charset=utf-8")
 io.WriteString(w,`{"ok":true}`)
}
func(g *gate)handle(w http.ResponseWriter,r *http.Request){
 if r.URL.Path==gateEndpoint{g.verify(w,r);return}
 if r.Method!="GET"&&r.Method!="HEAD"{http.Error(w,"Method not allowed",405);return}
 uri,filtered,depth:=canonicalTarget(r.Header.Get("X-Original-URI"))
 if !filtered{http.Error(w,"Invalid filter request",403);return}
 w.Header().Set("Cache-Control","no-store, private")
 c,_:=r.Cookie(cookieName)
 id,valid:="",false
 if c!=nil{id,valid=g.parseTicket(c.Value,"p",identity(r))}
 if !valid {
  if !permittedReferrer(r){http.Redirect(w,r,storeRoot(uri),http.StatusFound);return}
  issueChallenge(w,r,g,uri);return
 }
 cap,key:=12,"simple:"+id
 if depth>=2{cap,key=4,"complex:"+id}
 if allowed,sec:=g.reserve(key,cap);!allowed{
  w.Header().Set("Retry-After",strconv.Itoa(sec))
  w.Header().Set("Content-Type","text/html; charset=utf-8")
  w.Header().Set("X-Robots-Tag","noindex, nofollow")
  w.WriteHeader(429)
  fmt.Fprintf(w,`<!doctype html><html lang="es"><meta charset="utf-8"><title>Demasiadas consultas</title><body style="font:16px system-ui;margin:10vh auto;max-width:35rem;padding:1rem"><h2>Has realizado varias búsquedas seguidas</h2><p>Vuelve a intentarlo en %d segundos, o <a href="%s">consulta la tienda sin filtros</a>.</p></body></html>`,sec,html.EscapeString(storeRoot(uri)))
  return
 }
 w.Header().Set("X-Accel-Redirect",internalPrefix+uri)
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