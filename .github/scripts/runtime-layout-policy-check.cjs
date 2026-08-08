const fs = require('fs');
const path = require('path');

const root = path.resolve('elmercadodeorigen-child');
const bootstrap = fs.readFileSync(path.join(root, 'functions.php'), 'utf8');
const loaded = [...bootstrap.matchAll(/['"](inc\/[^'"]+\.php)['"]/g)].map((match) => match[1]);
const layoutRuntime = new Set([
  'inc/shop-filter-breakpoint-final-01044.php',
  'inc/premium-release-01045.php',
  'inc/premium-release-01046.php',
  'inc/premium-visual-system-01048.php',
  'inc/visual-coherence-01067.php',
]);
const failures = [];
const report = {};

for (const rel of loaded) {
  const file = path.join(root, rel);
  if (!fs.existsSync(file)) continue;
  const text = fs.readFileSync(file, 'utf8');
  const rAF = (text.match(/\brequestAnimationFrame\s*\(/g) || []).length;
  const resizeSync = (text.match(/addEventListener\s*\(\s*['"]resize['"]/g) || []).length;
  const globalObserver = /new\s+MutationObserver\s*\(/.test(text) && /\.observe\s*\(\s*document\.body\s*,\s*\{[\s\S]*?subtree\s*:\s*true/.test(text);

  if (layoutRuntime.has(rel) || globalObserver) report[rel] = { requestAnimationFrame: rAF, resizeSync, globalObserver };
  if (layoutRuntime.has(rel) && rAF) failures.push(`${rel}: requestAnimationFrame=${rAF}`);
  if (/filter|premium-release|premium-visual-system/i.test(rel) && resizeSync) failures.push(`${rel}: resize-driven filter layout sync=${resizeSync}`);
  if (globalObserver) failures.push(`${rel}: global MutationObserver(document.body, subtree:true)`);
}

const canonical = fs.readFileSync(path.join(root, 'inc/premium-release-01046.php'), 'utf8');
if (!/matchMedia\(['"]\(max-width:1100px\)['"]\)/.test(canonical)) failures.push('canonical filter controller: missing media-query breakpoint source');
if (!/addEventListener\(['"]change['"],\s*sync\)/.test(canonical)) failures.push('canonical filter controller: missing matchMedia change listener');

fs.mkdirSync('qa', { recursive: true });
fs.writeFileSync('qa/runtime-layout-policy-check.json', JSON.stringify({ loadedModules: loaded.length, report, failures }, null, 2));
if (failures.length) {
  console.error(`RUNTIME_LAYOUT_POLICY_FAIL ${JSON.stringify(failures)}`);
  process.exitCode = 2;
} else {
  console.log(`RUNTIME_LAYOUT_POLICY_OK loadedModules=${loaded.length} ${JSON.stringify(report)}`);
}
