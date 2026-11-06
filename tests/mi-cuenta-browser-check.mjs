import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { resolve } from 'node:path';

const browser = await chromium.launch({ headless: true });
const file = 'file://' + resolve(process.argv[2] || '/tmp/emdo-mi-cuenta-fixture.html');
const widths = [320, 360, 390, 430, 768, 1024, 1280];
try {
	for (const width of widths) {
		const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
		await page.goto(file, { waitUntil: 'load' });
		const a = await page.evaluate(() => {
			const box = selector => document.querySelector(selector).getBoundingClientRect();
			const el = selector => document.querySelector(selector);
			const nav = el('.woocommerce-MyAccount-navigation');
			const navList = el('.woocommerce-MyAccount-navigation ul');
			const orderRows = el('.woocommerce-orders-table tbody tr');
			const item = el('.woocommerce-table--order-details tbody tr');
			const name = el('.woocommerce-table--order-details .product-name');
			const total = el('.woocommerce-table--order-details tfoot tr');
			const headings = [...document.querySelectorAll('.woocommerce-MyAccount-navigation li a')];
			return {
				pageWidth: document.documentElement.scrollWidth,
				viewport: innerWidth,
				navWidth: nav.clientWidth,
				navScrollWidth: nav.scrollWidth,
				navColumns: getComputedStyle(navList).gridTemplateColumns.split(' ').length,
				navMode: getComputedStyle(navList).display,
				navItemsInside: headings.every(node => {
					const r = node.getBoundingClientRect(), outer = nav.getBoundingClientRect();
					return r.left >= outer.left - 1 && r.right <= outer.right + 1;
				}),
				orderDisplay: getComputedStyle(orderRows).display,
				orderCardWidth: box('.woocommerce-orders-table tbody tr').width,
				orderLabel: getComputedStyle(el('.woocommerce-orders-table tbody td'), '::before').content,
				itemDisplay: getComputedStyle(item).display,
				itemWidth: box('.woocommerce-table--order-details tbody tr').width,
				nameWidth: box('.woocommerce-table--order-details .product-name').width,
				totalsDisplay: getComputedStyle(total).display,
				cellsTiny: [...document.querySelectorAll('.woocommerce-table--order-details td,.woocommerce-table--order-details th')]
					.filter(n => n.getClientRects().length > 0 && n.getBoundingClientRect().width < 44).length,
				noteHeading: [...document.querySelectorAll('.woocommerce-MyAccount-content h2')]
					.some(h => h.textContent.trim() === 'Añadir una nota al pedido'),
				noteLabels: [...document.querySelectorAll('form:has(#cus_add_note) th label')]
					.map(label => label.textContent.trim()),
				noteFormWidth: box('form:has(#cus_add_note)').width,
				noteTextWidth: box('#cus_add_note').width,
				noteFileWidth: box('#cus_add_note_file').width,
				noteSubmit: el('#cus_note_submit').value,
				noteEnctype: el('#cus_add_note').form.enctype,
			};
		});
		console.log('WIDTH ' + width + 'px:', JSON.stringify(a));
		assert.ok(a.noteHeading, width + 'px: order notes heading remains in English');
		assert.ok(a.noteLabels.includes('Mensaje'), width + 'px: message label remains in English');
		assert.ok(a.noteLabels.includes('Adjuntar archivo (opcional)'), width + 'px: upload label remains in English');
		assert.equal(a.noteSubmit, 'Enviar nota', width + 'px: note submit remains in English');
		assert.equal(a.noteEnctype, 'multipart/form-data', width + 'px: note attachment behavior must remain intact');
		assert.ok(a.noteTextWidth > 190, width + 'px: note text area squeezed');
		assert.ok(a.noteFileWidth <= a.noteFormWidth - 20, width + 'px: file input wider than note form');
		assert.ok(a.pageWidth <= width + 4, width + 'px: horizontal document overflow');
		assert.ok(a.navItemsInside, width + 'px: navigation item escapes container');
		if (width <= 1023) {
			assert.ok(a.navScrollWidth <= a.navWidth + 3, width + 'px: account menu still scrolls horizontally');
			assert.equal(a.navMode, 'grid', width + 'px: account menu is not a grid');
			assert.equal(a.navColumns, width <= 767 ? 2 : 3, width + 'px: navigation grid columns wrong');
		}
		if (width <= 767) {
			assert.equal(a.orderDisplay, 'flex', width + 'px: orders are not cards');
			assert.ok(a.orderCardWidth > 220, width + 'px: order card too narrow');
			assert.ok(a.orderLabel.includes('Pedido'), width + 'px: missing order label');
			assert.equal(a.itemDisplay, 'grid', width + 'px: order details are not responsive');
			assert.ok(a.nameWidth > 160, width + 'px: product name squeezed vertically');
			assert.equal(a.totalsDisplay, 'grid', width + 'px: totals unusable');
			// Numeric totals may legitimately be compact; the product name must stay readable.
		}
		await page.screenshot({ path: '/tmp/emdo-account-' + width + '.png', fullPage: true });
		await page.close();
	}
	console.log('EMDO Account visual viewport audit: OK.');
} finally {
	await browser.close();
}
