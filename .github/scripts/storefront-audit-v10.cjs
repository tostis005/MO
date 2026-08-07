const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const report = { errors: [], warnings: [], checks: {}, runtime: {} };

const ignoredRequest = (request) => {
	const url = request.url();
	const failure = request.failure()?.errorText || '';
	if (/google-analytics|googletagmanager|doubleclick|clarity|facebook|notification\.mp3|fonts\.googleapis|fonts\.gstatic/i.test(url)) return true;
	return /ERR_ABORTED/i.test(failure) && (/wp-admin\/admin-ajax\.php|wc-ajax=/i.test(url) || request.resourceType() === 'document');
};

const attachRuntime = (page, key) => {
	const consoleErrors = [];
	const requestFailures = [];
	page.on('console', (message) => {
		if (message.type() === 'error' && !/favicon|third-party cookie|permissions policy/i.test(message.text())) consoleErrors.push(message.text());
	});
	page.on('requestfailed', (request) => {
		if (!ignoredRequest(request)) requestFailures.push(`${request.url()} :: ${request.failure()?.errorText || 'failed'}`);
	});
	report.runtime[key] = { consoleErrors, requestFailures };
};

const go = async (page, path, label) => {
	const raw = path.startsWith('http') ? path : BASE + path;
	const url = raw + (raw.includes('?') ? '&' : '?') + `qa=${Date.now()}`;
	const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
	await sleep(500);
	if (!response || response.status() >= 400) report.errors.push(`${label}: HTTP ${response?.status() || 'none'}`);
	const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 3).catch(() => false);
	if (overflow) report.errors.push(`${label}: horizontal overflow`);
	return response;
};

const shot = async (page, name) => page.screenshot({ path: `qa/${name}.png`, fullPage: true });
const safe = async (label, fn) => {
	try { await fn(); } catch (error) { report.errors.push(`${label}: ${error.message}`); }
};

const visibleFirst = async (page, selector) => {
	for (const element of await page.$$(selector)) {
		const visible = await element.evaluate((node) => {
			const rect = node.getBoundingClientRect();
			const style = getComputedStyle(node);
			return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
		}).catch(() => false);
		if (visible) return element;
	}
	return null;
};

const auditHeader = async (page, label) => {
	const metrics = await page.evaluate(() => {
		const header = document.querySelector('.site-header');
		const inner = document.querySelector('.site-header-inner');
		const shell = document.querySelector('.site-header-inner > .woostify-container');
		const brand = document.querySelector('.site-branding');
		const tools = document.querySelector('.site-tools');
		if (!header || !inner || !shell || !brand || !tools) return null;
		const h = header.getBoundingClientRect();
		const i = inner.getBoundingClientRect();
		const s = shell.getBoundingClientRect();
		const b = brand.getBoundingClientRect();
		const t = tools.getBoundingClientRect();
		return {
			headerHeight: Math.round(h.height),
			innerHeight: Math.round(i.height),
			shellHeight: Math.round(s.height),
			brandOffset: Math.round(Math.abs(s.top + s.height / 2 - b.top - b.height / 2)),
			toolsOffset: Math.round(Math.abs(s.top + s.height / 2 - t.top - t.height / 2)),
			shellBackground: getComputedStyle(shell).backgroundColor
		};
	});
	if (!metrics) return report.errors.push(`${label}: header structure missing`);
	if (metrics.shellHeight > 68) report.errors.push(`${label}: header shell too tall (${metrics.shellHeight}px)`);
	if (metrics.brandOffset > 6 || metrics.toolsOffset > 6) report.errors.push(`${label}: header contents vertically misaligned`);
	if (!/rgba\(0, 0, 0, 0\)|transparent/.test(metrics.shellBackground)) report.errors.push(`${label}: inner header shell still paints a white strip`);
	report.checks[`${label} header`] = metrics;
};

const auditCookieButton = async (page, label) => {
	const result = await page.evaluate(() => {
		const button = document.querySelector('#cookie_action_close_header');
		if (!button) return null;
		const style = getComputedStyle(button);
		return { display: style.display, alignItems: style.alignItems, justifyContent: style.justifyContent, lineHeight: style.lineHeight };
	});
	if (!result) return report.warnings.push(`${label}: cookie accept button not present in DOM`);
	if (!/flex/.test(result.display) || result.alignItems !== 'center' || result.justifyContent !== 'center') report.errors.push(`${label}: cookie accept text is not centered`);
	report.checks[`${label} cookie`] = result;
};

