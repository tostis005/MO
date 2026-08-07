const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const go = async (page, path, delay = 700) => {
	const url = `${BASE}${path}${path.includes('?') ? '&' : '?'}mobile-ui=${Date.now()}`;
	await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
	await sleep(delay);
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
			const rects = children.map((node) => node.getBoundingClientRect());
			const yCenters = rects.map((rect) => rect.top + rect.height / 2);
			const xCenters = rects.map((rect) => rect.left + rect.width / 2);
			const horizontalSteps = xCenters.slice(1).map((value, index) => value - xCenters[index]);
			return {
				brandGap: Math.round(b.left - m.right),
				toolCenterSpread: yCenters.length ? Math.round(Math.max(...yCenters) - Math.min(...yCenters)) : 99,
				toolStepSpread: horizontalSteps.length > 1 ? Math.round(Math.max(...horizontalSteps) - Math.min(...horizontalSteps)) : 99,
				toolSteps: horizontalSteps.map(Math.round),
				toolWidths: rects.map((rect) => Math.round(rect.width)),
				tools: children.length
			};
		});
		if (!header) throw new Error('mobile header structure missing');
		if (header.brandGap > 14) throw new Error(`brand too far from hamburger (${header.brandGap}px)`);
		if (header.toolCenterSpread > 3) throw new Error(`header tools vertically scattered (${header.toolCenterSpread}px)`);
		if (header.toolStepSpread > 3) throw new Error(`header tools unevenly spaced (${JSON.stringify(header.toolSteps)})`);
		if (header.tools < 3) throw new Error(`expected 3 mobile header tools, found ${header.tools}`);
		if (header.toolSteps.some((step) => step < 31 || step > 37)) throw new Error(`header tool steps out of range (${JSON.stringify(header.toolSteps)})`);

		await page.click('.site-header .toggle-sidebar-menu-btn');
		await sleep(300);
		const menuState = await page.evaluate(() => {
			const visible = (node) => {
				if (!node) return false;
				const rect = node.getBoundingClientRect();
				const style = getComputedStyle(node);
				return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0;
			};
			const drawer = document.querySelector('.sidebar-menu');
			const custom = drawer?.querySelector('.elmercado-mobile-menu-close');
			const trigger = document.querySelector('.site-header .toggle-sidebar-menu-btn');
			const tools = document.querySelector('.site-header .site-tools');
			const search = [...(drawer?.querySelectorAll('.dgwt-wcas-search-wrapp,.aws-container,.search-form') || [])].find(visible) || null;
			const natives = [...document.querySelectorAll('.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"]')]
				.filter((node) => node !== custom && visible(node));
			if (!drawer || !custom) return null;
			const d = drawer.getBoundingClientRect();
			const c = custom.getBoundingClientRect();
			const s = search?.getBoundingClientRect();
			return {
				custom: visible(custom),
				trigger: visible(trigger),
				tools: visible(tools),
				nativeCount: natives.length,
				closeInside: c.left >= d.left && c.right <= d.right && c.top >= d.top && c.bottom <= d.bottom,
				searchVisible: Boolean(s && s.width > 0),
				searchInside: Boolean(s && s.left >= d.left + 4 && s.right <= d.right - 4)
			};
		});
		if (!menuState) throw new Error('mobile menu state missing');
		if (!menuState.custom || menuState.trigger || menuState.tools || menuState.nativeCount || !menuState.closeInside || !menuState.searchVisible || !menuState.searchInside) {
			throw new Error(`mobile menu containment invalid (${JSON.stringify(menuState)})`);
		}
		await page.screenshot({ path: 'qa/v10-menu-mobile.png', fullPage: false });
		await page.click('.sidebar-menu .elmercado-mobile-menu-close');
		await sleep(250);
		if (await page.evaluate(() => document.documentElement.classList.contains('sidebar-menu-open'))) {
			throw new Error('explicit mobile menu close did not dismiss drawer');
		}

		await go(page, '/tienda/', 950);
		const shopInitial = await page.evaluate(() => {
			const visible = (node) => {
				if (!node) return false;
				const rect = node.getBoundingClientRect();
				const style = getComputedStyle(node);
				return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
			};
			const lead = document.querySelector('.emo-shop-lead--final');
			const oldLead = [...document.querySelectorAll('.emo-shop-lead:not(.emo-shop-lead--final)')].find(visible) || null;
			const toast = [...document.querySelectorAll('.emo-cart-toast')].find(visible) || null;
			const chevron = document.querySelector('.emo-mobile-filter-toggle .emo-filter-chevron');
			return {
				lead: visible(lead),
				leadText: lead?.textContent?.trim() || '',
				oldLeadVisible: Boolean(oldLead),
				toastVisible: Boolean(toast),
				chevronVisible: visible(chevron)
			};
		});
		if (!shopInitial.lead || !/aceites|ibéricos|fruta/i.test(shopInitial.leadText)) throw new Error(`shop lead invalid (${JSON.stringify(shopInitial)})`);
		if (shopInitial.oldLeadVisible) throw new Error('legacy shop lead still visible');
		if (shopInitial.toastVisible) throw new Error('stale add-to-cart toast visible on shop load');
		if (shopInitial.chevronVisible) throw new Error('mobile filter chevron still visible');

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
		await page.screenshot({ path: 'qa/v10-shop-mobile.png', fullPage: false });
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

		await go(page, '/productores/', 1300);
		const producers = await page.evaluate(() => {
			const visible = (node) => {
				if (!node) return false;
				const rect = node.getBoundingClientRect();
				const style = getComputedStyle(node);
				return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
			};
			const intro = document.querySelector('.emo-producers-intro');
			const sortSelects = [...document.querySelectorAll('#wcfmmp-stores-wrap select')].filter((select) => {
				const signature = `${select.name || ''} ${select.id || ''} ${select.textContent || ''}`.toLowerCase();
				return visible(select) && /(orderby|order|antig|newest|oldest)/.test(signature);
			});
			const style = intro ? getComputedStyle(intro) : null;
			return {
				introVisible: visible(intro),
				introRadius: style ? parseFloat(style.borderTopLeftRadius) || 0 : 0,
				introBackground: style?.backgroundColor || '',
				visibleSorting: sortSelects.length,
				storesRoot: Boolean(document.querySelector('#wcfmmp-stores-wrap'))
			};
		});
		if (!producers.storesRoot || !producers.introVisible || producers.introRadius < 16 || producers.visibleSorting > 0) {
			throw new Error(`producer surface invalid (${JSON.stringify(producers)})`);
		}
		await page.screenshot({ path: 'qa/v10-producers-mobile.png', fullPage: false });

		await go(page, '/blog/', 800);
		const blog = await page.evaluate(() => {
			const primary = document.querySelector('#primary.emo-journal');
			const secondary = document.querySelector('#secondary');
			const hero = document.querySelector('.emo-journal-hero__inner');
			const card = document.querySelector('.emo-article-card');
			const visible = (node) => {
				if (!node) return false;
				const rect = node.getBoundingClientRect();
				const style = getComputedStyle(node);
				return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
			};
			const p = primary?.getBoundingClientRect();
			const hs = hero ? getComputedStyle(hero) : null;
			const cs = card ? getComputedStyle(card) : null;
			return {
				primaryRatio: p ? p.width / window.innerWidth : 0,
				secondaryVisible: visible(secondary),
				heroRadius: hs ? parseFloat(hs.borderTopLeftRadius) || 0 : 0,
				heroBackground: hs?.backgroundColor || '',
				cardRadius: cs ? parseFloat(cs.borderTopLeftRadius) || 0 : null
			};
		});
		if (blog.primaryRatio < 0.95 || blog.secondaryVisible || blog.heroRadius < 16) throw new Error(`blog surface invalid (${JSON.stringify(blog)})`);
		if (blog.cardRadius !== null && blog.cardRadius < 16) throw new Error(`blog cards not rounded (${blog.cardRadius}px)`);
		await page.screenshot({ path: 'qa/v10-blog-mobile.png', fullPage: false });

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
		if (tablet.tabToolbarGap < 12 || tablet.tabToolbarGap > 30) throw new Error(`tablet tabs/toolbar spacing out of range (${tablet.tabToolbarGap}px)`);
		if (tablet.toolbarProductsGap < 6 || tablet.toolbarProductsGap > 22) throw new Error(`tablet toolbar/products spacing out of range (${tablet.toolbarProductsGap}px)`);
		if (tablet.bannerTabsGap !== null && tablet.bannerTabsGap > 70) throw new Error(`tablet vendor header/tabs gap too large (${tablet.bannerTabsGap}px)`);
		if (Math.max(tablet.imageInsetLeft, tablet.imageInsetRight) > 3) throw new Error(`tablet vendor product image still inset (${tablet.imageInsetLeft}/${tablet.imageInsetRight}px)`);
		await page.screenshot({ path: 'qa/v10-vendor-tablet.png', fullPage: false });

		console.log(`MOBILE_UI_OK header=${JSON.stringify(header)} menu=${JSON.stringify(menuState)} shop=${JSON.stringify(shopInitial)} producers=${JSON.stringify(producers)} blog=${JSON.stringify(blog)} vendorGap=${vendorGap}px tablet=${JSON.stringify(tablet)}`);
	} finally {
		await browser.close();
	}
})();
