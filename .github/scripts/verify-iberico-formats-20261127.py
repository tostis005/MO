import os,re,json,time,unicodedata
from decimal import Decimal, ROUND_HALF_UP
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait,Select
from selenium.common.exceptions import StaleElementReferenceException,NoSuchElementException
from concurrent.futures import ThreadPoolExecutor

products=json.load(open('/tmp/iberico-manifest.json'))
def norm(s):
    return ' '.join(''.join(c for c in unicodedata.normalize('NFD',s.lower()) if unicodedata.category(c)!='Mn').split())
def money(x):
    return Decimal(str(x)).quantize(Decimal('.01'),rounding=ROUND_HALF_UP)
def snapshot(d):
    return d.execute_script("""
    const c=document.querySelector('#yith-wapo-container');
    const visible=e=>!!(e&&e.getClientRects().length&&getComputedStyle(e).visibility!=='hidden');
    const s=[...document.querySelectorAll('#yith-wapo-container select')].find(x=>visible(x)&&[...x.options].some(o=>/lonche/i.test(o.textContent)));
    const b=[...document.querySelectorAll('form.cart .single_add_to_cart_button')].find(visible);
    return {text:visible(c)?c.innerText:'',selected:s?.selectedOptions[0]?.textContent||'',variation:document.querySelector('input.variation_id')?.value||'',button:!!b&&!b.disabled&&!b.classList.contains('disabled')};
    """)
def choose(d,label):
    for e in d.find_elements(By.CSS_SELECTOR,'#yith-wapo-container select'):
        if not e.is_displayed() or not e.is_enabled():continue
        for o in Select(e).options:
            if norm(o.text).startswith(norm(label)) and o.is_enabled():
                Select(e).select_by_value(o.get_attribute('value'))
                return True
    return False
def total(s):
    m=re.findall(r'(?m)^\s*(\d+(?:\.\d{3})*,\d{2})\s*€\s*$',s['text'])
    return Decimal(m[-1].replace('.','').replace(',','.')) if m else None
def check_product(p):
    opt=Options()
    for arg in ['--headless=new','--no-sandbox','--disable-dev-shm-usage','--lang=es-ES','--window-size=1440,1200']:opt.add_argument(arg)
    d=webdriver.Chrome(options=opt);d.set_page_load_timeout(70)
    w=WebDriverWait(d,18,ignored_exceptions=(StaleElementReferenceException,NoSuchElementException))
    errors=[];count=0
    try:
        for v in p['variants']:
            if not v['purchasable']:continue
            size=v['size']
            try:
                d.get(p['url']+(' &' if '?' in p['url'] else '?').strip()+'lang=es&format_audit='+os.environ['GITHUB_RUN_ID']+'&weight='+size)
                sel='select[name="attribute_pa_tamano"]'
                w.until(lambda d:d.find_elements(By.CSS_SELECTOR,sel))
                d.execute_script('const s=document.querySelector(arguments[0]);s.value=arguments[1];s.dispatchEvent(new Event("change",{bubbles:true}));',sel,size)
                time.sleep(4)
                for o in p['options']:
                    if str(o['enabled']).lower() in ('no','0','false'):continue
                    try:
                        w.until(lambda d:choose(d,o['label']))
                        time.sleep(3)
                        s=snapshot(d)
                        assert s['variation']==str(v['id']) and s['button'] and norm(s['selected']).startswith(norm(o['label'])), 'Format not selected or variation unavailable'
                        price=total(s)
                        assert price is not None,'No standalone visible final total'
                        method=o['method']; typ=o['type']
                        raw=o['sale'] if o['sale'] not in ('',None) else o['price']
                        if method=='free':expected=money(v['display_base'])
                        elif method=='increase' and typ=='fixed' and raw not in ('',None):expected=money(Decimal(str(v['display_base']))+Decimal(str(raw)))
                        else:raise AssertionError('Unsupported pricing rule; needs manual review '+str(o))
                        assert abs(price-expected)<=Decimal('.01'), 'Displayed '+str(price)+' expected '+str(expected)
                        count+=1
                        print('IBERICO_FORMAT_OK '+json.dumps(dict(id=p['id'],size=size,label=o['label'],configured=raw,total=str(price)),ensure_ascii=False),flush=True)
                    except Exception as e:
                        errors.append([p['id'],size,o['label'],repr(e),snapshot(d)])
                        print('IBERICO_FORMAT_FAIL '+json.dumps(errors[-1],ensure_ascii=False),flush=True)
            except Exception as e:
                errors.append([p['id'],size,repr(e)])
                print('IBERICO_WEIGHT_FAIL '+json.dumps(errors[-1],ensure_ascii=False),flush=True)
    finally:d.quit()
    return count,errors
count=0;errors=[]
for p in sorted(products,key=lambda p:(p['id']!=4160,p['vendor'],p['id'])):
    c,e=check_product(p);count+=c;errors+=e
print('IBERICO_BROWSER_SUMMARY '+json.dumps(dict(products=len(products),successful_formats=count,errors=errors),ensure_ascii=False),flush=True)
if errors:raise SystemExit(1)
