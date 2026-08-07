const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const go = async (page, path) => {
	const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}mobile-ui=${Date.now()}`;
	await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
	await sleep(650);
};

const visibleCount = async (page, selector) => page.evaluate((query) => [...document.querySelectorAll(query)].filter((node) => {
	const rect = node.getBoundingClientRect();
	const style = getComputedStyle(node);
	return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
}).length, selector);

(async () => {
	const browser = await puppeteer.launch({
		executablePath: '/usr/bin/google-chrome',
		headless: 'new',
		protocolTimeout: 120000,
		args: ['--no-sandbox', '--disable-dev-shm-usage']
	});
	const page = await browser.newPage();
	await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 1 });
	page.setDefaultNavigationTimeout(60000);

	try {
		await go(page, '/');
		const header = await page.evaluate(() => {
			const menu = document.querySelector('.site-header .toggle-sidebar-menu-btn');
			const brand = document.querySelector('.site-header .site-branding');
			const tools = document.querySelector('.site-header .site-tools');
			if (!menu || !brand || !tools) return null;
			const m = menu.getBoundingClientRect();
			const b = brand.getBoundingClientRect();
			const children = [...tools.children].filter((node) => node.getBoundingClientRect().width > 0);
			const centers = children.map((node) => {
				const rect = node.getBoundingClientRect();
				return rect.top + rect.height / 2;
			});
			return {
				brandGap: Math.round(b.left - m.right),
				toolCenterSpread: centers.length ? Math.round(Math.max(...centers) - Math.min(...centers)) : 99,
				tools: children.length
			};
		});
		if (!header) throw new Error('mobile header structure missing');
		if (header.brandGap > 14) throw new Error(`brand too far from hamburger (${header.brandGap}px)`);
		if (header.toolCenterSpread > 3) throw new Error(`header tools vertically scattered (${header.toolCenterSpread}px)`);
		if (header.tools < 3) throw new Error(`expected 3 mobile header tools, found ${header.tools}`);

		await page.click('.site-header .toggle-sidebar-menu-btn');
		await sleep(250);
		const closeCount = await visibleCount(page, '.sidebar-menu .elmercado-mobile-menu-close,.sidebar-menu .close-sidebar-menu-btn,.sidebar-menu .close-sidebar-menu,.sidebar-menu [class*="close-sidebar"]');
		if (closeCount !== 1) throw new Error(`mobile menu shows ${closeCount} visible close controls`);
		await page.screenshot({ path: 'qa/v10-menu-mobile.png', fullPage: false });
		await page.click('.sidebar-menu .elmercado-mobile-menu-close');
		await sleep(250);

		await go(page, '/tienda/');
		const filterToggle = await page.$('.emo-mobile-filter-toggle');
		if (!filterToggle) throw new Error('mobile shop filter toggle missing');
		const positions = await page.evaluate(() => {
			const sorting = document.querySelector('.woostify-sorting');
			const button = document.querySelector('.emo-mobile-filter-toggle');
			if (!sorting || !button) return null;
			const a = sorting.getBoundingClientRect();
			const b = button.getBoundingClientRect();
			return { buttonBelowToolbar: b.top >= a.bottom - 1, gap: Math.round(b.top - a.bottom) };
		});
		if (!positions?.buttonBelowToolbar) throw new Error('mobile filter control is not below result/order toolbar');
		await filterToggle.click();
		await sleep(250);
		const drawer = await page.evaluate(() => {
			const shell = document.querySelector('.emo-mobile-filter-shell');
			const panel = document.querySelector('.emo-mobile-filter-panel');
			const widgets = panel?.querySelectorAll('.widget-area .widget').length || 0;
			const rect = panel?.getBoundingClientRect();
			return {
				open: document.body.classList.contains('emo-shop-filter-open') && shell && !shell.hidden,
				visible: Boolean(rect && rect.width > 0 && rect.height > 0),
				widgets
			};
		});
		if (!drawer.open || !drawer.visible || drawer.widgets < 1) throw new Error(`mobile filter drawer invalid (${JSON.stringify(drawer)})`);
		await page.screenshot({ path: 'qa/v10-shop-filter-mobile.png', fullPage: false });
		await page.click('.emo-mobile-filter-close');
		await sleep(200);

		await go(page, '/tienda/hidalgo-de-la-jara/');
		const vendorGap = await page.evaluate(() => {
			const toolbar = document.querySelector('#wcfmmp-store .elmercado-vendor-toolbar');
			const tabs = document.querySelector('#wcfmmp-store .tab_links');
			if (!toolbar || !tabs) return null;
			const a = tabs.getBoundingClientRect();
			const b = toolbar.getBoundingClientRect();
			return Math.round(b.top - a.bottom);
		});
		if (vendorGap === null) throw new Error('vendor tabs or toolbar missing');
		if (vendorGap < 12) throw new Error(`vendor toolbar too close to tabs (${vendorGap}px)`);
		await page.screenshot({ path: 'qa/v10-vendor-spacing-mobile.png', fullPage: false });

		console.log(`MOBILE_UI_OK headerGap=${header.brandGap}px toolsSpread=${header.toolCenterSpread}px vendorGap=${vendorGap}px`);
	} finally {
		await browser.close();
	}
})();
