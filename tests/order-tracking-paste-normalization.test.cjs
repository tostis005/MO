/**
 * Tests of the actual inline JS shipped by the WooCommerce admin MU-plugin.
 * Run with: node tests/order-tracking-paste-normalization.test.cjs
 * No live orders, web requests or customer messages are sent.
 */
const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");

const source = fs.readFileSync("mu-plugins/emdo-admin-quick-tracking-20261118.php", "utf8");
const script = source.split("<<<'JS'", 2)[1]?.split("\nJS", 1)[0];
assert.ok(script, "Inline tracking JavaScript is present");

function pasteAt({ raw, at, timeZone = "Europe/Madrid", offset = 60, existing = "", select = null }) {
	const listeners = {};
	const textArea = {
		value: existing,
		selectionStart: select ? select[0] : existing.length,
		selectionEnd: select ? select[1] : existing.length,
		addEventListener(name, callback) { listeners[name] = callback; },
		setRangeText(insert, start, end) {
			this.value = this.value.slice(0, start) + insert + this.value.slice(end);
			this.selectionStart = this.selectionEnd = start + insert.length;
		},
		dispatchEvent() { this.inputDispatched = true; }
	};
	const wrapper = {
		addEventListener() {},
		querySelector(selector) { return selector === ".emo-tracking-message" ? textArea : null; }
	};
	const timestamp = Date.parse(at);
	class FakeDate extends Date {
		constructor(...args) { super(...(args.length ? args : [timestamp])); }
		static now() { return timestamp; }
	}
	const globals = {
		window: { elmercadoOrderQuickTracking: { timeZone, utcOffsetMinutes: offset } },
		document: { querySelectorAll() { return [wrapper]; } },
		Date: FakeDate,
		Intl,
		Number,
		Event: class Event {},
		URLSearchParams,
		fetch() { throw new Error("Should not submit while pasting"); }
	};
	vm.runInNewContext(script, globals, { timeout: 1000, filename: "order-tracking-admin-inline.js" });
	assert.equal(typeof listeners.paste, "function", "Paste handler is present");
	const event = {
		clipboardData: { getData(format) { assert.equal(format, "text/plain"); return raw; } },
		preventDefault() { this.prevented = true; }
	};
	listeners.paste(event);
	return { value: textArea.value, prevented: !!event.prevented };
}

const input = '"Buenos días, Francisco Javier Romero Melo,\nTu pedido está en camino.\nEl código es "ABC123".\nGracias."';
const expectedBody = '\nTu pedido está en camino.\nEl código es "ABC123".\nGracias.';

for (const [at, greeting] of [
	["2026-11-18T08:00:00Z", "Buenos días"],
	["2026-11-18T14:00:00Z", "Buenas tardes"],
	["2026-11-18T21:00:00Z", "Buenas noches"],
	["2026-11-18T02:00:00Z", "Buenas noches"],
	["2026-07-01T19:00:00Z", "Buenas noches"]
]) {
	const result = pasteAt({ raw: input, at });
	assert.equal(result.value, greeting + ", Francisco," + expectedBody, "Wrong greeting/name for " + at);
	assert.equal(result.prevented, true);
}

assert.equal(
	pasteAt({ raw: '"Buenas noches María del Carmen López,\r\nSeguimiento 123"', at: "2026-11-18T08:00:00Z" }).value,
	"Buenos días María,\r\nSeguimiento 123"
);
assert.equal(
	pasteAt({ raw: "“Buenas tardes, José Luis Pérez,\nTu compra contiene 2 artículos.”", at: "2026-11-18T08:00:00Z" }).value,
	"Buenos días, José,\nTu compra contiene 2 artículos."
);
assert.equal(
	pasteAt({ raw: '"Código "XY99" y seguimiento 4455"', at: "2026-11-18T08:00:00Z" }).value,
	'Código "XY99" y seguimiento 4455'
);
assert.equal(
	pasteAt({ raw: "Buenas tardes, gracias por tu pedido,\nMás información abajo.", at: "2026-11-18T08:00:00Z" }).value,
	"Buenos días, gracias por tu pedido,\nMás información abajo."
);
assert.equal(
	pasteAt({ raw: '"Buenos días, Francisco Javier Romero Melo,\nAviso"', at: "2026-11-18T14:00:00Z", existing: "Antes: " }).value,
	"Antes: Buenas tardes, Francisco,\nAviso"
);
assert.equal(
	pasteAt({ raw: '"Buenas noches, Ana Fernández,\nHola"', at: "2026-11-18T14:00:00Z", timeZone: "+99:00", offset: 60 }).value,
	"Buenas tardes, Ana,\nHola",
	"Time zone offset fallback"
);
console.log("Quick tracking paste normalization: all tests passed");
