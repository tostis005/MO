from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
import json,time

cases=[
 ("paleta_negra","https://www.elmercadodeorigen.com/producto/paleta-de-bellota-100-iberica-montjam/",["4-45-kg","5-55-kg"]),
 ("jamon_negra","https://www.elmercadodeorigen.com/producto/jamon-de-bellota-100-iberico-montjam/",["75-8-kg","8-85-kg"]),
]
opts=Options(); opts.add_argument("--headless=new"); opts.add_argument("--no-sandbox"); opts.add_argument("--disable-dev-shm-usage"); opts.add_argument("--window-size=1440,1200")
d=webdriver.Chrome(options=opts); d.set_page_load_timeout(60); wait=WebDriverWait(d,15)
out=[]
for key,url,sizes in cases:
 d.get(url+"?diag=20260923"); wait.until(lambda x:x.find_elements(By.CSS_SELECTOR,'select[name="attribute_pa_tamano"]'))
 for size in sizes:
  d.execute_script("""const s=document.querySelector('select[name="attribute_pa_tamano"]');s.value=arguments[0];s.dispatchEvent(new Event('change',{bubbles:true}));""",size)
  time.sleep(2)
  info=d.execute_script("""
   const wanted='FORMATO';
   const all=[...document.querySelectorAll('body *')];
   const exact=all.filter(e=>(e.textContent||'').trim()===wanted);
   const varInput=document.querySelector('input.variation_id');
   const sel=document.querySelector('select[name="attribute_pa_tamano"]');
   function chain(e){
     const a=[]; let n=e; for(let i=0;i<6&&n;i++,n=n.parentElement){
       const st=getComputedStyle(n),r=n.getBoundingClientRect();
       a.push({tag:n.tagName,id:n.id,cls:n.className,display:st.display,visibility:st.visibility,width:r.width,height:r.height,hidden:n.hidden,style:n.getAttribute('style'),data:Object.fromEntries([...n.attributes].filter(x=>x.name.startsWith('data-')).map(x=>[x.name,x.value]))});
     } return a;
   }
   return {
     selected:sel?sel.value:null,
     variation_id:varInput?varInput.value:null,
     exact_count:exact.length,
     exact:exact.map(e=>({outer:e.outerHTML.slice(0,1200),chain:chain(e)})),
     body_has_piece:document.body.innerHTML.includes('Pieza entera'),
     body_has_knife:document.body.innerHTML.includes('Loncheado a cuchillo'),
     body_has_boneless:document.body.innerHTML.includes('Deshuesado'),
     yith_nodes:[...document.querySelectorAll('[class*="yith-wapo"],[id*="yith-wapo"]')].slice(0,40).map(e=>({tag:e.tagName,id:e.id,cls:e.className,style:e.getAttribute('style'),text:(e.innerText||'').slice(0,300)}))
   };
  """)
  out.append({"product":key,"size":size,"info":info})
print("MONTJAM_FORMAT_DOM_DIAG: "+json.dumps(out,ensure_ascii=False))
d.quit()
