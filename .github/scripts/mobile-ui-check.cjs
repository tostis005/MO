const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const go = async (page, path) => {
	const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}mobile-ui=${Date.now()}`;
	await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
	await sleep(650);
};

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
			const children = [...tools.children].filter((node) => {
				const rect = node.getBoundingClientRect();
				return rect.width > 0 && rect.height > 0;
			});
			const yCenters = children.map((node) => {
				const rect = node.getBoundingClientRect();
				return rect.top + rect.height / 2;
			});
			const xCenters = children.map((node) => {
				const rect = node.getBoundingClientRect();
				return rect.left + rect.width / 2;
			});
			const horizontalSteps = xCenters.slice(1).map((value, index) => value - xCenters[index]);
			return {
				brandGap: Math.round(b.left - m.right),
				toolCenterSpread: yCenters.length ? Math.round(Math.max(...yCenters) - Math.min(...yCenters)) : 99,
				toolStepSpread: horizontalSteps.length > 1 ? Math.round(Math.max(...horizontalSteps) - Math.min(...horizontalSteps)) : 99,
				toolSteps: horizontalSteps.map(Math.round),
				tools: children.length
			};
		});
		if (!header) throw new Error('mobile header structure missing');
		if (header.brandGap > 14) throw new Error(`brand too far from hamburger (${header.brandGap}px)`);
		if (header.toolCenterSpread > 3) throw new Error(`header tools vertically scattered (${header.toolCenterSpread}px)`);
		if (header.toolStepSpread > 3) throw new Error(`header tools unevenly spaced (${JSON.stringify(header.toolSteps)})`);
		if (header.tools < 3) throw new Error(`expected 3 mobile header tools, found ${header.tools}`);

		await page.click('.site-header .toggle-sidebar-menu-btn');
		await sleep(250);
		const closeState = await page.evaluate(() => {
			const isVisible = (node) => {
				if (!node) return false;
				const rect = node.getBoundingClientRect();
				const style = getComputedStyle(node);
				return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
			};
			const custom = document.querySelector('.sidebar-menu .elmercado-mobile-menu-close');
			const trigger = document.querySelector('.site-header .toggle-sidebar-menu-btn');
			const natives = [...document.querySelectorAll('.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"]')]
				.filter((node) => node !== custom && isVisible(node));
			return { custom: isVisible(custom), trigger: isVisible(trigger), nativeCount: natives.length };
		});
		if (!closeState.custom || closeState.trigger || closeState.nativeCount) {
			throw new Error(`mobile menu close state invalid (${JSON.stringify(closeState)})`);
		}
		await page.screenshot({ path: 'qa/v10-menu-mobile.png', fullPage: false });
		await page.click('.sidebar-menu .elmercado-mobile-menu-close');
		await sleep(300);
		const menuStillOpen = await page.evaluate(() => document.documentElement.classList.contains('sidebar-menu-open'));
		if (menuStillOpen) throw new Error('explicit mobile menu close did not dismiss drawer');

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

		/* La captura del usuario revela el breakpoint intermedio; lo auditamos explícitamente. */
		await page.setViewport({ width: 768, height: 960, isMobile: true, hasTouch: true, deviceScaleFactor: 1 });
		await go(page, '/tienda/hidalgo-de-la-jara/');
		const tablet = await page.evaluate(() => {
			const banner = document.querySelector('#wcfmmp-store .banner_area,#wcfmmp-store .store_info_parallal,#wcfmmp-store .store_info');
			const tabs = document.querySelector('#wcfmmp-store .tab_links');
			const toolbar = document.querySelector('#wcfmmp-store .elmercado-vendor-toolbar');
			const product = document.querySelector('#wcfmmp-store ul.products li.product');
			const image = product?.querySelector('.product-loop-image-wrapper img,img.product-loop-image,.woocommerce-loop-product__link > img');
			if (!tabs || !toolbar || !product || !image) return null;
			const t = tabs.getBoundingClientRect();
			const w = toolbar.getBoundingClientRect();
			const p = product.getBoundingClientRect();
			const i = image.getBoundingClientRect();
			const b = banner?.getBoundingClientRect();
			return {
				bannerTabsGap: b ? Math.round(t.top - b.bottom) : null,
				tabToolbarGap: Math.round(w.top - t.bottom),
				toolbarProductsGap: Math.round(p.top - w.bottom),
				imageInsetLeft: Math.round(i.left - p.left),
				imageInsetRight: Math.round(p.right - i.right)
			};
		});
		if (!tablet) throw new Error('tablet vendor geometry missing');
		if (tablet.tabToolbarGap < 12 || tablet.tabToolbarGap > 30) {
			throw new Error(`tablet tabs/toolbar spacing out of range (${tablet.tabToolbarGap}px)`);
		}
		if (tablet.toolbarProductsGap < 6 || tablet.toolbarProductsGap > 22) {
			throw new Error(`tablet toolbar/products spacing out of range (${tablet.toolbarProductsGap}px)`);
		}
		if (tablet.bannerTabsGap !== null && tablet.bannerTabsGap > 70) {
			throw new Error(`tablet vendor header/tabs gap too large (${tablet.bannerTabsGap}px)`);
		}
		if (Math.max(tablet.imageInsetLeft, tablet.imageInsetRight) > 3) {
			throw new Error(`tablet vendor product image still inset (${tablet.imageInsetLeft}/${tablet.imageInsetRight}px)`);
		}
		await page.screenshot({ path: 'qa/v10-vendor-tablet.png', fullPage: false });

		console.log(`MOBILE_UI_OK headerGap=${header.brandGap}px toolsSpread=${header.toolCenterSpread}px toolStepSpread=${header.toolStepSpread}px vendorGap=${vendorGap}px tablet=${JSON.stringify(tablet)} close=ok`);
	} finally {
		await browser.close();
	}
})();
