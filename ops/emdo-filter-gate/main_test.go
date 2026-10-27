package main

import (
 "net/http"
 "net/http/httptest"
 "strings"
 "testing"
 "time"
)

func TestSignedTicket(t *testing.T) {
 g:=&gate{secret:[]byte(strings.Repeat("secret-key",5))}
 exp:=time.Now().Add(10*time.Minute).Unix()
 token:=g.ticket("p",strings.Repeat("a",32),exp,"198.51.100.5|ua")
 if id,ok:=g.parseTicket(token,"p","198.51.100.5|ua");!ok||len(id)!=32 {t.Fatal("signed pass should validate")}
 if _,ok:=g.parseTicket(token,"p","198.51.100.6|ua");ok {t.Fatal("token must be bound to client")}
 if _,ok:=g.parseTicket(token,"c","198.51.100.5|ua");ok {t.Fatal("challenge and pass kinds must not interchange")}
}
func TestFourComplexRequestsPerMinutePerVisitor(t *testing.T) {
 g:=&gate{buckets:make(map[string]*visitorBucket),lastGC:time.Now()}
 for i:=0;i<4;i++ {if ok,_:=g.reserve("complex:visitor-A",4);!ok {t.Fatalf("valid request %d denied",i+1)}}
 if ok,wait:=g.reserve("complex:visitor-A",4);ok||wait<1 {t.Fatal("fifth request should be denied with retry interval")}
 if ok,_:=g.reserve("complex:visitor-B",4);!ok {t.Fatal("other visitor must have separate quota")}
 if ok,_:=g.reserve("simple:visitor-A",12);!ok {t.Fatal("separate simple-filter bucket must stay usable")}
}
func TestFirstPartyReferrer(t *testing.T) {
 for _,tc:=range []struct{ref string;want bool}{
  {"https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/",true},
  {"https://www.elmercadodeorigen.com/en/store/montjam/",true},
  {"https://www.elmercadodeorigen.com/tienda/",true},
  {"https://www.elmercadodeorigen.com/",false},
  {"https://attacker.example/tienda/hidalgo-de-la-jara/",false},
  {"",false},
 } {
  r:=httptest.NewRequest(http.MethodGet,"https://www.elmercadodeorigen.com/tienda/hidalgo-de-la-jara/?filter_calidad=x",nil)
  if tc.ref!=""{r.Header.Set("Referer",tc.ref)}
  if got:=permittedReferrer(r);got!=tc.want {t.Fatalf("ref=%q got=%v wanted=%v",tc.ref,got,tc.want)}
 }
}
func TestFilterTarget(t *testing.T) {
 for _,tc:=range []struct{uri string;want bool;depth int}{
  {"/tienda/hidalgo-de-la-jara/?emo_vendor_cat=jamones-paletas&filter_calidad=x&filter_preparacion=x",true,2},
  {"/tienda/hidalgo-de-la-jara/?filter_calidad=x",true,1},
  {"/tienda/hidalgo-de-la-jara/",false,0},
  {"/producto/foo/?filter_calidad=x",false,0},
 }{
  _,ok,depth:=canonicalTarget(tc.uri)
  if ok!=tc.want||depth!=tc.depth {t.Fatalf("%q got %v/%d",tc.uri,ok,depth)}
 }
}