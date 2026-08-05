(() => {
	'use strict';

	const body = document.body;
	const header = document.querySelector('.site-header');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let scrollFrame = 0;

	const updateScrollState = () => {
		const scrolled = window.scrollY > 12;
		body.classList.toggle('is-scrolled', scrolled);
		header?.classList.toggle('is-scrolled', scrolled);
		scrollFrame = 0;
	};

	const requestScrollUpdate = () => {
		if (scrollFrame) {
			return;
		}

		scrollFrame = window.requestAnimationFrame(updateScrollState);
	};

	body.classList.add('emo-js-ready');
	updateScrollState();
	window.addEventListener('scroll', requestScrollUpdate, { passive: true });

	const mobileMenuTrigger = document.querySelector('#mobile-trigger, .toggle-sidebar-menu');
	mobileMenuTrigger?.setAttribute('aria-label', 'Abrir menú de navegación');

	const iconLinks = document.querySelectorAll('.site-tools a:not([aria-label])');
	iconLinks.forEach((link) => {
		const title = link.getAttribute('title');
		if (title) {
			link.setAttribute('aria-label', title);
		}
	});

	if (reducedMotion) {
		document.querySelectorAll('.emo-reveal').forEach((element) => element.classList.add('is-visible'));
		return;
	}

	const revealElements = document.querySelectorAll([
		'.emo-section-heading',
		'.emo-category-card',
		'.emo-trust article',
		'.emo-story__values article',
		'.emo-vendor-cta__inner',
		'.woocommerce ul.products li.product',
		'.entry-content > .wp-block-group',
		'.emo-reveal'
	].join(','));

	if (!('IntersectionObserver' in window) || revealElements.length === 0) {
		revealElements.forEach((element) => element.classList.add('is-visible'));
		return;
	}

	revealElements.forEach((element, index) => {
		element.classList.add('emo-reveal');
		element.style.transitionDelay = `${Math.min(index % 6, 5) * 45}ms`;
	});

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
		{ rootMargin: '0px 0px -7% 0px', threshold: 0.08 }
	);

	revealElements.forEach((element) => observer.observe(element));
})();
