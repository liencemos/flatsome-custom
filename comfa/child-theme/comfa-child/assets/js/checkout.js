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
		const mobile = window.matchMedia('(max-width: 999px)');
		const rates = form.querySelector('.cf-checkout-rates tbody');
		const shippingError = form.querySelector('.cf-checkout-shipping-error');
		let active = 0;
		let unlocked = 0;
		let expanded = false;
		let reviewTable;
		const email = form.querySelector('#billing_email_field');
		if (email) stages[0].querySelector('h2').after(email);

		function show(index, focus) {
			active = index;
			stages.forEach((panel, i) => {
				panel.hidden = i !== index;
				buttons[i].disabled = i > unlocked;
				if (i === index) buttons[i].setAttribute('aria-current', 'step');
				else buttons[i].removeAttribute('aria-current');
			});
			updateRecaps();
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

		function rateLabel(method) {
			if (!method) return '';
			if (method.tagName === 'SELECT') return method.selectedOptions[0]?.textContent.trim();
			const item = method.type === 'hidden' ? method.closest('li') : null;
			const label = method.labels?.[0] ?? item?.querySelector('label') ?? item;
			return label?.textContent.trim();
		}

		function fieldValue(id) {
			const field = document.getElementById(id);
			if (!field?.value) return '';
			return (field.tagName === 'SELECT' ? field.selectedOptions[0]?.textContent : field.value)?.trim();
		}

		function updateRecaps() {
			const contact = fieldValue('billing_email');
			const prefix = form.querySelector('#ship-to-different-address-checkbox')?.checked ? 'shipping_' : 'billing_';
			const address = ['address_1', 'address_2', 'city', 'state', 'postcode', 'country'].map(key => fieldValue(prefix + key)).filter(Boolean).join(', ');
			const methods = rates ? [...rates.rows].map(row => rateLabel(selectedRate(row))).filter(Boolean).join(' · ') : '';
			for (const box of form.querySelectorAll('.cf-checkout-recap')) {
				box.replaceChildren();
				const lines = [[cfCheckout.contact, contact, 'details']];
				if (rates) lines.push([cfCheckout.shipTo, address, 'details']);
				if (box.closest('#cf-checkout-payment')) lines.push([cfCheckout.method, methods, 'delivery']);
				for (const [label, content, stage] of lines) {
					if (!content) continue;
					const row = document.createElement('div');
					const heading = document.createElement('span');
					const value = document.createElement('span');
					const change = document.createElement('button');
					heading.textContent = label;
					value.textContent = content;
					change.type = 'button';
					change.dataset.cfGo = stage;
					change.textContent = cfCheckout.change;
					change.setAttribute('aria-label', cfCheckout.change + ' — ' + label);
					row.append(heading, value, change);
					box.append(row);
				}
			}
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
				const label = rateLabel(method);
				if (!label || !method.value) continue;
				const summaryRow = document.createElement('tr');
				summaryRow.className = 'cf-checkout-shipping-summary';
				const heading = document.createElement('th');
				heading.scope = 'row';
				heading.textContent = row.querySelector('th')?.textContent ?? '';
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
			updateRecaps();
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
			const back = event.target.closest('[data-cf-go]');
			if (back) {
				const index = buttons.findIndex(button => button.dataset.cfStage === back.dataset.cfGo);
				if (index >= 0) show(index, true);
				return;
			}
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
		const coupon = summary.querySelector('.cf-checkout-coupon');
		const nativeCoupon = document.querySelector('form.checkout_coupon');
		if (coupon && nativeCoupon) {
			const code = coupon.querySelector('input');
			const note = summary.querySelector('.cf-checkout-coupon-note');
			const endpoint = wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon');
			let pendingCode;
			coupon.hidden = false;
			document.querySelector('.woocommerce-form-coupon-toggle')?.setAttribute('hidden', '');
			function couponNote(message, isError) {
				note.textContent = message;
				note.hidden = !message;
				note.classList.toggle('cf-checkout-coupon-error', Boolean(isError));
			}
			function applyCoupon() {
				if (!code.value.trim()) { code.focus(); return; }
				if (pendingCode) return;
				pendingCode = code.value.trim();
				couponNote('');
				$(nativeCoupon).find('[name="coupon_code"]').val(pendingCode);
				$(nativeCoupon).trigger('submit');
			}
			coupon.querySelector('button').addEventListener('click', applyCoupon);
			code.addEventListener('keydown', event => {
				if (event.key !== 'Enter' || event.isComposing) return;
				event.preventDefault();
				applyCoupon();
			});
			$(document).on('ajaxComplete', (_event, xhr, settings) => {
				if (!pendingCode || settings.url !== endpoint) return;
				const submittedCode = pendingCode;
				pendingCode = undefined;
				const response = new DOMParser().parseFromString(xhr.responseText || '', 'text/html');
				const error = response.querySelector('.woocommerce-error, .is-error');
				if (error || !response.querySelector('.woocommerce-message, .is-success')) {
					couponNote(error?.textContent.trim() || cfCheckout.couponError, true);
					return;
				}
				if (code.value.trim() === submittedCode) code.value = '';
				couponNote(cfCheckout.applied);
			});
			$(document.body).on('removed_coupon_in_checkout', () => couponNote(''));
		}
		$(document.body).on('updated_checkout', refresh).on('checkout_error', () => {
			const error = form.querySelector('.woocommerce-error [data-id]');
			const field = error && document.getElementById(error.dataset.id);
			const index = stages.findIndex(panel => panel.contains(field));
			if (index >= 0) show(index, false);
			else requireShipping();
		});
	});
}(jQuery));
