(() => {
	'use strict';

	const body = document.body;
	const header = document.querySelector('.site-header');

	const updateScrollState = () => {
		const scrolled = window.scrollY > 12;
		body.classList.toggle('is-scrolled', scrolled);
		header?.classList.toggle('is-scrolled', scrolled);
	};

	updateScrollState();
	window.addEventListener('scroll', updateScrollState, { passive: true });

	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	const revealElements = document.querySelectorAll(
		'.emo-reveal, .woocommerce ul.products li.product, .entry-content > .wp-block-group'
	);

	if (!('IntersectionObserver' in window) || revealElements.length === 0) {
		revealElements.forEach((element) => element.classList.add('is-visible'));
		return;
	}

	revealElements.forEach((element) => element.classList.add('emo-reveal'));

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) {
					return;
				}

				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			});
		},
		{ rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
	);

	revealElements.forEach((element) => observer.observe(element));
})();
