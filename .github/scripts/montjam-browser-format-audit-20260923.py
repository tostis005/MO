from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
import json, time, sys

products = [
    ("paleta_negra", "https://www.elmercadodeorigen.com/producto/paleta-de-bellota-100-iberica-montjam/", ["4-45-kg","45-5-kg","5-55-kg","55-6-kg"]),
    ("paleta_negra_dop", "https://www.elmercadodeorigen.com/producto/paleta-bellota-100-iberica-dop-jabugo-montjam/", ["4-45-kg","45-5-kg","5-55-kg","55-6-kg"]),
    ("paleta_verde", "https://www.elmercadodeorigen.com/producto/paleta-cebo-de-campo-iberica-50-montjam/", ["45-5-kg","5-55-kg","55-6-kg"]),
    ("jamon_negra", "https://www.elmercadodeorigen.com/producto/jamon-de-bellota-100-iberico-montjam/", ["6-65-kg","65-7-kg","7-75-kg","75-8-kg","8-85-kg","85-9-kg","9-95-kg"]),
    ("jamon_negra_dop", "https://www.elmercadodeorigen.com/producto/jamon-bellota-100-iberico-dop-jabugo-montjam/", ["6-65-kg","65-7-kg","7-75-kg","75-8-kg","8-85-kg","85-9-kg","9-95-kg"]),
    ("jamon_roja", "https://www.elmercadodeorigen.com/producto/jamon-de-bellota-iberico-50-montjam/", ["7-75-kg","75-8-kg","8-85-kg","85-9-kg","9-95-kg"]),
    ("jamon_verde", "https://www.elmercadodeorigen.com/producto/jamon-cebo-de-campo-iberico-50-montjam/", ["75-8-kg","8-85-kg","85-9-kg","9-95-kg"]),
]

opts = Options()
opts.add_argument("--headless=new")
opts.add_argument("--no-sandbox")
opts.add_argument("--disable-dev-shm-usage")
opts.add_argument("--window-size=1440,1200")
opts.add_argument("--disable-gpu")
driver = webdriver.Chrome(options=opts)
driver.set_page_load_timeout(60)
wait = WebDriverWait(driver, 15)
results = []

def visible_text_exists(txt):
    return driver.execute_script("""
      const target = arguments[0];
      const nodes = [...document.querySelectorAll('body *')];
      return nodes.some(e => {
        const style = getComputedStyle(e);
        const r = e.getBoundingClientRect();
        const vis = style.display !== 'none' && style.visibility !== 'hidden' && r.width > 0 && r.height > 0;
        if (!vis) return false;
        if (e.children.length > 0) return false;
        return (e.textContent || '').trim().includes(target);
      });
    """, txt)

try:
    for key, url, sizes in products:
        driver.get(url + "?audit=20260923")
        wait.until(lambda d: d.find_elements(By.CSS_SELECTOR, 'select[name="attribute_pa_tamano"]'))
        available = driver.execute_script("""
          const s=document.querySelector('select[name="attribute_pa_tamano"]');
          return s ? [...s.options].map(o=>o.value).filter(Boolean) : [];
        """)
        for size in sizes:
            if size not in available:
                results.append({"product":key,"size":size,"status":"FAIL","reason":"weight option missing","url":url})
                continue
            driver.execute_script("""
              const s=document.querySelector('select[name="attribute_pa_tamano"]'), val=arguments[0];
              if (!s) return false;
              s.value=val;
              s.dispatchEvent(new Event('change',{bubbles:true}));
              return true;
            """, size)
            time.sleep(1.5)
            checks = {
                "FORMATO": visible_text_exists("FORMATO"),
                "Pieza entera": visible_text_exists("Pieza entera"),
                "Loncheado a cuchillo": visible_text_exists("Loncheado a cuchillo"),
                "Deshuesado": visible_text_exists("Deshuesado"),
            }
            status = "PASS" if all(checks.values()) else "FAIL"
            results.append({"product":key,"size":size,"status":status,"checks":checks,"url":url})
            # Re-query via JavaScript on each loop to avoid stale DOM references.
finally:
    driver.quit()

fails = [r for r in results if r["status"] != "PASS"]
print("MONTJAM_BROWSER_VARIATION_AUDIT: " + json.dumps({"total":len(results),"fail_count":len(fails),"fails":fails,"results":results}, ensure_ascii=False))
sys.exit(1 if fails else 0)