const auditHome = async (page, mode) => {
	await go(page, '/', `home ${mode}`);
	await shot(page, `v10-home-${mode}`);
	await auditHeader(page, `home ${mode}`);
	await auditCookieButton(page, `home ${mode}`);
	if (mode === 'mobile') {
		const toggle = await visibleFirst(page, '.site-header .toggle-sidebar-menu-btn');
		if (!toggle) return report.errors.push('mobile menu: toggle missing');
		await toggle.click();
		await sleep(250);
		if (!(await page.evaluate(() => document.documentElement.classList.contains('sidebar-menu-open')))) report.errors.push('mobile menu: did not open');
		const close = await visibleFirst(page, '.sidebar-menu .elmercado-mobile-menu-close');
		if (!close) report.errors.push('mobile menu: explicit close control missing');
		else {
			await close.click();
			await sleep(300);
			if (await page.evaluate(() => document.documentElement.classList.contains('sidebar-menu-open'))) report.errors.push('mobile menu: close failed');
		}
	}
};

const auditProducers = async (page, mode) => {
	await go(page, '/productores/', `producers ${mode}`);
	await page.evaluate(() => { window.__emoLongTasks = []; });
	const responsiveness = await page.evaluate(async () => {
		const delays = [];
		for (let i = 0; i < 8; i += 1) {
			const start = performance.now();
			await new Promise((resolve) => setTimeout(resolve, 50));
			delays.push(performance.now() - start - 50);
		}
		return { maxDelay: Math.round(Math.max(...delays)), avgDelay: Math.round(delays.reduce((a, b) => a + b, 0) / delays.length) };
	});
	const directory = await page.evaluate(() => ({
		bodyClass: document.body.classList.contains('wcfmmp-store-list-page'),
		cards: document.querySelectorAll('#wcfmmp-stores-wrap .wcfmmp-single-store, #wcfmmp-stores-lists .wcfmmp-single-store').length,
		storesRoot: Boolean(document.querySelector('#wcfmmp-stores-lists, #wcfmmp-stores-wrap'))
	}));
	report.checks[`producers ${mode}`] = { ...directory, ...responsiveness };
	if (!directory.storesRoot) report.errors.push(`producers ${mode}: WCFM directory root missing`);
	if (responsiveness.maxDelay > 350) report.errors.push(`producers ${mode}: main thread stalls (${responsiveness.maxDelay}ms timer delay)`);
	else if (responsiveness.maxDelay > 180) report.warnings.push(`producers ${mode}: elevated main-thread delay (${responsiveness.maxDelay}ms)`);
	await auditHeader(page, `producers ${mode}`);
	await shot(page, `v10-producers-${mode}`);
};

const auditShop = async (page, mode) => {
	await go(page, '/tienda/', `shop ${mode}`);
	const result = await page.evaluate(() => ({
		products: document.querySelectorAll('ul.products li.product').length,
		ordering: Boolean(document.querySelector('.woocommerce-ordering select')),
		producerFilter: [...document.querySelectorAll('select')].some((select) => {
			const text = [...select.options].map((option) => option.textContent || '').join(' ');
			const rect = select.getBoundingClientRect();
			return /(todos los productores|todos los vendedores)/i.test(text) && rect.width > 0 && rect.height > 0;
		})
	}));
	if (!result.products) report.errors.push(`shop ${mode}: product catalogue missing`);
	if (!result.ordering) report.errors.push(`shop ${mode}: ordering missing`);
	if (result.producerFilter) report.errors.push(`shop ${mode}: producer filter visible`);
	await shot(page, `v10-shop-${mode}`);
};

const auditVendor = async (page, mode) => {
	await go(page, '/tienda/hidalgo-de-la-jara/', `vendor ${mode}`);
	await sleep(300);
	const result = await page.evaluate(() => {
		const count = document.querySelector('.elmercado-vendor-toolbar .woocommerce-result-count');
		const ordering = document.querySelector('.elmercado-vendor-toolbar .woocommerce-ordering');
		const products = document.querySelectorAll('li.product').length;
		if (!count || !ordering) return { products, toolbar: false };
		const a = count.getBoundingClientRect();
		const b = ordering.getBoundingClientRect();
		return { products, toolbar: true, deltaY: Math.round(Math.abs(a.top - b.top)), countHeight: Math.round(a.height), orderHeight: Math.round(b.height) };
	});
	if (!result.products) report.errors.push(`vendor ${mode}: product catalogue missing`);
	if (!result.toolbar) report.errors.push(`vendor ${mode}: normalized result/order toolbar missing`);
	else if (result.deltaY > 3) report.errors.push(`vendor ${mode}: result/order controls misaligned (${result.deltaY}px)`);
	report.checks[`vendor ${mode}`] = result;
	await shot(page, `v10-vendor-${mode}`);
};

