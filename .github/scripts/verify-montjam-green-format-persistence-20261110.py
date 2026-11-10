from selenium import webdriver
from selenium.webdriver.chrome.options import Options
import json,time,sys
URL='https://www.elmercadodeorigen.com/producto/paleta-cebo-de-campo-iberica-50-montjam/'
opts=Options()
for arg in ['--headless=new','--no-sandbox','--disable-dev-shm-usage','--window-size=1440,1200']:
 opts.add_argument(arg)
def snapshot(driver,label):
 return driver.execute_script("""
   const c=document.querySelector('#yith-wapo-container');
   const select=document.querySelector('select[name="attribute_pa_tamano"]');
   const text=(c?.innerText||'');
   const rect=c?.getBoundingClientRect();
   return {at:arguments[0],size:select?.value,option_values:select?[...select.options].filter(o=>o.value).map(o=>o.value):[],
       container_height:rect?.height,container_text:text.slice(0,500),
       visible:!!(c && rect && rect.height>30 && getComputedStyle(c).display!=='none' && getComputedStyle(c).visibility!=='hidden'),
       has_format:text.includes('FORMATO'),has_whole:text.includes('Pieza entera'),
       has_slice:text.includes('Loncheado a cuchillo'),has_boneless:text.includes('Deshuesado'),
       variation_id:document.querySelector('input.variation_id')?.value};
 """,label)
def valid(s):
 return bool(s['visible'] and s['size']=='6-65-kg' and s['option_values']==['6-65-kg'] and s['variation_id'] not in ['',None,'0'] and
   s['has_format'] and s['has_whole'] and s['has_slice'] and s['has_boneless'])
driver=webdriver.Chrome(options=opts)
driver.set_page_load_timeout(75)
output=[]
try:
 for run in [1,2]:
  driver.get(URL+'?verify-format-persist='+str(run)+'-20261110')
  t=time.monotonic()
  for second in [2,6,12,22]:
   while time.monotonic()-t<second:time.sleep(.2)
   z=snapshot(driver,f'reload-{run}-t{second}')
   output.append(z)
   if not valid(z):raise AssertionError('FORMAT disappeared or not selectable: '+json.dumps(z,ensure_ascii=False))
  driver.execute_script("""const s=document.querySelector('select[name="attribute_pa_tamano"]');s.dispatchEvent(new Event('change',{bubbles:true}));""")
  time.sleep(3)
  z=snapshot(driver,f'reload-{run}-after-change')
  output.append(z)
  if not valid(z):raise AssertionError('FORMAT disappeared after variation change: '+json.dumps(z,ensure_ascii=False))
 print('MONTJAM_FORMAT_BROWSER_PERSISTENCE_OK '+json.dumps(output,ensure_ascii=False))
except Exception as e:
 print('MONTJAM_FORMAT_BROWSER_PERSISTENCE_FAIL '+repr(e)+' OBSERVED '+json.dumps(output,ensure_ascii=False))
 sys.exit(1)
finally:
 driver.quit()
