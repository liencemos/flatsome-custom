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

	const mountGiftWrapControl = () => {
		const template = document.querySelector('#bl-gift-wrap-block-template');
		if (!template) return;
		if (document.querySelector('.bl-gift-wrap-control')) {
			template.remove();
			return;
		}
		const target = document.querySelector('.wc-block-cart__submit-container, .wc-block-checkout__payment-method');
		const control = template.content.firstElementChild?.cloneNode(true);
		if (!target || !control) return;
		target.parentNode.insertBefore(control, target);
		template.remove();
	};

	if (document.querySelector('#bl-gift-wrap-block-template')) {
		const giftWrapObserver = new MutationObserver(mountGiftWrapControl);
		giftWrapObserver.observe(document.body, { childList: true, subtree: true });
		mountGiftWrapControl();
		window.setTimeout(() => giftWrapObserver.disconnect(), 10000);
	}

	const todayPrice = document.querySelector('.bl-today-price');
	if (todayPrice && window.jQuery) {
		const defaultTodayPrice = todayPrice.innerHTML;
		const updateTodayPrice = (price) => {
			const amount = Number(price);
			if (!Number.isFinite(amount)) return;
			todayPrice.textContent = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
		};
		const isMainForm = (form) => !form.closest('.product-lightbox, .mfp-content, .product-quick-view-container');
		window.jQuery(document).on('found_variation', 'form.variations_form', (event, variation) => { if (isMainForm(event.currentTarget)) updateTodayPrice(variation?.display_price); });
		window.jQuery(document).on('reset_data', 'form.variations_form', (event) => { if (isMainForm(event.currentTarget)) todayPrice.innerHTML = defaultTodayPrice; });
	}

	const syncCartLabels = () => {
		document.querySelectorAll('.header-cart-link').forEach((link) => {
			const count = Number(link.querySelector('.cart-icon')?.dataset.cartCount || 0);
			link.setAttribute('aria-label', `Cart (${count} items)`);
		});
	};

	const applyFragments = (fragments) => {
		Object.entries(fragments || {}).forEach(([selector, markup]) => {
			document.querySelectorAll(selector).forEach((node) => {
				const template = document.createElement('template');
				template.innerHTML = markup.trim();
				node.replaceWith(template.content.cloneNode(true));
			});
		});
		syncCartLabels();
		document.dispatchEvent(new CustomEvent('bl:cart-updated'));
	};

	syncCartLabels();
	const header = document.querySelector('#header');
	if (header && 'MutationObserver' in window) {
		new MutationObserver(syncCartLabels).observe(header, { childList: true, subtree: true });
	}

	const openCart = () => {
		const cartToggle = document.querySelector('.off-canvas-toggle[data-open="#cart-popup"]');
		if (cartToggle) cartToggle.click();
	};

	const archiveUrl = () => {
		const url = new URL(window.location.href);
		url.pathname = url.pathname.replace(/\/page\/\d+\/?$/, '/');
		url.searchParams.delete('paged');
		return url;
	};

	const mobileFilterPanel = document.querySelector('#bl-mobile-filter-panel');
	let archiveFilterPlaceholder = null;
	document.addEventListener('change', (event) => {
		const select = event.target;
		if (!mobileFilterPanel || mobileFilterPanel.hidden || !mobileFilterPanel.contains(select) || !select.matches('.woocommerce-ordering select.orderby')) return;
		event.stopImmediatePropagation();
	}, true);
	document.addEventListener('submit', (event) => {
		const form = event.target;
		if (!mobileFilterPanel || mobileFilterPanel.hidden || !mobileFilterPanel.contains(form) || !form.matches('.woocommerce-ordering')) return;
		event.preventDefault();
		event.stopImmediatePropagation();
	}, true);
	const reviewLightbox = document.querySelector('#bl-review-lightbox');
	let reviewPhotoFocus = null;

	const closeReviewLightbox = () => {
		if (!reviewLightbox || reviewLightbox.hidden) return;
		reviewLightbox.hidden = true;
		document.body.classList.remove('bl-review-lightbox-open');
		const image = reviewLightbox.querySelector('img');
		if (image) image.removeAttribute('src');
		reviewPhotoFocus?.focus();
	};

	const openMobileFilterSheet = (toggle) => {
		if (!mobileFilterPanel || !window.matchMedia('(max-width: 849px)').matches) return;
		const filters = document.querySelector('.bl-archive-toolbar .bl-archive-filters');
		const content = mobileFilterPanel.querySelector('.bl-mobile-filter-content');
		if (filters && content && !content.contains(filters)) {
			archiveFilterPlaceholder = document.createElement('span');
			archiveFilterPlaceholder.hidden = true;
			filters.before(archiveFilterPlaceholder);
			content.append(filters);
		}
		mobileFilterPanel.hidden = false;
		mobileFilterPanel.classList.add('is-open');
		document.body.classList.add('bl-filter-sheet-open');
		toggle.setAttribute('aria-expanded', 'true');
		window.setTimeout(() => mobileFilterPanel.querySelector('.bl-mobile-filter-close')?.focus(), 30);
	};

	const closeMobileFilterSheet = (restoreFocus = true) => {
		if (!mobileFilterPanel || mobileFilterPanel.hidden) return;
		mobileFilterPanel.classList.remove('is-open');
		mobileFilterPanel.hidden = true;
		document.body.classList.remove('bl-filter-sheet-open');
		const filters = mobileFilterPanel.querySelector('.bl-mobile-filter-content .bl-archive-filters');
		if (filters && archiveFilterPlaceholder?.parentNode) {
			archiveFilterPlaceholder.before(filters);
			archiveFilterPlaceholder.remove();
		}
		archiveFilterPlaceholder = null;
		const toggle = document.querySelector('.bl-mobile-filter-toggle');
		toggle?.setAttribute('aria-expanded', 'false');
		if (restoreFocus) toggle?.focus();
	};

	const mobileArchiveUrl = () => {
		let url = archiveUrl();
		const category = mobileFilterPanel?.querySelector('.bl-category-filter')?.value;
		if (category) url = new URL(category, window.location.href);
		url.pathname = url.pathname.replace(/\/page\/\d+\/?$/, '/');
		url.searchParams.delete('paged');
		const type = mobileFilterPanel?.querySelector('.bl-type-filter')?.value;
		if (type && type !== 'all') url.searchParams.set('filter_product-type', type);
		else url.searchParams.delete('filter_product-type');
		const order = mobileFilterPanel?.querySelector('.woocommerce-ordering select');
		const defaultOrder = order?.options[0]?.value || 'menu_order';
		if (order?.value && order.value !== defaultOrder) url.searchParams.set('orderby', order.value);
		else url.searchParams.delete('orderby');
		return url;
	};

	const stickyCart = document.querySelector('.bl-mobile-sticky-cart');
	const primaryAddToCart = document.querySelector('.single-product .bl-pdp-info form.cart .single_add_to_cart_button');
	if (stickyCart && primaryAddToCart && 'IntersectionObserver' in window) {
		const cartObserver = new IntersectionObserver(([entry]) => {
			stickyCart.classList.toggle('is-visible', !entry.isIntersecting && entry.boundingClientRect.bottom <= 0);
		}, { threshold: 0 });
		cartObserver.observe(primaryAddToCart);
	}

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
			const wrapper = toggle.closest('.bl-search-wrap');
			const panel = wrapper?.querySelector('.bl-search-panel');
			if (panel) {
				panel.hidden = false;
				panel.classList.add('is-open');
				toggle.setAttribute('aria-expanded', 'true');
				window.setTimeout(() => panel.querySelector('input[type="search"]')?.focus(), 100);
			}
			return;
		}
		const close = event.target.closest('.bl-search-close');
		if (close) {
			const wrapper = close.closest('.bl-search-wrap');
			const panel = wrapper?.querySelector('.bl-search-panel');
			if (panel) {
				panel.classList.remove('is-open');
				panel.hidden = true;
			}
			const searchToggle = wrapper?.querySelector('.bl-search-toggle');
			searchToggle?.setAttribute('aria-expanded', 'false');
			searchToggle?.focus();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (reviewLightbox && !reviewLightbox.hidden) {
			if (event.key === 'Escape') {
				closeReviewLightbox();
				return;
			}
			if (event.key === 'Tab') {
				event.preventDefault();
				reviewLightbox.querySelector('.bl-review-lightbox-close')?.focus();
				return;
			}
		}
		if (mobileFilterPanel && !mobileFilterPanel.hidden) {
			if (event.key === 'Escape') {
				closeMobileFilterSheet();
				return;
			}
			if (event.key === 'Tab') {
				const focusable = Array.from(mobileFilterPanel.querySelectorAll('button:not([disabled]), select:not([disabled]), a[href]'))
					.filter((element) => element.getClientRects().length);
				const first = focusable[0];
				const last = focusable[focusable.length - 1];
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last?.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first?.focus();
				}
				return;
			}
		}
		if ('Escape' !== event.key) return;
		const panel = Array.from(document.querySelectorAll('.bl-search-panel')).find((item) => !item.hidden && item.classList.contains('is-open'));
		if (!panel) return;
		panel.hidden = true;
		panel.classList.remove('is-open');
		const wrapper = panel.closest('.bl-search-wrap');
		wrapper?.querySelector('.bl-search-toggle')?.setAttribute('aria-expanded', 'false');
		wrapper?.querySelector('.bl-search-toggle')?.focus();
	});

	document.addEventListener('change', (event) => {
		if (event.target.closest('.bl-mobile-filter-panel')) return;
		const giftWrapInput = event.target.closest('[data-gift-wrap-toggle], [data-gift-wrap-note]');
		if (giftWrapInput) {
			const control = giftWrapInput.closest('.bl-gift-wrap-control');
			const toggle = control?.querySelector('[data-gift-wrap-toggle]');
			const note = control?.querySelector('[data-gift-wrap-note]');
			const message = control?.querySelector('.bl-gift-wrap-message');
			if (giftWrapInput.matches('[data-gift-wrap-toggle]') && note) note.hidden = !toggle.checked;
			const payload = {
				action: 'bl_update_gift_wrap',
				enabled: toggle?.checked ? '1' : '0',
				note: note?.value || '',
				nonce: control?.querySelector('[data-gift-wrap-nonce]')?.value || '',
				bl_gift_website: control?.querySelector('[data-gift-wrap-website]')?.value || ''
			};
			ajaxPost(payload)
				.then(() => {
					if (message) message.textContent = 'Gift wrap updated.';
					if (document.querySelector('.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout')) {
						const cartStore = window.wp?.data?.dispatch?.('wc/store/cart');
						if (cartStore?.invalidateResolutionForStore) cartStore.invalidateResolutionForStore();
						else window.location.reload();
					} else if (document.body.classList.contains('woocommerce-checkout')) {
						if (window.jQuery) window.jQuery(document.body).trigger('update_checkout');
						else window.location.reload();
					} else {
						window.location.reload();
					}
				})
				.catch((error) => {
					if (message) message.textContent = error?.data?.message || 'Please try again.';
				});
			return;
		}
		if (event.target.matches('.bl-category-filter') && event.target.value) {
			window.location.href = event.target.value;
		}
		if (event.target.matches('.bl-type-filter')) {
			const url = archiveUrl();
			if (event.target.value && event.target.value !== 'all') url.searchParams.set('filter_product-type', event.target.value);
			else url.searchParams.delete('filter_product-type');
			window.location.href = url.toString();
		}
		if (event.target.matches('.woocommerce-ordering select')) {
			const url = archiveUrl();
			url.searchParams.set('orderby', event.target.value);
			window.location.href = url.toString();
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
		const reviewPhoto = event.target.closest('.bl-review-photo');
		if (reviewPhoto && reviewLightbox) {
			event.preventDefault();
			reviewPhotoFocus = reviewPhoto;
			const image = reviewLightbox.querySelector('img');
			if (image) {
				image.src = reviewPhoto.href;
				image.alt = reviewPhoto.querySelector('img')?.alt || 'Customer review photo';
			}
			reviewLightbox.hidden = false;
			document.body.classList.add('bl-review-lightbox-open');
			reviewLightbox.querySelector('.bl-review-lightbox-close')?.focus();
		}
		if (event.target.closest('.bl-review-lightbox-close') || event.target === reviewLightbox) {
			closeReviewLightbox();
		}
		const chip = event.target.closest('.bl-review-chips button');
		if (chip) {
			const list = chip.closest('.bl-review-controls')?.nextElementSibling;
			if (!list?.classList.contains('bl-review-list')) return;
			chip.parentElement.querySelectorAll('button').forEach((button) => button.classList.toggle('is-active', button === chip));
			const filter = chip.dataset.reviewRating;
			const cards = Array.from(list.querySelectorAll('.bl-review-card'));
			const visibleCount = Number(list.dataset.visibleCount || 6);
			cards.forEach((card, index) => {
				if (filter === 'all') card.hidden = index >= visibleCount;
				else if (filter === 'photos') card.hidden = !card.querySelector('.bl-review-photo');
				else if (filter === '3') card.hidden = Number(card.dataset.rating) > 3;
				else card.hidden = Number(card.dataset.rating) !== Number(filter);
			});
			const more = list.nextElementSibling;
			if (more?.classList.contains('bl-review-more')) more.hidden = filter !== 'all' || visibleCount >= cards.length;
		}
		const moreReviews = event.target.closest('.bl-review-more');
		if (moreReviews) {
			const list = moreReviews.previousElementSibling;
			const cards = Array.from(list.querySelectorAll('.bl-review-card'));
			const visibleCount = Math.min(cards.length, Number(list.dataset.visibleCount || 6) + 6);
			cards.forEach((card, index) => { card.hidden = index >= visibleCount; });
			list.dataset.visibleCount = String(visibleCount);
			moreReviews.hidden = visibleCount >= cards.length;
		}
	});

	document.addEventListener('click', (event) => {
		const toggle = event.target.closest('.bl-mobile-filter-toggle');
		if (toggle) {
			openMobileFilterSheet(toggle);
			return;
		}
		if (event.target.closest('.bl-mobile-filter-close, [data-mobile-filter-close]') || event.target === mobileFilterPanel) {
			closeMobileFilterSheet();
			return;
		}
		if (event.target.closest('.bl-mobile-filter-clear')) {
			const category = mobileFilterPanel?.querySelector('.bl-category-filter');
			const type = mobileFilterPanel?.querySelector('.bl-type-filter');
			const order = mobileFilterPanel?.querySelector('.woocommerce-ordering select');
			if (category) category.value = '';
			if (type) type.value = 'all';
			if (order) order.value = order.options[0]?.value || 'menu_order';
			return;
		}
		if (event.target.closest('.bl-mobile-filter-apply')) {
			const url = mobileArchiveUrl();
			closeMobileFilterSheet(false);
			window.location.assign(url.toString());
		}
	});

	window.addEventListener('resize', () => {
		if (mobileFilterPanel && !mobileFilterPanel.hidden && !window.matchMedia('(max-width: 849px)').matches) {
			closeMobileFilterSheet(false);
		}
	});

	document.addEventListener('submit', (event) => {
		const form = event.target;
		if (form.matches('.bl-stock-notify-form')) {
			event.preventDefault();
			const message = form.querySelector('.bl-stock-notify-message');
			const submit = form.querySelector('[type="submit"]');
			const payload = Object.fromEntries(new FormData(form).entries());
			payload.action = 'bl_stock_notify';
			if (submit) submit.disabled = true;
			if (message) message.textContent = '';
			ajaxPost(payload)
				.then((result) => {
					if (message) message.textContent = result.data.message;
					form.reset();
				})
				.catch((error) => {
					if (message) message.textContent = error?.data?.message || 'Please try again.';
				})
				.finally(() => {
					if (submit) submit.disabled = false;
				});
			return;
		}
		if (form.matches('.bl-qa-form')) {
			event.preventDefault();
			const message = form.querySelector('.bl-qa-message');
			const submit = form.querySelector('[type="submit"]');
			const payload = Object.fromEntries(new FormData(form).entries());
			payload.action = 'bl_submit_product_question';
			if (submit) submit.disabled = true;
			if (message) message.textContent = '';
			ajaxPost(payload)
				.then((result) => {
					if (message) message.textContent = result.data.message;
					form.reset();
				})
				.catch((error) => {
					if (message) message.textContent = error?.data?.message || 'Please try again.';
				})
				.finally(() => {
					if (submit) submit.disabled = false;
				});
			return;
		}
		if (form.matches('.bl-newsletter-form, .bl-popup-form')) {
			event.preventDefault();
			const isPopup = form.matches('.bl-popup-form');
			const message = form.querySelector(isPopup ? '.bl-email-popup-error' : '.bl-newsletter-message');
			const email = form.querySelector('[name="email"]')?.value || '';
			const payload = {
				action: 'bl_newsletter',
				email,
				bl_newsletter_website: form.querySelector('[name="bl_newsletter_website"]')?.value || ''
			};
			if (isPopup) payload.source = 'popup';
			const submit = form.querySelector('[type="submit"]');
			if (submit) submit.disabled = true;
			if (message) message.textContent = '';
			ajaxPost(payload)
				.then((result) => {
					if (isPopup && result.data.code) {
						const popup = form.closest('#bl-email-popup');
						const success = popup?.querySelector('.bl-popup-success');
						if (success) {
							form.hidden = true;
							success.hidden = false;
							document.cookie = `bl_popup_seen=1; Max-Age=${365 * 24 * 60 * 60}; Path=/; SameSite=Lax${window.location.protocol === 'https:' ? '; Secure' : ''}`;
						}
					} else if (message) {
						message.textContent = result.data.message;
					}
				})
				.catch((error) => {
					if (message) message.textContent = error?.data?.message || 'Please try again.';
				})
				.finally(() => {
					if (submit) submit.disabled = false;
				});
		}
	}, true);

	document.querySelectorAll('.bl-review-form form').forEach((form) => {
		form.enctype = 'multipart/form-data';
	});

	const emailPopup = document.querySelector('#bl-email-popup');
	if (emailPopup) {
		let popupOpened = false;
		let previousFocus = null;
		const popupCookieExists = () => document.cookie.split(';').some((cookie) => cookie.trim().startsWith('bl_popup_seen='));
		const setPopupCookie = (days) => {
			document.cookie = `bl_popup_seen=1; Max-Age=${days * 24 * 60 * 60}; Path=/; SameSite=Lax${window.location.protocol === 'https:' ? '; Secure' : ''}`;
		};
		const isVisible = (element) => {
			if (!element) return false;
			const style = window.getComputedStyle(element);
			const rect = element.getBoundingClientRect();
			return style.display !== 'none' && style.visibility !== 'hidden' && Number(style.opacity) > 0 && rect.left < window.innerWidth && rect.right > 0 && rect.top < window.innerHeight && rect.bottom > 0;
		};
		const isPopupBlocked = () => (
			isVisible(document.querySelector('.off-canvas-cart'))
			|| isVisible(document.querySelector('.mfp-wrap.mfp-ready'))
			|| Boolean(document.querySelector('.bl-mobile-filter-panel.is-open'))
		);
		const closeEmailPopup = () => {
			if (emailPopup.hidden) return;
			const subscribed = !emailPopup.querySelector('.bl-popup-success')?.hidden;
			setPopupCookie(subscribed ? 365 : 14);
			emailPopup.hidden = true;
			emailPopup.classList.remove('is-open');
			document.body.classList.remove('bl-email-popup-open');
			emailPopup.setAttribute('aria-hidden', 'true');
			if (previousFocus && typeof previousFocus.focus === 'function') previousFocus.focus();
		};
		const openEmailPopup = () => {
			if (popupOpened || popupCookieExists()) return;
			if (isPopupBlocked()) {
				window.setTimeout(openEmailPopup, 750);
				return;
			}
			popupOpened = true;
			previousFocus = document.activeElement;
			emailPopup.hidden = false;
			emailPopup.setAttribute('aria-hidden', 'false');
			emailPopup.classList.add('is-open');
			document.body.classList.add('bl-email-popup-open');
			window.setTimeout(() => emailPopup.querySelector('#bl-popup-email')?.focus(), 40);
		};

		emailPopup.addEventListener('click', (event) => {
			if (event.target === emailPopup || event.target.closest('[data-popup-dismiss]')) {
				closeEmailPopup();
				return;
			}
			const copyButton = event.target.closest('.bl-popup-copy');
			if (copyButton) {
				const code = 'WELCOME10';
				if (navigator.clipboard?.writeText) {
					navigator.clipboard.writeText(code).then(() => {
						copyButton.textContent = 'Copied!';
					}).catch(() => {
						copyButton.textContent = code;
					});
				} else {
					copyButton.textContent = code;
				}
			}
		});
		document.addEventListener('keydown', (event) => {
			if (emailPopup.hidden) return;
			if (event.key === 'Escape') {
				closeEmailPopup();
				return;
			}
			if (event.key === 'Tab') {
				const focusable = Array.from(emailPopup.querySelectorAll('input:not([disabled]), button:not([disabled]), a[href]'))
					.filter((element) => element.getClientRects().length);
				const first = focusable[0];
				const last = focusable[focusable.length - 1];
				if (event.shiftKey && document.activeElement === first) {
					event.preventDefault();
					last?.focus();
				} else if (!event.shiftKey && document.activeElement === last) {
					event.preventDefault();
					first?.focus();
				}
			}
		});

		if (!popupCookieExists()) {
			const startedAt = Date.now();
			if (window.matchMedia('(max-width: 849px)').matches) {
				window.setTimeout(openEmailPopup, 25000);
				document.addEventListener('scroll', () => {
					const scrollable = document.documentElement.scrollHeight - window.innerHeight;
					if (scrollable > 0 && window.scrollY / scrollable >= 0.5) openEmailPopup();
				}, { passive: true });
			} else {
				window.setTimeout(openEmailPopup, 30000);
				document.addEventListener('mouseout', (event) => {
					if (event.clientY <= 0 && Date.now() - startedAt >= 5000) openEmailPopup();
				});
			}
		}
	}

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
