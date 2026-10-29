package main

import (
 "net/http"
 "net/http/httptest"
 "strings"
 "testing"
 "time"
)

func TestScopedTicket(t *testing.T) {
 g:=&gate{secret:[]byte(strings.Repeat("secret-key",5))}
 exp:=time.Now().Add(10*time.Minute).Unix()
 token:=g.ticket("p",strings.Repeat("a",32),exp,"198.51.100.5|ua|vendor:hidalgo-de-la-jara")
 if id,ok:=g.parseTicket(token,"p","198.51.100.5|ua|vendor:hidalgo-de-la-jara");!ok||len(id)!=32 {t.Fatal("valid scoped ticket rejected")}
 if _,ok:=g.parseTicket(token,"p","198.51.100.5|ua|vendor:1957");ok {t.Fatal("token must not authorize other vendor")}
 if _,ok:=g.parseTicket(token,"p","198.51.100.5|ua|category:es:jamones-paletas");ok {t.Fatal("vendor token must not authorize category")}
 if _,ok:=g.parseTicket(token,"p","198.51.100.6|ua|vendor:hidalgo-de-la-jara");ok {t.Fatal("token must be client bound")}
 if _,ok:=g.parseTicket(token,"c","198.51.100.5|ua|vendor:hidalgo-de-la-jara");ok {t.Fatal("challenge and pass kinds must not interchange")}
}
func TestFourComplexRequestsPerMinutePerVisitor(t *testing.T) {
 g:=&gate{buckets:make(map[string]*visitorBucket),lastGC:time.Now()}
 for i:=0;i<4;i++ {if ok,_:=g.reserve("complex:visitor-A",4);!ok {t.Fatalf("valid request %d denied",i+1)}}
 if ok,wait:=g.reserve("complex:visitor-A",4);ok||wait<1 {t.Fatal("fifth request should be denied")}
 if ok,_:=g.reserve("complex:visitor-B",4);!ok {t.Fatal("other visitor must have separate quota")}
 if ok,_:=g.reserve("simple:visitor-A",12);!ok {t.Fatal("simple-filter quota must remain usable")}
}
func TestTargetAndScope(t *testing.T){
 cases:=[]struct{uri,scope,clean string;depth int;filtered bool;ok bool}{
  {"/tienda/hidalgo-de-la-jara/?emo_vendor_cat=x&filter_calidad=x&filter_preparacion=x","vendor:hidalgo-de-la-jara","/tienda/hidalgo-de-la-jara/",2,true,true},
  {"/tienda/1957?filter_calidad=x","vendor:1957","/tienda/1957/",1,true,true},
  {"/en/store/montjam/page/2/?filter_foo=x","vendor:montjam","/en/store/montjam/",1,true,true},
  {"/tienda/page/2/?filter_foo=x","shop:es","/tienda/",1,true,true},
  {"/en/store/?orderby=price","shop:en","/en/store/",0,true,true},
  {"/categoria-producto/jamones-paletas/?min_price=10","category:es:jamones-paletas","/categoria-producto/jamones-paletas/",0,true,true},
  {"/en/product-category/cured-meats/page/2/?filter_foo=x","category:en:cured-meats","/en/product-category/cured-meats/",1,true,true},
  {"/tienda/1957/","vendor:1957","/tienda/1957/",0,false,true},
  {"/tienda/1957/?random=1","vendor:1957","/tienda/1957/",0,false,true},
  {"/producto/foo/?filter_calidad=x","","",0,false,false},
  {"/tienda/1957/page/evil/?filter_foo=x","","",0,false,false},
  {"/tienda/-evil/?filter_foo=x","","",0,false,false},
 }
 for _,tc:=range cases{
  got,ok:=canonicalTarget(tc.uri)
  if ok!=tc.ok {t.Fatalf("%q parsed %v expected %v",tc.uri,ok,tc.ok)}
  if ok&&(got.scope!=tc.scope||got.clean!=tc.clean||got.depth!=tc.depth||got.filtered!=tc.filtered){t.Fatalf("%q got %+v",tc.uri,got)}
 }
}
func TestSameSectionReferrerOnly(t *testing.T){
 tests:=[]struct{target,ref string;allow bool}{
  {"/tienda/hidalgo-de-la-jara/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/",true},
  {"/tienda/hidalgo-de-la-jara/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/page/2/?filter_foo=y",true},
  {"/tienda/1957/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/",false},
  {"/tienda/hidalgo-de-la-jara/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara-evil/",false},
  {"/tienda/hidalgo-de-la-jara/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/",false},
  {"/tienda/hidalgo-de-la-jara/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/montjam/",false},
  {"/tienda/hidalgo-de-la-jara/?filter_foo=x","https://evil.example/tienda/hidalgo-de-la-jara/",false},
  {"/tienda/hidalgo-de-la-jara/?filter_foo=x","",false},
  {"/categoria-producto/jamones-paletas/?filter_foo=x","https://www.elmercadodeorigen.com/categoria-producto/jamones-paletas/",true},
  {"/categoria-producto/jamones-paletas/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/",true},
  {"/categoria-producto/jamones-paletas/?filter_foo=x","https://www.elmercadodeorigen.com/categoria-producto/embutidos-y-curados/",false},
  {"/tienda/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/",true},
  {"/tienda/?filter_foo=x","https://www.elmercadodeorigen.com/tienda/1957/",false},
  {"/en/store/?filter_foo=x","https://www.elmercadodeorigen.com/en/store/",true},
 }
 for _,tc:=range tests{
  tar,ok:=canonicalTarget(tc.target);if !ok{t.Fatal(tc.target)}
  r:=httptest.NewRequest(http.MethodGet,"https://www.elmercadodeorigen.com"+tc.target,nil)
  r.Header.Set("Referer",tc.ref)
  if got:=permittedReferrer(r,tar);got!=tc.allow {t.Errorf("target %q ref %q got %v want %v",tc.target,tc.ref,got,tc.allow)}
 }
}
func TestGateEndToEndScopes(t *testing.T){
 g:=&gate{secret:[]byte(strings.Repeat("test-key",6)),buckets:make(map[string]*visitorBucket),lastGC:time.Now()}
 uri:="/tienda/hidalgo-de-la-jara/?filter_calidad=x"
 req:=httptest.NewRequest("GET","https://www.elmercadodeorigen.com/",nil)
 req.Header.Set("X-Original-URI",uri);req.Header.Set("X-Real-IP","203.0.113.5")
 req.Header.Set("Referer","https://www.elmercadodeorigen.com/tienda/1957/")
 denied:=httptest.NewRecorder();g.handle(denied,req)
 if denied.Code!=302||denied.Header().Get("Location")!="/tienda/hidalgo-de-la-jara/"{t.Fatalf("wrong ref should redirect, got %d %s",denied.Code,denied.Header().Get("Location"))}
 req.Header.Set("Referer","https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/")
 chal:=httptest.NewRecorder();g.handle(chal,req)
 if chal.Code!=200||!strings.Contains(chal.Body.String(),"/__emdo_filtergate/verify?scope=vendor%3Ahidalgo-de-la-jara"){t.Fatal("same vendor must receive scoped challenge")}
}
