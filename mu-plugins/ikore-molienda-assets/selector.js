/* Visual grind selector: progressive enhancement over WooCommerce's real select. */
(function ($) {
  'use strict';
  var config = window.EMDOIkoreMolienda;
  if (!config || !$) { return; }

  function normal(value) {
    return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase().replace(/[^a-z0-9]/g, '');
  }
  function typeFor(value) {
    var v = normal(value);
    if (/^(engrano|grano|wholebeans?|beans?|coffeebeans?)$/.test(v)) { return 'grano'; }
    if (/italiana|moka/.test(v)) { return 'italiana'; }
    if (/espresso|expresso/.test(v)) { return 'espresso'; }
    if (/prensafrancesa|frenchpress|cafetiere/.test(v)) { return 'francesa'; }
    if (/coldbrew|extraccionenfrio/.test(v)) { return 'cold-brew'; }
    if (/aeropress/.test(v)) { return 'aeropress'; }
    if (/filtro|v60|pourover|filter/.test(v)) { return 'filtro-v60'; }
    return null;
  }
  function grindSelect(form) {
    return Array.from(form.querySelectorAll('table.variations select[name^="attribute_"]'))
      .find(function (select) { return /molienda/i.test(normal(select.name)); });
  }
  function enhance(form) {
    if (form.dataset.emdoIkoreReady) { return; }
    var select = grindSelect(form);
    if (!select) { return; }
    var table = select.closest('table.variations');
    var grindRow = select.closest('tr');
    // The WooCommerce value <td> is narrower than the form on desktop.
    // Keep the native attribute inside its row, but put the visual cards
    // after the table so they can use the complete product-form width.
    if (!table || !grindRow || !form.contains(table)) { return; }
    var options = Array.from(select.options).filter(function (option) { return option.value !== ''; });
    // Never hide the native selector when an unfamiliar variant is encountered.
    if (!options.length || options.some(function (option) {
      return !typeFor(option.value) && !typeFor(option.textContent);
    })) { return; }

    var wrap = document.createElement('div');
    wrap.className = 'emdo-ikore-grind';
    var legend = document.createElement('p');
    legend.className = 'emdo-ikore-title';
    legend.textContent = config.legend;
    legend.id = 'emdo-ikore-title-' + (form.dataset.product_id || 'coffee');
    var help = document.createElement('p');
    help.className = 'emdo-ikore-help';
    help.textContent = config.help;
    var grid = document.createElement('div');
    grid.className = 'emdo-ikore-grid';
    grid.setAttribute('role', 'group');
    grid.setAttribute('aria-labelledby', legend.id);

    var entries = options.map(function (option) {
      var type = typeFor(option.value) || typeFor(option.textContent);
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'emdo-ikore-card';
      button.dataset.value = option.value;
      button.setAttribute('aria-pressed', 'false');
      var img = document.createElement('img');
      // Unique URL for V60 avoids stale CDN/browser image caches.
      var filename = type === 'filtro-v60' ? 'filtro-v60-ilustracion.webp' : type + '.webp';
      img.src = config.assetBase + filename + (type === 'filtro-v60' ? '?v=20261028.2' : '');
      if (type === 'filtro-v60') {
        img.onerror = function () {
          img.onerror = null;
          img.src = config.assetBase + 'filtro-v60.webp?v=20261028.2';
        };
      }
      img.alt = '';
      img.width = 144;
      img.height = 144;
      img.decoding = 'async';
      // Icons for actively available choices must load immediately.
      img.loading = 'eager';
      var label = document.createElement('span');
      label.className = 'emdo-ikore-card-label';
      label.textContent = config.labels[type] || option.textContent.trim();
      button.appendChild(img);
      button.appendChild(label);
      button.addEventListener('click', function () {
        if (button.disabled) { return; }
        // Preserve the exact original attribute term / slug. WooCommerce
        // keeps ownership of variation_id, price, stock, cart and email.
        $(select).val(option.value).trigger('change');
        update();
      });
      grid.appendChild(button);
      return { option: option, button: button };
    });
    wrap.appendChild(legend);
    wrap.appendChild(help);
    wrap.appendChild(grid);
    table.insertAdjacentElement('afterend', wrap);
    // WooCommerce's real reset link must remain in the form and clickable.
    // On single-attribute packs it normally belongs to this row; on
    // multi-attribute coffees it may be in a different table row.
    var reset = table.querySelector('a.reset_variations');
    if (reset) {
      reset.classList.add('emdo-ikore-reset');
      wrap.appendChild(reset);
    }
    grindRow.classList.add('emdo-ikore-grind-row');
    select.classList.add('emdo-ikore-native');
    select.setAttribute('tabindex', '-1');
    form.dataset.emdoIkoreReady = '1';
    form.classList.add('emdo-ikore-mounted');

    function update() {
      entries.forEach(function (item) {
        var selected = select.value === item.option.value;
        item.button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        item.button.disabled = item.option.disabled;
        item.button.setAttribute('aria-disabled', item.option.disabled ? 'true' : 'false');
      });
    }
    $(select).on('change', update);
    $(form).on('woocommerce_update_variation_values woocommerce_variation_has_changed reset_data', update);
    // WC may modify <option disabled> when another attribute changes.
    var observer = new MutationObserver(update);
    observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected'] });
    update();
  }

  function start() {
    document.querySelectorAll('form.variations_form').forEach(enhance);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else { start(); }
})(window.jQuery);
