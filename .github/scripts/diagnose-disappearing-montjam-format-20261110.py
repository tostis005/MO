from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
import json,time,sys
url="https://www.elmercadodeorigen.com/producto/paleta-cebo-de-campo-iberica-50-montjam/?format-persist-diagnostic=20261110"
opts=Options()
for opt in ["--headless=new","--no-sandbox","--disable-dev-shm-usage","--window-size=1440,1200","--disable-gpu"]:
 opts.add_argument(opt)
opts.set_capability("goog:loggingPrefs",{"browser":"ALL"})
d=webdriver.Chrome(options=opts)
d.set_page_load_timeout(65)
try:
 d.get(url)
 start=time.monotonic()
 points=[0,0.5,2,5,10,17,25]
 def sample(stage):
  return d.execute_script("""
    const c=document.querySelector('#yith-wapo-container');
    const s=document.querySelector('select[name="attribute_pa_tamano"]');
    const vid=document.querySelector('input.variation_id');
    const f=document.querySelector('form.variations_form');
    function info(n){if(!n)return null;const st=getComputedStyle(n),r=n.getBoundingClientRect();return {exists:true,display:st.display,visibility:st.visibility,opacity:st.opacity,rect:{w:r.width,h:r.height},hidden:n.hidden,style:n.getAttribute('style'),classes:n.className};}
    const labels=[...document.querySelectorAll('body *')].filter(e=>e.children.length===0 && (e.textContent||'').trim()==='FORMATO').slice(0,4);
    const addon=[...document.querySelectorAll('[class*="yith-wapo"],[id*="yith-wapo"]')].slice(0,35).map(n=>({id:n.id,cls:typeof n.className==='string'?n.className:'',display:getComputedStyle(n).display,style:n.getAttribute('style')}))
    return {time:arguments[0],url:location.href,ready:document.readyState,select:s?{value:s.value,options:[...s.options].map(o=>({value:o.value,text:o.textContent}))}:null,variationId:vid?.value,form:info(f),container:info(c),containerText:(c?.innerText||'').slice(0,900),containerHTML:(c?.outerHTML||'').slice(0,3000),labels:labels.map(n=>({parent:info(n.parentElement),grandparent:info(n.parentElement?.parentElement)})),addonNodes:addon,bodyHasSlice:document.body.innerHTML.includes('Loncheado a cuchillo')};
  """,stage)
 out=[]
 for p in points:
  while time.monotonic()-start<p:time.sleep(.15)
  out.append(sample(p))
 # Force change event on currently selected valid size, wait 3s
 d.execute_script("""var s=document.querySelector('select[name="attribute_pa_tamano"]');if(s){s.dispatchEvent(new Event('change',{bubbles:true}));}""")
 time.sleep(3)
 out.append(sample("after_change"))
 logs=[x for x in d.get_log("browser") if any(k in x.get('message','').lower() for k in ['error','yith','variation','jqu'])]
 print("GREEN_FORMAT_BROWSER_DIAG "+json.dumps({'samples':out,'console':logs[:25]},ensure_ascii=False))
except Exception as e:
 print("GREEN_BROWSER_ERROR",repr(e));sys.exit(2)
finally:
 d.quit()
