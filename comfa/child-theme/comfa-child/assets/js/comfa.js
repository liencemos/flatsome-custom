/* Comfa storefront behaviour. Depends on jQuery (WooCommerce variation events). */
(function ($) {
	'use strict';
	var store = window.cfStore || {};

	// Newsletter forms (footer and welcome offer).
	$(document).on('submit', '.cf-newsletter', function (event) {
		event.preventDefault();
		var $form = $(this);
		var $message = $form.find('.cf-newsletter__message');
		var $button = $form.find('button[type="submit"]').prop('disabled', true);
		$.post(store.ajaxUrl, {
			action: 'cf_newsletter',
			nonce: store.nonce,
			email: $form.find('[name="email"]').val(),
			company: $form.find('[name="company"]').val()
		}).done(function (response) {
			$message.removeClass('is-error').text(response && response.data ? response.data.message : '');
			$form.addClass('is-done');
			try { window.localStorage.setItem('cfOfferDismissed', '1'); } catch (e) {}
			$('.cf-offer-pill').attr('hidden', true);
		}).fail(function (xhr) {
			var data = xhr.responseJSON && xhr.responseJSON.data;
			$message.addClass('is-error').text(data && data.message ? data.message : (store.i18n ? store.i18n.error : ''));
		}).always(function () {
			$button.prop('disabled', false);
		});
	});

	// Welcome-offer pill.
	$(function () {
		var dismissed = false;
		try { dismissed = window.localStorage.getItem('cfOfferDismissed') === '1'; } catch (e) {}
		var $pill = $('.cf-offer-pill');
		if (!dismissed) {
			$pill.removeAttr('hidden');
		}
		$pill.on('click', 'button', function () {
			$pill.attr('hidden', true);
			try { window.localStorage.setItem('cfOfferDismissed', '1'); } catch (e) {}
		});
	});

	// Variation pickers: fit notes on size buttons, giving line.
	function decorate($form) {
		var notes = {};
		var $notes = $form.siblings('.cf-size-notes').add($form.parent().find('.cf-size-notes')).first();
		if ($notes.length) {
			try { notes = JSON.parse($notes.text()) || {}; } catch (e) {}
		}
		$form.find('.ux-swatches[data-attribute_name="attribute_pa_size"] .ux-swatch').each(function () {
			var $swatch = $(this);
			var note = notes[$swatch.data('value')];
			if (note && !$swatch.find('.cf-size-note').length) {
				$swatch.append($('<small class="cf-size-note"></small>').text(note));
			}
		});
	}

	$(document).on('woocommerce_update_variation_values check_variations wc_variation_form', '.variations_form', function () {
		decorate($(this));
	});
	$(document).on('change', '.variations_form select', function () {
		decorate($(this).closest('.variations_form'));
	});
	function scope($form) {
		return $form.closest('.product-info, .product-lightbox-inner, .product');
	}
	function mainPrice($form) {
		return scope($form).find('.price-wrapper .price, .product-lightbox-inner > .price, p.price').not($form.find('.price')).first();
	}
	$(document).on('found_variation', '.variations_form', function (event, variation) {
		var $form = $(this);
		var count = variation && variation.cf_pack_count ? parseInt(variation.cf_pack_count, 10) : 1;
		var $line = scope($form).find('[data-cf-give]').first();
		if ($line.length) {
			$line.text($line.text().replace(/\d+/g, count));
		}
		// The page shows one price line; keep it in sync with the chosen pack.
		var $price = mainPrice($form);
		if ($price.length && variation && variation.price_html) {
			if ($price.data('cfOriginal') === undefined) {
				$price.data('cfOriginal', $price.html());
			}
			$price.html($('<div>').html(variation.price_html).find('.price').html() || $price.data('cfOriginal'));
		}
	});
	$(document).on('reset_data', '.variations_form', function () {
		var $price = mainPrice($(this));
		if ($price.length && $price.data('cfOriginal') !== undefined) {
			$price.html($price.data('cfOriginal'));
		}
		previewPartial($(this));
	});
	// Selection not complete yet: show the photo of the first variation that goes with the picks so far,
	// using WooCommerce's own variation data and image swap.
	function previewPartial($form) {
		var variations = $form.data('product_variations');
		var picks = [];
		$form.find('.variations select').each(function () {
			if (this.value) {
				picks.push([this.name, this.value]);
			}
		});
		if (!$.isArray(variations) || !picks.length || !$.fn.wc_variations_image_update) {
			return;
		}
		$.each(variations, function () {
			var variation = this;
			var fits = picks.every(function (pick) {
				return !variation.attributes[pick[0]] || variation.attributes[pick[0]] === pick[1];
			});
			if (fits && variation.image && variation.image.src) {
				$form.wc_variations_image_update(variation);
				return false;
			}
		});
	}
	// WooCommerce strikes out the options that do not go with the other picks. Clicking one switches to it:
	// the other picks are cleared, then put back where they still go with the new one.
	$(document).on('click', '.variations_form .ux-swatch.disabled', function () {
		var $swatch = $(this);
		var $form = $swatch.closest('.variations_form');
		var name = $swatch.closest('.ux-swatches').data('attribute_name');
		var $selects = $form.find('.variations select');
		var $own = $selects.filter(function () { return this.name === name; });
		var $others = $selects.not($own);
		var kept = [];
		if (!$own.length) {
			return;
		}
		$others.each(function () {
			kept.push([this.name, this.value]);
			this.value = '';
		});
		$own.val('').trigger('change');
		$own.val(String($swatch.data('value'))).trigger('change');
		$.each(kept, function (index, pick) {
			var $select = $selects.filter(function () { return this.name === pick[0]; });
			if (pick[1] && $select.find('option').filter(function () { return this.value === pick[1] && !this.disabled; }).length) {
				$select.val(pick[1]).trigger('change');
			}
		});
	});
	$(function () {
		$('.variations_form').each(function () {
			decorate($(this));
		});
	});
})(jQuery);
