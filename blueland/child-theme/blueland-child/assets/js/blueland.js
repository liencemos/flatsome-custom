(function () {
	'use strict';

	const ajaxPost = async (data) => {
		const response = await fetch(blStore.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: new URLSearchParams(Object.assign({ nonce: blStore.nonce }, data))
		});
		const result = await response.json();
		if (!response.ok) throw result;
		return result;
	};

	const applyFragments = (fragments) => {
		Object.entries(fragments || {}).forEach(([selector, markup]) => {
			document.querySelectorAll(selector).forEach((node) => {
				const template = document.createElement('template');
				template.innerHTML = markup.trim();
				node.replaceWith(template.content.cloneNode(true));
			});
		});
		document.dispatchEvent(new CustomEvent('bl:cart-updated'));
	};

	const openCart = () => {
		const cartToggle = document.querySelector('.off-canvas-toggle[data-open="#cart-popup"]');
		if (cartToggle) cartToggle.click();
	};

	document.addEventListener('click', (event) => {
		const button = event.target.closest('.bl-announcement-next, .bl-announcement-prev');
		if (!button) return;
		const slides = Array.from(document.querySelectorAll('.bl-announcement-slide'));
		if (!slides.length) return;
		let index = slides.findIndex((slide) => slide.classList.contains('is-active'));
		index = (index + (button.classList.contains('bl-announcement-next') ? 1 : -1) + slides.length) % slides.length;
		slides.forEach((slide, slideIndex) => slide.classList.toggle('is-active', slideIndex === index));
	});

	window.setInterval(() => {
		const next = document.querySelector('.bl-announcement-next');
		if (next) next.click();
	}, 5000);

	document.addEventListener('click', (event) => {
		const toggle = event.target.closest('.bl-search-toggle');
		if (toggle) {
			const panel = document.querySelector('.bl-search-panel');
			if (panel) {
				panel.hidden = false;
				panel.classList.add('is-open');
				toggle.setAttribute('aria-expanded', 'true');
				window.setTimeout(() => document.querySelector('#bl-search-input')?.focus(), 100);
			}
			return;
		}
		if (event.target.closest('.bl-search-close')) {
			const panel = document.querySelector('.bl-search-panel');
			if (panel) {
				panel.classList.remove('is-open');
				panel.hidden = true;
			}
			const searchToggle = document.querySelector('.bl-search-toggle');
			searchToggle?.setAttribute('aria-expanded', 'false');
			searchToggle?.focus();
		}
	});

	document.addEventListener('change', (event) => {
		if (event.target.matches('.bl-category-filter') && event.target.value) {
			window.location.href = event.target.value;
		}
		if (event.target.matches('.bl-type-filter')) {
			const url = new URL(window.location.href);
			if (event.target.value) url.searchParams.set('filter_product-type', event.target.value);
			else url.searchParams.delete('filter_product-type');
			url.searchParams.delete('paged');
			window.location.href = url.toString();
		}
		if (event.target.matches('.woocommerce-ordering select')) {
			event.target.form?.requestSubmit();
		}
		if (event.target.matches('.bl-plan-selector input[name="bl_plan"]')) {
			const autoship = event.target.value === 'autoship';
			document.querySelectorAll('.bl-plan-card').forEach((card) => card.classList.toggle('is-selected', card.contains(event.target)));
			const cards = document.querySelectorAll('.bl-plan-card');
			const autoshipPrice = cards[0]?.querySelector('.bl-plan-price')?.innerHTML;
			const oneTimePrice = cards[1]?.querySelector('.bl-plan-price')?.innerHTML;
			const total = document.querySelector('.bl-plan-total');
			if (total) total.innerHTML = autoship ? autoshipPrice : oneTimePrice;
			document.querySelector('.bl-todays-total')?.classList.toggle('is-autoship', autoship);
		}
	});

	document.addEventListener('click', (event) => {
		const categoryButton = event.target.closest('.bl-category-prev, .bl-category-next');
		if (categoryButton) {
			const track = categoryButton.parentElement.querySelector('.bl-home-categories-track');
			const card = track?.querySelector('.bl-home-category-card');
			if (track && card) track.scrollBy({ left: (card.getBoundingClientRect().width + 32) * (categoryButton.classList.contains('bl-category-next') ? 1 : -1), behavior: 'smooth' });
			return;
		}
		const addonControl = event.target.closest('[data-addon-direction], [data-addon-index]');
		if (addonControl) {
			const widget = addonControl.closest('.bl-product-addon');
			const track = widget?.querySelector('.bl-product-addon-track');
			const slides = track ? Array.from(track.querySelectorAll('[data-addon-slide]')) : [];
			if (track && slides.length) {
				const current = slides.findIndex((slide) => Math.abs(slide.offsetLeft - track.scrollLeft) < 20);
				const index = addonControl.hasAttribute('data-addon-index') ? Number(addonControl.dataset.addonIndex) : (current + Number(addonControl.dataset.addonDirection) + slides.length) % slides.length;
				track.scrollTo({ left: slides[Math.max(0, Math.min(index, slides.length - 1))].offsetLeft, behavior: 'smooth' });
				widget.querySelectorAll('[data-addon-index]').forEach((dot) => dot.classList.toggle('is-active', Number(dot.dataset.addonIndex) === index));
			}
			return;
		}
		const press = event.target.closest('.bl-press-logo');
		if (press) {
			document.querySelectorAll('.bl-press-logo').forEach((button) => {
				button.classList.toggle('is-active', button === press);
				button.setAttribute('aria-pressed', button === press ? 'true' : 'false');
			});
			const quote = document.querySelector('.bl-press-quote');
			if (quote) quote.textContent = `“${press.dataset.quote}”`;
		}
		const safeTab = event.target.closest('.bl-safe-home-tabs button');
		if (safeTab) {
			safeTab.parentElement.querySelectorAll('button').forEach((button) => {
				button.classList.toggle('is-active', button === safeTab);
				button.setAttribute('aria-selected', button === safeTab ? 'true' : 'false');
			});
		}
		const writeReview = event.target.closest('.bl-write-review');
		if (writeReview) {
			const form = document.querySelector('.bl-review-form');
			form?.classList.toggle('is-open');
			if (form?.classList.contains('is-open')) form.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
		const chip = event.target.closest('.bl-review-chips button');
		if (chip) chip.parentElement.querySelectorAll('button').forEach((button) => button.classList.toggle('is-active', button === chip));
	});

	document.addEventListener('click', (event) => {
		const toggle = event.target.closest('.bl-mobile-filter-toggle');
		if (toggle) {
			const panel = document.querySelector('.bl-mobile-filter-panel');
			const filters = document.querySelector('.bl-archive-filters');
			if (!panel || !filters) return;
			if (!panel.querySelector('.bl-archive-filters')) panel.append(filters.cloneNode(true));
			panel.hidden = false;
			panel.classList.add('is-open');
			toggle.setAttribute('aria-expanded', 'true');
			return;
		}
		if (event.target.closest('.bl-mobile-filter-close')) {
			const panel = document.querySelector('.bl-mobile-filter-panel');
			if (panel) {
				panel.hidden = true;
				panel.classList.remove('is-open');
			}
			const filterToggle = document.querySelector('.bl-mobile-filter-toggle');
			filterToggle?.setAttribute('aria-expanded', 'false');
			filterToggle?.focus();
		}
	});

	document.addEventListener('submit', (event) => {
		const form = event.target;
		if (form.matches('form.cart')) {
			const plan = document.querySelector('.bl-plan-selector input[name="bl_plan"]:checked')?.value || 'one-time';
			const interval = document.querySelector('.bl-plan-selector select[name="bl_interval"]')?.value || '3';
			[
				['bl_plan', plan],
				['bl_interval', interval]
			].forEach(([name, value]) => {
				let input = form.querySelector(`input[type="hidden"][name="${name}"]`);
				if (!input) {
					input = document.createElement('input');
					input.type = 'hidden';
					input.name = name;
					form.append(input);
				}
				input.value = value;
			});
		}
		if (form.matches('.bl-newsletter-form')) {
			event.preventDefault();
			const message = form.querySelector('.bl-newsletter-message');
			const email = form.querySelector('[name="email"]')?.value || '';
			ajaxPost({ action: 'bl_newsletter', email })
				.then((result) => {
					if (message) message.textContent = result.data.message;
				})
				.catch((error) => {
					if (message) message.textContent = error?.data?.message || 'Please try again.';
				});
		}
	}, true);

	document.addEventListener('click', async (event) => {
		const button = event.target.closest('.bl-quick-add.add_to_cart_button, .bl-product-addon .add_to_cart_button');
		if (!button) return;
		event.preventDefault();
		event.stopPropagation();
		button.setAttribute('aria-busy', 'true');
		button.classList.add('loading');
		const endpoint = window.wc_add_to_cart_params?.wc_ajax_url?.replace('%%endpoint%%', 'add_to_cart') || `${window.location.origin}/?wc-ajax=add_to_cart`;
		const payload = new URLSearchParams({
			product_id: button.dataset.product_id || '',
			quantity: button.dataset.quantity || '1',
			product_sku: button.dataset.product_sku || ''
		});
		try {
			const response = await fetch(endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: payload
			});
			const result = await response.json();
			if (result.error && result.product_url) {
				window.location.href = result.product_url;
				return;
			}
			applyFragments(result.fragments);
			button.classList.remove('loading');
			button.classList.add('added');
			button.removeAttribute('aria-busy');
			openCart();
		} catch (error) {
			button.classList.remove('loading');
			button.removeAttribute('aria-busy');
			window.location.href = button.href;
		}
	}, true);

	document.addEventListener('click', (event) => {
		const link = event.target.closest('.bl-anchor-bar a');
		if (!link) return;
		const target = document.querySelector(link.getAttribute('href'));
		if (!target) return;
		event.preventDefault();
		target.scrollIntoView({ behavior: 'smooth', block: 'start' });
	});

	document.addEventListener('click', (event) => {
		const heading = event.target.closest('.bl-mobile-accordion-toggle');
		if (!heading) return;
		const open = heading.classList.toggle('is-open');
		const content = heading.nextElementSibling;
		if (content) content.classList.toggle('is-open', open);
	});

	document.addEventListener('click', (event) => {
		if (!event.target.closest('[data-bl-scroll-add]')) return;
		const button = document.querySelector('.single_add_to_cart_button');
		if (button) button.click();
		else window.scrollTo({ top: document.querySelector('.bl-pdp-main')?.offsetTop || 0, behavior: 'smooth' });
	});

	document.addEventListener('click', async (event) => {
		const button = event.target.closest('[data-cart-qty]');
		if (!button) return;
		button.disabled = true;
		try {
			const result = await ajaxPost({ action: 'bl_update_cart_qty', key: button.dataset.key, delta: button.dataset.cartQty });
			applyFragments(result.fragments);
		} catch (error) {
			window.location.reload();
		} finally {
			button.disabled = false;
		}
	});
})();
