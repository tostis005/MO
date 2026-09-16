(function () {
	'use strict';

	var config = window.MDOReviewsInline || {};
	var table = document.querySelector('.mdo-review-table');
	if (!table || !config.ajaxUrl || !config.nonce) {
		return;
	}

	var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
	var rowMap = {};
	var ids = [];

	rows.forEach(function (row) {
		var detailLink = row.querySelector('a[href*="review_id="]');
		if (!detailLink) {
			return;
		}
		var match = detailLink.href.match(/[?&]review_id=(\d+)/);
		if (!match) {
			return;
		}
		var id = parseInt(match[1], 10);
		if (!id) {
			return;
		}
		ids.push(id);
		rowMap[id] = { row: row, detailUrl: detailLink.href };
	});

	if (!ids.length) {
		return;
	}

	function request(action, payload) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', config.nonce);
		Object.keys(payload || {}).forEach(function (key) {
			var value = payload[key];
			if (Array.isArray(value)) {
				value.forEach(function (item) { body.append(key + '[]', item); });
			} else {
				body.append(key, value == null ? '' : value);
			}
		});
		return fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		}).then(function (response) { return response.json(); });
	}

	function option(value, label, selected) {
		var node = document.createElement('option');
		node.value = String(value);
		node.textContent = label;
		node.selected = selected;
		return node;
	}

	function renderRow(id, review, vendors) {
		var entry = rowMap[id];
		if (!entry || !review) {
			return;
		}
		var cells = entry.row.cells;
		if (!cells || cells.length < 10) {
			return;
		}

		var vendorSelect = document.createElement('select');
		vendorSelect.className = 'mdo-review-inline-select';
		vendorSelect.appendChild(option(0, 'Sin asignar', !review.vendor_id));
		Object.keys(vendors || {}).forEach(function (vendorId) {
			vendorSelect.appendChild(option(vendorId, vendors[vendorId], String(review.vendor_id) === String(vendorId)));
		});
		cells[5].textContent = '';
		cells[5].appendChild(vendorSelect);

		var statusSelect = document.createElement('select');
		statusSelect.className = 'mdo-review-inline-status';
		statusSelect.appendChild(option('pending', 'Pendiente', review.status === 'pending'));
		statusSelect.appendChild(option('validated', 'Publicada', review.status === 'validated'));
		statusSelect.appendChild(option('rejected', 'Descartada', review.status === 'rejected'));
		cells[8].textContent = '';
		cells[8].appendChild(statusSelect);

		var actions = document.createElement('div');
		actions.className = 'mdo-review-inline-actions';
		var saveButton = document.createElement('button');
		saveButton.type = 'button';
		saveButton.className = 'button button-primary button-small';
		saveButton.textContent = config.labels.save;
		var detail = document.createElement('a');
		detail.className = 'button button-small';
		detail.href = entry.detailUrl;
		detail.textContent = config.labels.detail;
		var feedback = document.createElement('span');
		feedback.className = 'mdo-review-inline-feedback';
		actions.appendChild(saveButton);
		actions.appendChild(detail);
		actions.appendChild(feedback);
		cells[9].textContent = '';
		cells[9].appendChild(actions);

		saveButton.addEventListener('click', function () {
			var vendorId = parseInt(vendorSelect.value, 10) || 0;
			var status = statusSelect.value;
			if (status === 'validated' && !vendorId) {
				feedback.textContent = config.labels.vendorRequired;
				feedback.classList.add('is-error');
				return;
			}
			feedback.classList.remove('is-error');
			feedback.textContent = '';
			saveButton.disabled = true;
			saveButton.textContent = config.labels.saving;
			request('mdo_reviews_inline_save', {
				review_id: id,
				vendor_user_id: vendorId,
				status: status
			}).then(function (response) {
				if (!response || !response.success) {
					throw new Error(response && response.data && response.data.message ? response.data.message : config.labels.error);
				}
				feedback.textContent = config.labels.saved;
				feedback.classList.remove('is-error');
			}).catch(function (error) {
				feedback.textContent = error.message || config.labels.error;
				feedback.classList.add('is-error');
			}).finally(function () {
				saveButton.disabled = false;
				saveButton.textContent = config.labels.save;
			});
		});
	}

	request('mdo_reviews_inline_data', { ids: ids })
		.then(function (response) {
			if (!response || !response.success || !response.data) {
				return;
			}
			var reviews = response.data.reviews || {};
			var vendors = response.data.vendors || {};
			Object.keys(reviews).forEach(function (id) {
				renderRow(parseInt(id, 10), reviews[id], vendors);
			});
		})
		.catch(function () {});
}());
