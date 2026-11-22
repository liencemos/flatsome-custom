(function ($) {
	'use strict';

	const form = document.querySelector('form.mcwc-form');
	if (!form) return;

	const panels = Array.from(form.querySelectorAll('[data-co-panel]'));
	const stepButtons = Array.from(form.querySelectorAll('[data-co-step]'));
	const stepNames = panels.map((panel) => panel.dataset.coPanel);
	const i18n = window.mcwcCheckoutI18n || {};
	let furthestStep = 0;
	let summaryTrigger = null;

	const isVisible = (element) => element.getClientRects().length > 0 && !element.closest('[hidden]');
	const getField = (name) => form.querySelector(`[name="${name}"]`);
	const focusField = (field) => {
		if (!field) return;
		const select2 = field.matches('select.select2-hidden-accessible') ? field.closest('.form-row')?.querySelector('.select2-selection') : null;
		(select2 || field).focus();
	};

	const updateReviewSummaries = () => {
		const email = getField('billing_email')?.value.trim() || i18n.addEmail || '';
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
		const address = addressParts.length ? addressParts.join(', ') : i18n.addAddress || '';
		const selectedMethod = form.querySelector('.mcwc-shipping-methods input.shipping_method:checked');
		const method = selectedMethod?.closest('li')?.querySelector('label')?.textContent.trim() || i18n.calculated || '';

		form.querySelectorAll('.mcwc-contact-value').forEach((node) => { node.textContent = email; });
		form.querySelectorAll('.mcwc-address-value').forEach((node) => { node.textContent = address; });
		form.querySelectorAll('.mcwc-method-value').forEach((node) => { node.textContent = method; });
	};

	const updateMobileTotal = () => {
		const target = form.querySelector('.mcwc-mobile-total');
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
				if (focusName === 'mcwc-shipping-methods') {
					target = form.querySelector('.mcwc-shipping-methods input.shipping_method') || form.querySelector('.mcwc-shipping-methods');
					if (target?.classList.contains('mcwc-shipping-methods')) target.tabIndex = -1;
				}
				focusField(target);
			}, 0);
		} else {
			panel.querySelector('h1')?.focus();
		}
	};

	const showFieldError = (row, message) => {
		row.classList.add('woocommerce-invalid', 'woocommerce-invalid-required-field');
		let error = row.querySelector('.mcwc-field-error');
		if (!error) {
			error = document.createElement('small');
			error.className = 'mcwc-field-error';
			error.setAttribute('role', 'alert');
			row.append(error);
		}
		error.textContent = message;
		row.querySelector('input, select, textarea')?.setAttribute('aria-invalid', 'true');
	};

	const clearFieldErrors = (panel) => {
		panel.querySelectorAll('.mcwc-field-error').forEach((error) => error.remove());
		panel.querySelectorAll('.woocommerce-invalid').forEach((row) => row.classList.remove('woocommerce-invalid', 'woocommerce-invalid-required-field'));
		panel.querySelectorAll('[aria-invalid="true"]').forEach((field) => field.removeAttribute('aria-invalid'));
	};

	const validatePanel = (panel) => {
		clearFieldErrors(panel);
		let firstInvalid = null;
		panel.querySelectorAll('.validate-required, .validate-email').forEach((row) => {
			if (!isVisible(row)) return;
			const field = Array.from(row.querySelectorAll('input:not([type="hidden"]), select, textarea')).find(isVisible);
			if (!field || field.disabled) return;
			let message = '';
			const value = field.value.trim();
			const required = row.classList.contains('validate-required');
			if (field.type === 'checkbox') {
				if (required && !field.checked) message = i18n.required || '';
			} else if (required && !value) {
				message = i18n.required || '';
			} else if (value && (field.type === 'email' || row.classList.contains('validate-email')) && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
				message = i18n.validEmail || '';
			} else if (field.name === 'billing_postcode' && getField('billing_country')?.value === 'US' && !/^\d{5}(-\d{4})?$/.test(value)) {
				message = i18n.validZip || '';
			} else if (field.name === 'shipping_postcode' && getField('shipping_country')?.value === 'US' && !/^\d{5}(-\d{4})?$/.test(value)) {
				message = i18n.validZip || '';
			}
			if (message) {
				showFieldError(row, message);
				if (!firstInvalid) firstInvalid = field;
			}
		});
		if (firstInvalid) {
			const focusTarget = firstInvalid.matches('select.select2-hidden-accessible') ? firstInvalid.closest('.form-row')?.querySelector('.select2-selection') || firstInvalid : firstInvalid;
			focusField(focusTarget);
			focusTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
			const fieldElement = fieldName ? document.getElementById(fieldName) : null;
			const invalidField = (fieldName ? getField(fieldName) : null) || fieldElement?.querySelector('input, select, textarea') || fieldElement;
			const fieldPanel = (invalidField || fieldElement)?.closest('[data-co-panel]');
			let step = 'information';
			if (fieldPanel) step = fieldPanel.dataset.coPanel;
			else if (/^(shipping_method|order_comments|gift_wrap)/.test(fieldName)) step = stepNames.includes('shipping') ? 'shipping' : 'information';
			else if (/^(payment_method|payment|terms)/.test(fieldName)) step = 'payment';
			const firstPanel = panels.find((panel) => panel.dataset.coPanel === step);
			furthestStep = Math.max(furthestStep, stepNames.indexOf(step));
			setStep(step);
			if (notice && firstPanel && !firstPanel.contains(notice)) {
				firstPanel.querySelector('h1')?.after(notice);
			}
			if (invalidField) focusField(invalidField);
			else firstPanel?.querySelector('h1')?.focus();
		}, 0);
	});

	const applyCoupon = () => {
		const input = form.querySelector('#mcwc-coupon-code');
		const notice = form.querySelector('.mcwc-coupon-notice');
		const code = input?.value.trim();
		if (!code || !window.wc_checkout_params) return;
		const url = wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'apply_coupon');
		$.post(url, {
			security: wc_checkout_params.apply_coupon_nonce,
			coupon_code: code,
			billing_email: getField('billing_email')?.value.trim() || ''
		})
			.done((response) => {
				const message = new DOMParser().parseFromString(response, 'text/html').querySelector('.woocommerce-error, .woocommerce-message');
				if (notice) notice.textContent = message?.textContent.trim() || '';
				$(document.body).trigger('update_checkout');
			})
			.fail(() => {
				if (notice) notice.textContent = i18n.couponError || '';
			});
	};

	form.addEventListener('keydown', (event) => {
		if (event.key !== 'Enter' || event.target.id !== 'mcwc-coupon-code') return;
		event.preventDefault();
		form.querySelector('[data-co-coupon-apply]')?.click();
	});

	form.addEventListener('click', (event) => {
		const toggle = event.target.closest('[data-co-coupon-toggle]');
		if (toggle) {
			const couponForm = form.querySelector('.mcwc-coupon-form');
			if (!couponForm) return;
			couponForm.hidden = !couponForm.hidden;
			toggle.setAttribute('aria-expanded', couponForm.hidden ? 'false' : 'true');
			if (!couponForm.hidden) couponForm.querySelector('input')?.focus();
		}
		if (event.target.closest('[data-co-coupon-apply]')) {
			applyCoupon();
		}
	});

	const summary = form.querySelector('.mcwc-summary');
	const summaryToggle = form.querySelector('[data-co-summary-toggle]');
	const summaryClose = form.querySelector('[data-co-summary-close]');
	const closeSummary = () => {
		document.body.classList.remove('mcwc-summary-open');
		summaryToggle?.setAttribute('aria-expanded', 'false');
		summaryTrigger?.focus();
	};
	summaryToggle?.addEventListener('click', () => {
		summaryTrigger = summaryToggle;
		document.body.classList.add('mcwc-summary-open');
		summaryToggle.setAttribute('aria-expanded', 'true');
		summaryClose?.focus();
	});
	summaryClose?.addEventListener('click', closeSummary);
	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && document.body.classList.contains('mcwc-summary-open')) closeSummary();
	});

	setStep('information');
	updateMobileTotal();
})(window.jQuery);
