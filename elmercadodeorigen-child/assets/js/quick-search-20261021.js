(() => {
  'use strict';

  const config = window.elmercadoQuickSearch20261021;
  if (!config || !config.endpoint) return;

  const english = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const copy = english ? {
    heading: 'PRODUCTS',
    all: 'See all results for',
    empty: 'No products found',
    error: 'Suggestions are temporarily unavailable',
    loading: 'Searching products…',
    count: (n) => n === 1 ? '1 suggestion' : n + ' suggestions'
  } : {
    heading: 'PRODUCTOS',
    all: 'Ver todos los resultados de',
    empty: 'No encontramos productos',
    error: 'Las sugerencias no están disponibles ahora',
    loading: 'Buscando productos…',
    count: (n) => n === 1 ? '1 sugerencia' : n + ' sugerencias'
  };
  const lang = english ? 'en' : 'es';
  const cached = new Map();
  const CACHE_MS = 45000;
  const LIMIT = 25;

  function create(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
  }

  function init(root) {
    if (!root || root.dataset.emoQuickSearchBound === '1') return false;

    const form = root.querySelector('form.search-form, form.woocommerce-product-search, form');
    const input = form && form.querySelector('input[type="search"], input.search-field');
    if (!form || !input) return false;
    root.dataset.emoQuickSearchBound = '1';

    const region = create('section', 'emo-quick-search-20261021');
    region.id = 'emo-quick-search-results-20261021';
    region.setAttribute('aria-label', english ? 'Product suggestions' : 'Sugerencias de productos');
    region.hidden = true;
    const live = create('span', 'emo-quick-search__sr');
    live.setAttribute('aria-live', 'polite');
    live.setAttribute('aria-atomic', 'true');
    form.insertAdjacentElement('afterend', region);
    region.insertAdjacentElement('afterend', live);

    input.setAttribute('autocomplete', 'off');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', region.id);
    input.setAttribute('aria-expanded', 'false');

    let timer = null;
    let controller = null;
    let sequence = 0;
    let options = [];
    let active = -1;

    const reset = () => {
      region.hidden = true;
      region.setAttribute('aria-busy', 'false');
      region.replaceChildren();
      options = [];
      active = -1;
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      live.textContent = '';
    };

    const stop = () => {
      clearTimeout(timer);
      if (controller) controller.abort();
      controller = null;
      sequence += 1;
    };

    const fullSearchUrl = (term) => {
      const url = new URL(form.action || location.href, location.href);
      try {
        new FormData(form).forEach((value, key) => {
          if (typeof value === 'string') url.searchParams.set(key, value);
        });
      } catch (_) { /* El formulario sigue funcionando sin enlaces auxiliares. */ }
      url.searchParams.set(input.name || 's', term);
      return url.href;
    };

    const markActive = (index) => {
      active = index;
      options.forEach((link, i) => {
        link.classList.toggle('is-active', i === index);
        link.setAttribute('aria-selected', i === index ? 'true' : 'false');
      });
      if (index >= 0 && options[index]) {
        input.setAttribute('aria-activedescendant', options[index].id);
        options[index].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    };

    const render = (term, products, message) => {
      region.setAttribute('aria-busy', 'false');
      region.replaceChildren();
      options = [];
      active = -1;
      input.removeAttribute('aria-activedescendant');

      const heading = create('div', 'emo-quick-search__heading', copy.heading);
      region.appendChild(heading);

      if (message || products.length === 0) {
        region.appendChild(create('p', 'emo-quick-search__empty', message || copy.empty));
      } else {
        const list = create('ul', 'emo-quick-search__list');
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', heading.textContent);
        products.forEach((product, index) => {
          if (!product || !product.url || !product.name) return;
          const item = create('li', 'emo-quick-search__item');
          item.setAttribute('role', 'presentation');
          const link = create('a', 'emo-quick-search__link');
          link.href = product.url;
          link.id = 'emo-quick-search-option-' + index;
          link.setAttribute('role', 'option');
          link.setAttribute('aria-selected', 'false');

          const image = create('span', 'emo-quick-search__image');
          if (product.image) {
            const img = document.createElement('img');
            img.src = product.image;
            img.alt = '';
            img.width = 56;
            img.height = 56;
            img.loading = 'lazy';
            img.decoding = 'async';
            image.appendChild(img);
          } else {
            image.textContent = 'EMO';
            image.classList.add('is-placeholder');
          }

          const detail = create('span', 'emo-quick-search__detail');
          detail.appendChild(create('span', 'emo-quick-search__name', product.name));
          if (product.category) detail.appendChild(create('span', 'emo-quick-search__category', product.category));
          const price = create('span', 'emo-quick-search__price', product.price || '');
          const arrow = create('span', 'emo-quick-search__arrow', '↗');
          arrow.setAttribute('aria-hidden', 'true');
          link.append(image, detail, price, arrow);
          item.appendChild(link);
          list.appendChild(item);
          options.push(link);
        });
        region.appendChild(list);
      }

      const all = create('a', 'emo-quick-search__all');
      all.href = fullSearchUrl(term);
      all.appendChild(create('span', '', copy.all + ' «' + term + '»'));
      all.appendChild(create('span', '', '→'));
      region.appendChild(all);
      region.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      live.textContent = message || copy.count(options.length);
    };

    const showLoading = (term) => {
      region.replaceChildren();
      options = [];
      active = -1;
      input.removeAttribute('aria-activedescendant');
      region.setAttribute('aria-busy', 'true');

      const heading = create('div', 'emo-quick-search__heading', copy.heading);
      region.appendChild(heading);

      const loading = create('div', 'emo-quick-search__loading');
      loading.setAttribute('role', 'status');
      const spinner = create('span', 'emo-quick-search__spinner');
      spinner.setAttribute('aria-hidden', 'true');
      loading.append(spinner, create('span', 'emo-quick-search__loading-text', copy.loading));
      region.appendChild(loading);

      const skeleton = create('div', 'emo-quick-search__skeletons');
      skeleton.setAttribute('aria-hidden', 'true');
      for (let i = 0; i < 3; i += 1) {
        const row = create('div', 'emo-quick-search__skeleton-row');
        const picture = create('span', 'emo-quick-search__skeleton-image');
        const lines = create('span', 'emo-quick-search__skeleton-lines');
        lines.append(create('span', 'emo-quick-search__skeleton-line'), create('span', 'emo-quick-search__skeleton-line is-short'));
        row.append(picture, lines);
        skeleton.appendChild(row);
      }
      region.appendChild(skeleton);

      const all = create('a', 'emo-quick-search__all');
      all.href = fullSearchUrl(term);
      all.appendChild(create('span', '', copy.all + ' «' + term + '»'));
      all.appendChild(create('span', '', '→'));
      region.appendChild(all);

      region.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      live.textContent = copy.loading;
    };

    const search = async (term, requestId) => {
      const key = lang + ':' + term.toLocaleLowerCase();
      const hit = cached.get(key);
      if (hit && Date.now() - hit.time < CACHE_MS) {
        render(term, hit.products);
        return;
      }

      controller = new AbortController();
      const activeController = controller;
      try {
        const url = new URL(config.endpoint, location.href);
        url.searchParams.set('q', term);
        url.searchParams.set('lang', lang);
        const response = await fetch(url.toString(), {
          method: 'GET',
          credentials: 'same-origin',
          signal: activeController.signal,
          headers: { Accept: 'application/json' }
        });
        if (!response.ok) throw new Error('Search unavailable');
        const result = await response.json();
        if (requestId !== sequence || input.value.trim().replace(/\\s+/g, ' ') !== term) return;
        const products = Array.isArray(result.products) ? result.products.slice(0, 6) : [];
        cached.set(key, { products, time: Date.now() });
        if (cached.size > LIMIT) cached.delete(cached.keys().next().value);
        render(term, products);
      } catch (error) {
        if (error.name === 'AbortError' || requestId !== sequence) return;
        render(term, [], copy.error);
      } finally {
        if (controller === activeController) controller = null;
      }
    };

    const onInput = (event) => {
      if (event.isComposing) return;
      stop();
      reset();
      const term = input.value.trim().replace(/\s+/g, ' ');
      if (Array.from(term).length < 2 || Array.from(term).length > 50) return;
      const current = sequence;
      const key = lang + ':' + term.toLocaleLowerCase();
      const hit = cached.get(key);
      if (hit && Date.now() - hit.time < CACHE_MS) {
        render(term, hit.products);
        return;
      }
      // Indicador inmediato durante debounce y consulta HTTP.
      showLoading(term);
      timer = setTimeout(() => search(term, current), 280);
    };

    input.addEventListener('input', onInput);
    input.addEventListener('focus', () => {
      if (input.value.trim().length >= 2 && region.hidden) onInput({});
    });
    input.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        stop();
        reset();
        return; // Woostify conserva el cierre de su diálogo.
      }
      if (region.hidden || options.length === 0) return;
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        markActive((active + 1) % options.length);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        markActive(active <= 0 ? options.length - 1 : active - 1);
      } else if (event.key === 'Enter' && active >= 0) {
        event.preventDefault();
        location.assign(options[active].href);
      }
    });
    form.addEventListener('submit', stop);
    root.addEventListener('click', (event) => {
      if (event.target.closest('.dialog-search-close-icon')) {
        stop();
        reset();
      }
    });

    return true;
  }

  const attach = () => init(document.querySelector('.site-dialog-search'));
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attach, { once: true });
  } else {
    attach();
  }

  // Compatibilidad con plantillas que insertan el diálogo después del DOM inicial.
  if (!document.querySelector('.site-dialog-search')) {
    const observer = new MutationObserver(() => {
      if (attach()) observer.disconnect();
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
    document.addEventListener('DOMContentLoaded', () => {
      if (document.querySelector('.site-dialog-search')) observer.disconnect();
    }, { once: true });
  }
})();
