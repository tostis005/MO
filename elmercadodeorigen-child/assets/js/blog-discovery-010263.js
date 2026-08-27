(() => {
	'use strict';

	document.documentElement.classList.add('js');

	const form = document.querySelector('[data-blog-filter-form="010263"]');
	if (!form) return;

	const hidden = form.querySelector('[data-blog-cats-value]');
	const chips = Array.from(form.querySelectorAll('[data-blog-chip]'));
	const allChip = form.querySelector('[data-blog-all]');

	const current = () => new Set((hidden?.value || '').split(',').map((item) => item.trim()).filter(Boolean));
	const paint = (selected) => {
		chips.forEach((chip) => {
			const active = selected.has(chip.dataset.blogChip);
			chip.classList.toggle('is-active', active);
			chip.setAttribute('aria-pressed', active ? 'true' : 'false');
		});
		if (allChip) {
			allChip.classList.toggle('is-active', selected.size === 0);
			allChip.setAttribute('aria-pressed', selected.size === 0 ? 'true' : 'false');
		}
	};

	chips.forEach((chip) => chip.addEventListener('click', () => {
		const selected = current();
		const slug = chip.dataset.blogChip;
		if (selected.has(slug)) selected.delete(slug);
		else selected.add(slug);
		if (hidden) hidden.value = Array.from(selected).join(',');
		paint(selected);
	}));

	allChip?.addEventListener('click', () => {
		if (hidden) hidden.value = '';
		paint(new Set());
	});

	form.addEventListener('submit', () => {
		const query = form.querySelector('[name="blog_q"]');
		if (query && !query.value.trim()) query.disabled = true;
		if (hidden && !hidden.value.trim()) hidden.disabled = true;
	});

	paint(current());
})();