const auditCommerce = async (page, mode, simpleProduct) => {
	if (!simpleProduct) return report.warnings.push(`commerce ${mode}: no purchasable simple product available`);
	await go(page, `/contacto/?add-to-cart=${simpleProduct.id}`, `seed cart ${mode}`);
	await go(page, '/carrito/', `cart ${mode}`);
	const cart = await page.evaluate(() => ({
		items: document.querySelectorAll('.woocommerce-cart-form .cart_item, tr.cart_item').length,
		quantity: Boolean(document.querySelector('.woocommerce-cart-form input.qty'))
	}));
	if (!cart.items) report.errors.push(`cart ${mode}: seeded product missing`);
	if (!cart.quantity) report.errors.push(`cart ${mode}: quantity control missing`);
	await shot(page, `v10-cart-${mode}`);
	await go(page, '/finalizar-compra/', `checkout ${mode}`);
	if (!(await page.$('form.checkout,#customer_details,.woocommerce-checkout'))) report.errors.push(`checkout ${mode}: checkout form missing`);
};

const auditBasicContent = async (page, mode) => {
	await go(page, '/contacto/', `contact ${mode}`);
	if (!(await page.$('form.wpcf7-form,.elementor-form,form[id*="contact"]'))) report.errors.push(`contact ${mode}: form missing`);
	await go(page, '/mi-cuenta/', `account ${mode}`);
	if (!(await page.$('form.woocommerce-form-login,input[name="username"],input[name="password"]'))) report.errors.push(`account ${mode}: logged-out form missing`);
};

const runMode = async (browser, mode, viewport, simpleProduct) => {
	const context = await browser.createBrowserContext();
	const page = await context.newPage();
	await page.setViewport(viewport);
	page.setDefaultNavigationTimeout(60000);
	await page.evaluateOnNewDocument(() => {
		window.__emoLongTasks = [];
		try {
			new PerformanceObserver((list) => {
				window.__emoLongTasks.push(...list.getEntries().map((entry) => Math.round(entry.duration)));
			}).observe({ entryTypes: ['longtask'] });
		} catch (_) {}
	});
	attachRuntime(page, mode);
	await safe(`home ${mode}`, () => auditHome(page, mode));
	await safe(`producers ${mode}`, () => auditProducers(page, mode));
	await safe(`shop ${mode}`, () => auditShop(page, mode));
	await safe(`vendor ${mode}`, () => auditVendor(page, mode));
	await safe(`commerce ${mode}`, () => auditCommerce(page, mode, simpleProduct));
	await safe(`content ${mode}`, () => auditBasicContent(page, mode));
	const runtime = report.runtime[mode];
	const consoleErrors = [...new Set(runtime.consoleErrors)];
	const requestFailures = [...new Set(runtime.requestFailures)];
	if (consoleErrors.length) report.errors.push(`${mode}: console errors: ${consoleErrors.slice(0, 3).join(' | ')}`);
	if (requestFailures.length) report.errors.push(`${mode}: request failures: ${requestFailures.slice(0, 3).join(' | ')}`);
	await context.close();
};

(async () => {
	fs.mkdirSync('qa', { recursive: true });
	const products = await fetch(`${BASE}/wp-json/wc/store/v1/products?per_page=100`).then((response) => response.json());
	const simpleProduct = products.find((product) => product.is_purchasable && product.is_in_stock && product.type === 'simple');
	const browser = await puppeteer.launch({
		executablePath: '/usr/bin/google-chrome',
		headless: 'new',
		protocolTimeout: 120000,
		args: ['--no-sandbox', '--disable-dev-shm-usage']
	});
	try {
		await runMode(browser, 'desktop', { width: 1440, height: 1000, deviceScaleFactor: 1 }, simpleProduct);
		await runMode(browser, 'mobile', { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 1 }, simpleProduct);
	} finally {
		await browser.close();
	}
	report.errors = [...new Set(report.errors)];
	report.warnings = [...new Set(report.warnings)];
	fs.writeFileSync('qa/report-v10.json', JSON.stringify(report, null, 2));
	if (report.errors.length) {
		console.error(report.errors.join('\n'));
		process.exitCode = 2;
	} else {
		console.log(`AUDIT_OK warnings=${report.warnings.length}`);
	}
})();
