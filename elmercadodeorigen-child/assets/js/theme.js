(() => {
	'use strict';

	const body = document.body;
	const header = document.querySelector('.site-header');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let scrollFrame = 0;

	/**
	 * El JavaScript personalizado antiguo intentaba fijar la cabecera con la
	 * clase .fija, añadía un bumper vacío y escribía márgenes inline en #content.
	 * La nueva cabecera usa position: sticky; retiramos únicamente esos efectos.
	 */
	const cleanLegacyHeaderArtifacts = () => {
		const headerInner = document.querySelector('.site-header-inner');
		const oldTopbar = document.querySelector('.topbar');
		const content = document.querySelector('#content');

		headerInner?.classList.remove('fija');
		headerInner?.style.removeProperty('top');
		oldTopbar?.classList.remove('fija');
		content?.style.removeProperty('margin-top');
		document.querySelectorAll('.site-header-inner + .bumper').forEach((bumper) => bumper.remove());
	};

	const updateScrollState = () => {
		const scrolled = window.scrollY > 12;
		body.classList.toggle('is-scrolled', scrolled);
		header?.classList.toggle('is-scrolled', scrolled);
		cleanLegacyHeaderArtifacts();
		scrollFrame = 0;
	};

	const requestScrollUpdate = () => {
		if (scrollFrame) {
			return;
		}

		/* Se ejecuta después de los handlers síncronos del script heredado. */
		scrollFrame = window.requestAnimationFrame(updateScrollState);
	};

	body.classList.add('emo-js-ready');
	updateScrollState();
	window.addEventListener('scroll', requestScrollUpdate, { passive: true });
	window.addEventListener('load', cleanLegacyHeaderArtifacts, { once: true });
	window.setTimeout(cleanLegacyHeaderArtifacts, 300);
	window.setTimeout(cleanLegacyHeaderArtifacts, 1500);

	/*
	 * El mismo JavaScript heredado inserta una segunda .site-title dentro del
	 * enlace de marca. Conservamos sus funciones comerciales, pero retiramos
	 * únicamente ese nodo inválido cuando aparece.
	 */
	const brandingLink = document.querySelector('.site-branding > .site-title > a');
	const cleanDuplicatedBrand = () => {
		brandingLink?.querySelectorAll(':scope > .site-title').forEach((duplicate) => duplicate.remove());
	};

	if (brandingLink) {
		cleanDuplicatedBrand();

		const brandingObserver = new MutationObserver(cleanDuplicatedBrand);
		brandingObserver.observe(brandingLink, { childList: true });
		window.addEventListener('load', cleanDuplicatedBrand, { once: true });
		window.setTimeout(() => {
			cleanDuplicatedBrand();
			brandingObserver.disconnect();
		}, 5000);
	}

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

	/*
	 * Las tarjetas de producto no usan reveal: WooCommerce y varios plugins
	 * cargan imágenes y filas de forma diferida, y ocultarlas hasta observarlas
	 * podía dejar productos invisibles en cargas lentas. Se mantiene el efecto
	 * únicamente en bloques editoriales no transaccionales.
	 */
	const revealElements = document.querySelectorAll([
		'.emo-section-heading',
		'.emo-category-card',
		'.emo-trust article',
		'.emo-story__values article',
		'.emo-vendor-cta__inner',
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
