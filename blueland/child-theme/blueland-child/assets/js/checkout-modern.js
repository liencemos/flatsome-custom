(function ($) {
	'use strict';

	const form = document.querySelector('form.bl-co-form');
	if (!form) return;

	const panels = Array.from(form.querySelectorAll('[data-co-panel]'));
	const stepButtons = Array.from(form.querySelectorAll('[data-co-step]'));
	const stepNames = panels.map((panel) => panel.dataset.coPanel);
	let furthestStep = 0;
	let summaryTrigger = null;

	const isVisible = (element) => element.getClientRects().length > 0 && !element.closest('[hidden]');
	const getField = (name) => form.querySelector(`[name="${name}"]`);

	const updateReviewSummaries = () => {
		const email = getField('billing_email')?.value.trim() || 'Add your email address';
		const shipToDifferent = form.querySelector('#ship-to-different-address-checkbox')?.checked;
		const prefix = shipToDifferent ? 'shipping_' : 'billing_';
		const addressParts = [
			`${prefix}address_1`,
			`${prefix}address_2`,
			`${prefix}city`,
			`${prefix}state`,
			`${prefix}postcode`,
			`${prefix}country`
		].map((name) => {
			const input = getField(name);
			if (!input || !input.value) return '';
			if (input.tagName === 'SELECT') return input.selectedOptions[0]?.textContent.trim() || '';
			return input.value.trim();
		}).filter(Boolean);
		const address = addressParts.length ? addressParts.join(', ') : 'Add your shipping address';
		const selectedMethod = form.querySelector('.bl-co-shipping-methods input.shipping_method:checked');
		const method = selectedMethod?.closest('li')?.querySelector('label')?.textContent.trim() || 'Calculated at next step';

		form.querySelectorAll('.bl-co-contact-value').forEach((node) => { node.textContent = email; });
		form.querySelectorAll('.bl-co-address-value').forEach((node) => { node.textContent = address; });
		form.querySelectorAll('.bl-co-method-value').forEach((node) => { node.textContent = method; });
	};

	const updateMobileTotal = () => {
		const target = form.querySelector('.bl-co-mobile-total');
		const amount = form.querySelector('.woocommerce-checkout-review-order-table .order-total .amount');
		if (target && amount) target.textContent = amount.textContent.trim();
	};

	const setStep = (step, focusName = '') => {
		const index = stepNames.indexOf(step);
		if (index < 0) return;
		panels.forEach((panel) => { panel.hidden = panel.dataset.coPanel !== step; });
		stepButtons.forEach((button) => {
			const buttonIndex = stepNames.indexOf(button.dataset.coStep);
			if (button.dataset.coStep === step) {
				button.setAttribute('aria-current', 'step');
				button.removeAttribute('aria-disabled');
			} else {
				button.removeAttribute('aria-current');
				if (buttonIndex > furthestStep) button.setAttribute('aria-disabled', 'true');
				else button.removeAttribute('aria-disabled');
			}
		});
		window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${step}`);
		updateReviewSummaries();
		const panel = panels[index];
		if (focusName) {
			window.setTimeout(() => {
				let target = getField(focusName);
				if (focusName === 'bl-co-shipping-methods') {
					target = form.querySelector('.bl-co-shipping-methods input.shipping_method') || form.querySelector('.bl-co-shipping-methods');
					if (target?.classList.contains('bl-co-shipping-methods')) target.tabIndex = -1;
				}
				target?.focus();
			}, 0);
		} else {
			panel.querySelector('h1')?.focus();
		}
	};

	const showFieldError = (row, message) => {
		row.classList.add('woocommerce-invalid', 'woocommerce-invalid-required-field');
		let error = row.querySelector('.bl-co-field-error');
		if (!error) {
			error = document.createElement('small');
			error.className = 'bl-co-field-error';
			error.setAttribute('role', 'alert');
			row.append(error);
		}
		error.textContent = message;
		row.querySelector('input, select, textarea')?.setAttribute('aria-invalid', 'true');
	};

	const clearFieldErrors = (panel) => {
		panel.querySelectorAll('.bl-co-field-error').forEach((error) => error.remove());
		panel.querySelectorAll('.woocommerce-invalid').forEach((row) => row.classList.remove('woocommerce-invalid', 'woocommerce-invalid-required-field'));
		panel.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
	};

	const validatePanel = (panel) => {
		clearFieldErrors(panel);
		let firstInvalid = null;
		panel.querySelectorAll('.validate-required').forEach((row) => {
			if (!isVisible(row)) return;
			const field = Array.from(row.querySelectorAll('input:not([type="hidden"]), select, textarea')).find(isVisible);
			if (!field || field.disabled) return;
			let message = '';
			if (field.type === 'checkbox') {
				if (!field.checked) message = 'This field is required.';
			} else if (!field.value.trim()) {
				message = 'This field is required.';
			} else if (field.type === 'email' && !field.validity.valid) {
				message = 'Please enter a valid email address.';
			} else if (field.name === 'billing_postcode' && getField('billing_country')?.value === 'US' && !/^\d{5}(-\d{4})?$/.test(field.value.trim())) {
				message = 'Enter a valid 5-digit ZIP code.';
			} else if (field.name === 'shipping_postcode' && getField('shipping_country')?.value === 'US' && !/^\d{5}(-\d{4})?$/.test(field.value.trim())) {
				message = 'Enter a valid 5-digit ZIP code.';
			}
			if (message) {
				showFieldError(row, message);
				if (!firstInvalid) firstInvalid = field;
			}
		});
		if (firstInvalid) {
			firstInvalid.focus();
			firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
			return false;
		}
		return true;
	};

	const goForward = (panel) => {
		if (!validatePanel(panel)) return;
		const currentIndex = stepNames.indexOf(panel.dataset.coPanel);
		const nextStep = stepNames[currentIndex + 1];
		if (!nextStep) return;
		furthestStep = Math.max(furthestStep, currentIndex + 1);
		setStep(nextStep);
		$(document.body).trigger('update_checkout');
	};

	form.querySelectorAll('[data-co-panel]').forEach((panel) => {
		panel.querySelector('[data-co-continue]')?.addEventListener('click', () => goForward(panel));
	});

	stepButtons.forEach((button) => {
		button.addEventListener('click', () => {
			if (button.getAttribute('aria-disabled') === 'true') return;
			setStep(button.dataset.coStep);
		});
	});

	form.addEventListener('click', (event) => {
		const change = event.target.closest('[data-co-change]');
		if (change) {
			const step = change.dataset.coChange;
			if (stepNames.includes(step)) setStep(step, change.dataset.coFocus || '');
		}
	});

	form.addEventListener('input', updateReviewSummaries);
	form.addEventListener('change', (event) => {
		updateReviewSummaries();
	});

	$(document.body).on('updated_checkout', function () {
		updateReviewSummaries();
		updateMobileTotal();
	});

	$(document.body).on('checkout_error', function () {
		window.setTimeout(() => {
			const notice = form.querySelector('.woocommerce-NoticeGroup-checkout, .woocommerce-error');
			const fieldNotice = form.querySelector('.woocommerce-NoticeGroup-checkout li[data-id], .woocommerce-error li[data-id]');
			const fieldName = fieldNotice?.getAttribute('data-id') || '';
			let step = 'information';
			if (/^(shipping_method|shipping_)/.test(fieldName)) step = stepNames.includes('shipping') ? 'shipping' : 'information';
			else if (/^(payment_method|terms)/.test(fieldName)) step = 'payment';
			const firstPanel = panels.find((panel) => panel.dataset.coPanel === step);
			furthestStep = Math.max(furthestStep, stepNames.indexOf(step));
			setStep(step);
			if (notice && firstPanel && !firstPanel.contains(notice)) {
				firstPanel.querySelector('h1')?.after(notice);
			}
			const invalidField = fieldName ? getField(fieldName) : null;
			(invalidField || firstPanel?.querySelector('h1'))?.focus();
		}, 0);
	});

	form.addEventListener('click', (event) => {
		const toggle = event.target.closest('[data-co-coupon-toggle]');
		if (toggle) {
			const couponForm = form.querySelector('.bl-co-coupon-form');
			if (!couponForm) return;
			couponForm.hidden = !couponForm.hidden;
			toggle.setAttribute('aria-expanded', couponForm.hidden ? 'false' : 'true');
			if (!couponForm.hidden) couponForm.querySelector('input')?.focus();
		}
		if (event.target.closest('[data-co-coupon-apply]')) {
			const input = form.querySelector('#bl-co-coupon-code');
			const notice = form.querySelector('.bl-co-coupon-notice');
			const code = input?.value.trim();
			if (!code || !window.wc_checkout_params) return;
			const url = wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon');
			$.post(url, { security: wc_checkout_params.apply_coupon_nonce, coupon_code: code })
				.done((response) => {
					const message = new DOMParser().parseFromString(response, 'text/html').querySelector('.woocommerce-error, .woocommerce-message');
					if (notice) notice.textContent = message?.textContent.trim() || '';
					$(document.body).trigger('update_checkout');
				})
				.fail(() => {
					if (notice) notice.textContent = 'We could not apply that code. Please try again.';
				});
		}
	});

	const summary = form.querySelector('.bl-co-summary');
	const summaryToggle = form.querySelector('[data-co-summary-toggle]');
	const summaryClose = form.querySelector('[data-co-summary-close]');
	const closeSummary = () => {
		document.body.classList.remove('bl-co-summary-open');
		summaryToggle?.setAttribute('aria-expanded', 'false');
		summaryTrigger?.focus();
	};
	summaryToggle?.addEventListener('click', () => {
		summaryTrigger = summaryToggle;
		document.body.classList.add('bl-co-summary-open');
		summaryToggle.setAttribute('aria-expanded', 'true');
		summaryClose?.focus();
	});
	summaryClose?.addEventListener('click', closeSummary);
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && document.body.classList.contains('bl-co-summary-open')) closeSummary();
	});

	setStep('information');
	updateMobileTotal();
})(window.jQuery);
