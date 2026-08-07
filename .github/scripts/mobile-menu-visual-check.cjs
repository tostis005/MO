const puppeteer = require('puppeteer-core');

const BASE = 'https://dev.elmercadodeorigen.com';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

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
		await page.goto(`${BASE}/?menu-visual=${Date.now()}`, { waitUntil: 'domcontentloaded', timeout: 60000 });
		await sleep(750);

		const header = await page.evaluate(() => {
			const tools = document.querySelector('.site-header .site-tools');
			if (!tools) return null;
			const visible = (node) => {
				if (!node) return false;
				const r = node.getBoundingClientRect();
				const s = getComputedStyle(node);
				return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
			};
			const direct = [...tools.children].filter(visible).slice(0, 3);
			const targets = direct.map((child) => {
				if (child.matches('a,button,[role="button"]')) return child;
				return [...child.querySelectorAll('a,button,[role="button"]')].find(visible) || child;
			});
			const rects = targets.map((node) => node.getBoundingClientRect());
			const centersX = rects.map((r) => r.left + r.width / 2);
			const centersY = rects.map((r) => r.top + r.height / 2);
			const steps = centersX.slice(1).map((x, i) => Math.round(x - centersX[i]));
			const gaps = rects.slice(1).map((r, i) => Math.round(r.left - rects[i].right));
			return {
				count: targets.length,
				widths: rects.map((r) => Math.round(r.width)),
				heights: rects.map((r) => Math.round(r.height)),
				steps,
				gaps,
				ySpread: Math.round(Math.max(...centersY) - Math.min(...centersY)),
				secondCenter: rects[1] ? { x: rects[1].left + rects[1].width / 2, y: rects[1].top + rects[1].height / 2 } : null
			};
		});
		if (!header || header.count !== 3) throw new Error(`header tools missing (${JSON.stringify(header)})`);
		if (Math.max(...header.widths) - Math.min(...header.widths) > 1 || Math.max(...header.heights) - Math.min(...header.heights) > 1) {
			throw new Error(`header tool hit areas differ (${JSON.stringify(header)})`);
		}
		if (header.widths.some((value) => value < 29 || value > 31) || header.heights.some((value) => value < 29 || value > 31)) {
			throw new Error(`header tool hit areas out of range (${JSON.stringify(header)})`);
		}
		if (header.ySpread > 2 || header.steps.some((step) => step < 33 || step > 35) || header.gaps.some((gap) => gap < 3)) {
			throw new Error(`header tool rhythm invalid (${JSON.stringify(header)})`);
		}

		if (header.secondCenter) {
			await page.mouse.move(header.secondCenter.x, header.secondCenter.y);
			await sleep(120);
			await page.screenshot({ path: 'qa/v10-header-tools-hover-mobile.png', clip: { x: 0, y: 0, width: 390, height: 150 } });
		}

		await page.click('.site-header .toggle-sidebar-menu-btn');
		await sleep(350);
		const state = await page.evaluate(() => {
			const drawer = document.querySelector('.sidebar-menu');
			const custom = drawer?.querySelector('.elmercado-mobile-menu-close');
			if (!drawer || !custom) return null;
			const visible = (node) => {
				if (!node) return false;
				const r = node.getBoundingClientRect();
				const s = getComputedStyle(node);
				return r.width > 0 && r.height > 0 && s.display !== 'none' && s.visibility !== 'hidden' && Number(s.opacity) > 0;
			};
			const signature = (node) => `${node.tagName.toLowerCase()}${node.id ? `#${node.id}` : ''}${node.className && typeof node.className === 'string' ? `.${node.className.trim().replace(/\s+/g, '.')}` : ''}`;
			const pseudo = (node, name) => {
				const s = getComputedStyle(node, name);
				return {
					content: s.content,
					display: s.display,
					visibility: s.visibility,
					opacity: s.opacity,
					position: s.position,
					width: s.width,
					height: s.height,
					top: s.top,
					right: s.right,
					bottom: s.bottom,
					left: s.left,
					backgroundImage: s.backgroundImage,
					backgroundColor: s.backgroundColor,
					borderTop: s.borderTop,
					borderRight: s.borderRight,
					borderBottom: s.borderBottom,
					borderLeft: s.borderLeft,
					transform: s.transform,
					fontFamily: s.fontFamily,
					fontSize: s.fontSize,
					color: s.color
				};
			};
			const describe = (node) => {
				const r = node.getBoundingClientRect();
				const s = getComputedStyle(node);
				return {
					signature: signature(node),
					text: (node.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 80),
					aria: node.getAttribute?.('aria-label') || '',
					title: node.getAttribute?.('title') || '',
					rect: { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) },
					display: s.display,
					position: s.position,
					zIndex: s.zIndex,
					before: pseudo(node, '::before'),
					after: pseudo(node, '::after')
				};
			};
			const pointStack = (x, y) => document.elementsFromPoint(x, y).slice(0, 10).map(describe);
			const intersects = (r, box) => r.right >= box.left && r.left <= box.right && r.bottom >= box.top && r.top <= box.bottom;
			const scan = (box) => [...document.querySelectorAll('body *')]
				.filter((node) => {
					const r = node.getBoundingClientRect();
					return r.width > 0 && r.height > 0 && intersects(r, box);
				})
				.slice(0, 80)
				.map(describe);

			const searchRoot = [...drawer.querySelectorAll('.dgwt-wcas-search-wrapp,.aws-container,form.search-form')].find(visible) || null;
			const d = drawer.getBoundingClientRect();
			const c = custom.getBoundingClientRect();
			const s = searchRoot?.getBoundingClientRect();
			const nativeClose = [...document.querySelectorAll('#close-sidebar-menu-btn,#close-sidebar-menu,[id*="close-sidebar"],.close-sidebar-menu-btn,.close-sidebar-menu,.sidebar-menu-close,[class*="close-sidebar"]')]
				.filter((node) => node !== custom && visible(node));
			const searchCandidates = [...drawer.querySelectorAll([
				'.header-search-icon','.search-icon','.site-search-toggle','.search-toggle','.woostify-search-toggle','.toggle-search',
				'[class*="search-toggle"]','[class*="search-icon"]',
				'button[aria-label*="Buscar" i]','a[aria-label*="Buscar" i]','button[aria-label*="Search" i]','a[aria-label*="Search" i]',
				'button[title*="Buscar" i]','a[title*="Buscar" i]','button[title*="Search" i]','a[title*="Search" i]'
			].join(','))].filter((node) => {
				if (!visible(node)) return false;
				if (searchRoot && (searchRoot.contains(node) || node.contains(searchRoot))) return false;
				if (node.closest('.elmercado-mobile-menu-close')) return false;
				return true;
			});
			const edgeX = Math.min(window.innerWidth - 2, Math.round(d.right + 11));
			const edgeY = Math.round(d.top + 35);
			const glassX = Math.round(d.left + 43);
			const glassY = Math.min(window.innerHeight - 2, Math.round(d.top + 469));
			return {
				menu: {
					customVisible: visible(custom),
					closeInside: c.left >= d.left && c.right <= d.right && c.top >= d.top && c.bottom <= d.bottom,
					nativeCloseCount: nativeClose.length,
					nativeCloseSignatures: nativeClose.map(signature),
					searchVisible: Boolean(searchRoot && visible(searchRoot)),
					searchInside: Boolean(s && s.left >= d.left + 3 && s.right <= d.right - 3),
					standaloneSearchCount: searchCandidates.length,
					standaloneSearchSignatures: searchCandidates.map(signature)
				},
				diagnostics: {
					drawer: { x: Math.round(d.x), y: Math.round(d.y), w: Math.round(d.width), h: Math.round(d.height), right: Math.round(d.right) },
					edgePoint: { x: edgeX, y: edgeY, stack: pointStack(edgeX, edgeY) },
					glassPoint: { x: glassX, y: glassY, stack: pointStack(glassX, glassY) },
					edgeRegion: scan({ left: Math.max(0, d.right - 15), right: window.innerWidth, top: 0, bottom: 90 }),
					glassRegion: scan({ left: Math.max(0, d.left + 15), right: Math.min(window.innerWidth, d.left + 90), top: Math.max(0, d.top + 420), bottom: Math.min(window.innerHeight, d.top + 520) })
				}
			};
		});
		if (!state) throw new Error('mobile drawer missing');
		console.log(`MENU_POINT_DIAGNOSTIC ${JSON.stringify(state.diagnostics)}`);
		const menu = state.menu;
		if (!menu.customVisible || !menu.closeInside || menu.nativeCloseCount !== 0 || !menu.searchVisible || !menu.searchInside || menu.standaloneSearchCount !== 0) {
			throw new Error(`mobile drawer visual artifacts remain (${JSON.stringify(menu)})`);
		}
		await page.screenshot({ path: 'qa/v10-menu-visual-mobile.png', fullPage: false });
		console.log(`MENU_VISUAL_OK header=${JSON.stringify(header)} menu=${JSON.stringify(menu)}`);
	} finally {
		await browser.close();
	}
})();
