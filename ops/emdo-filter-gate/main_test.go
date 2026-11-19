package main

import (
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
  {"/categoria-producto/quesos/curados/?filter_foo=x","category:es:quesos/curados","/categoria-producto/quesos/curados/",1,true,true},
  {"/en/product-category/cheese/aged/page/2?filter_foo=x","category:en:cheese/aged","/en/product-category/cheese/aged/",1,true,true},
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
func TestGateEndToEndScopes(t *testing.T){
 g:=&gate{secret:[]byte(strings.Repeat("test-key",6)),buckets:make(map[string]*visitorBucket),lastGC:time.Now()}
 uri:="/tienda/hidalgo-de-la-jara/?filter_calidad=x"
 req:=httptest.NewRequest("GET","https://www.elmercadodeorigen.com/",nil)
 req.Header.Set("X-Original-URI",uri);req.Header.Set("X-Real-IP","203.0.113.5")
 req.Header.Set("Referer","https://www.elmercadodeorigen.com/tienda/1957/")
 wrongRef:=httptest.NewRecorder();g.handle(wrongRef,req)
 if wrongRef.Code!=403||wrongRef.Header().Get("Location")!="" {t.Fatalf("cross-vendor referrer should be rejected without redirect, got %d",wrongRef.Code)}
 req.Header.Del("Referer")
 direct:=httptest.NewRecorder();g.handle(direct,req)
 if direct.Code!=403||direct.Header().Get("Location")!=""||!strings.Contains(direct.Body.String(),"Ir a la tienda y seleccionar los filtros"){t.Fatalf("direct link must fail closed without redirect or challenge, got %d",direct.Code)}
 req.Header.Set("Referer","https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/")
 chal:=httptest.NewRecorder();g.handle(chal,req)
 if chal.Code!=200||!strings.Contains(chal.Body.String(),"/__emdo_filtergate/verify?scope=vendor%3Ahidalgo-de-la-jara"){t.Fatal("same vendor must receive scoped challenge")}
}
