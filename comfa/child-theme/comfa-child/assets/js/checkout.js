(function ($) {
	'use strict';

	$(function () {
		const form = document.querySelector('form.checkout');
		const nav = form?.querySelector('.cf-checkout-stages');
		const summary = form?.querySelector('#cf-checkout-summary');
		const toggle = form?.querySelector('.cf-checkout-summary-toggle');
		if (!nav || !summary || !toggle) return;

		const buttons = [...nav.querySelectorAll('[data-cf-stage]')];
		const stages = buttons.map(button => form.querySelector('#cf-checkout-' + button.dataset.cfStage));
		const mobile = window.matchMedia('(max-width: 849px)');
		const rates = form.querySelector('.cf-checkout-rates tbody');
		const shippingError = form.querySelector('.cf-checkout-shipping-error');
		let active = 0;
		let unlocked = 0;
		let expanded = false;
		let reviewTable;

		function show(index, focus) {
			active = index;
			stages.forEach((panel, i) => {
				panel.hidden = i !== index;
				buttons[i].disabled = i > unlocked;
				if (i === index) buttons[i].setAttribute('aria-current', 'step');
				else buttons[i].removeAttribute('aria-current');
			});
			if (focus) {
				const heading = stages[index].querySelector('h2');
				heading.tabIndex = -1;
				heading.focus();
			}
		}

		function updateSummary() {
			summary.hidden = mobile.matches && !expanded;
			toggle.hidden = !mobile.matches;
			toggle.setAttribute('aria-expanded', String(!summary.hidden));
			toggle.querySelector('strong').textContent = summary.querySelector('.order-total td')?.textContent.trim() ?? '';
		}

		function selectedRate(row) {
			return row.querySelector('input.shipping_method:checked, input.shipping_method[type="hidden"], select.shipping_method');
		}

		function canShip() {
			return !rates || (rates.rows.length > 0 && [...rates.rows].every(row => {
				const method = selectedRate(row);
				return method?.value && !method.disabled;
			}));
		}

		function requireShipping() {
			if (canShip()) return true;
			show(stages.indexOf(rates.closest('section')), false);
			shippingError.hidden = false;
			shippingError.focus();
			return false;
		}

		function updateSummaryShipping(table) {
			const footer = table?.tFoot;
			if (!footer || !rates) return;
			footer.querySelectorAll('.cf-checkout-shipping-summary').forEach(row => row.remove());
			const anchor = footer.querySelector('.order-total');
			if (!anchor) return;
			for (const row of rates.rows) {
				const method = selectedRate(row);
				const label = method?.tagName === 'SELECT' ? method.selectedOptions[0]?.textContent : method?.labels?.[0]?.textContent;
				if (!label || !method.value) continue;
				const summaryRow = document.createElement('tr');
				summaryRow.className = 'cf-checkout-shipping-summary';
				const heading = document.createElement('th');
				heading.scope = 'row';
				heading.textContent = row.cells[0]?.textContent;
				summaryRow.append(heading);
				summaryRow.insertCell().textContent = label;
				anchor.before(summaryRow);
			}
		}

		function refresh() {
			const table = summary.querySelector('.woocommerce-checkout-review-order-table');
			if (rates && table !== reviewTable) {
				reviewTable = table;
				rates.replaceChildren(...(table?.querySelectorAll('tr.woocommerce-shipping-totals') ?? []));
			}
			updateSummaryShipping(table);
			updateSummary();
			if (shippingError && canShip()) shippingError.hidden = true;
		}

		function clearError(field) {
			const row = field.closest('.form-row');
			const error = row?.querySelector('.cf-checkout-field-error');
			if (!error) return;
			const describedBy = (field.getAttribute('aria-describedby') || '').split(/\s+/).filter(id => id && id !== error.id);
			if (describedBy.length) field.setAttribute('aria-describedby', describedBy.join(' '));
			else field.removeAttribute('aria-describedby');
			field.removeAttribute('aria-invalid');
			error.remove();
		}

		function validateDetails() {
			let firstInvalid;
			stages[0].querySelectorAll('.form-row.validate-required, .form-row.validate-email').forEach(row => {
				const field = row.querySelector('input:not([type=hidden]), select, textarea');
				if (!row.getClientRects().length || !field || field.disabled || !field.id) return;
				clearError(field);
				const empty = field.type === 'checkbox' ? !field.checked : !field.value.trim();
				const message = empty && row.classList.contains('validate-required') ? cfCheckout.required : !field.validity.valid ? cfCheckout.invalid : '';
				if (!message) return;
				const error = document.createElement('span');
				error.className = 'cf-checkout-field-error';
				error.id = field.id + '-cf-error';
				error.textContent = message;
				row.append(error);
				field.setAttribute('aria-invalid', 'true');
				field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), error.id].filter(Boolean).join(' '));
				firstInvalid ||= row.querySelector('.select2-selection') || field;
			});
			firstInvalid?.focus();
			return !firstInvalid;
		}

		form.classList.add('cf-checkout-enhanced');
		nav.hidden = false;
		form.querySelectorAll('.cf-checkout-next').forEach(button => { button.hidden = false; });
		if (rates) rates.closest('section').hidden = false;
		show(0, false);
		refresh();

		buttons.forEach((button, i) => button.addEventListener('click', () => {
			if (active === 0 && i > active && !validateDetails()) return;
			if (i === stages.length - 1 && !requireShipping()) return;
			show(i, true);
		}));
		form.addEventListener('input', event => clearError(event.target));
		form.addEventListener('change', event => clearError(event.target));
		form.addEventListener('click', event => {
			if (!event.target.closest('.cf-checkout-next')) return;
			if (active === 0 && !validateDetails()) return;
			if (active + 1 === stages.length - 1 && !requireShipping()) return;
			unlocked = Math.max(unlocked, active + 1);
			show(active + 1, true);
		});
		form.addEventListener('submit', event => {
			if (active === stages.length - 1 || !event.isTrusted) return;
			event.preventDefault();
			event.stopImmediatePropagation();
			stages[active].querySelector('.cf-checkout-next')?.click();
		}, true);
		toggle.addEventListener('click', () => { expanded = !expanded; updateSummary(); });
		mobile.addEventListener('change', updateSummary);
		$(document.body).on('updated_checkout', refresh).on('checkout_error', () => {
			const error = form.querySelector('.woocommerce-error [data-id]');
			const field = error && document.getElementById(error.dataset.id);
			const index = stages.findIndex(panel => panel.contains(field));
			if (index >= 0) show(index, false);
			else requireShipping();
		});
	});
}(jQuery));
