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

	// Variation pickers: selected value next to the label, fit notes on size buttons, giving line.
	function decorate($form) {
		$form.find('.cf-attr-value').each(function () {
			var $value = $(this);
			var $select = $form.find('select[name="attribute_' + $value.data('cf-attr') + '"]');
			var text = $select.val() ? $select.find('option:selected').text() : '';
			$value.text(text);
		});
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
	});
	// Designs sold as Product (style) x Color x Size: only offer the colours and sizes the chosen product comes in.
	function styleMap($form) {
		var data = $form.data('cfStyleMap');
		if (data === undefined) {
			data = null;
			try { data = JSON.parse($form.find('.cf-style-map').first().text()); } catch (e) {}
			$form.data('cfStyleMap', data && data.styles ? data : null);
		}
		return $form.data('cfStyleMap');
	}
	function applyStyle($form) {
		var data = styleMap($form);
		if (!data) {
			return;
		}
		var spec = data.styles[$form.find('select[name="attribute_pa_style"]').val()] || null;
		var changed = false;
		$.each({ pa_color: 'colors', pa_size: 'sizes' }, function (attribute, key) {
			var select = $form.find('select[name="attribute_' + attribute + '"]')[0];
			var allowed = spec ? spec[key] : null;
			if (!select) {
				return;
			}
			$.each(select.options, function () {
				this.disabled = !!this.value && !!allowed && allowed.indexOf(this.value) === -1;
			});
			if (select.value && allowed && allowed.indexOf(select.value) === -1) {
				// Keep a colour picked so the photo always shows the chosen product; a size has to be chosen again.
				select.value = attribute === 'pa_color' && allowed.length ? allowed[0] : '';
				changed = true;
			}
		});
		$form.addClass('cf-has-styles').trigger('woocommerce_update_variation_values');
		if (changed) {
			$form.find('select[name="attribute_pa_color"]').trigger('change');
		}
	}
	// Until a size is picked WooCommerce has no variation to show, so preview the product and colour.
	function previewStyle($form) {
		var data = styleMap($form);
		var style = $form.find('select[name="attribute_pa_style"]').val();
		var spec = data && data.styles[style];
		if (!spec || $form.find('select[name="attribute_pa_size"]').val()) {
			return;
		}
		var $price = mainPrice($form);
		if ($price.length) {
			if ($price.data('cfOriginal') === undefined) {
				$price.data('cfOriginal', $price.html());
			}
			$price.html('<span class="cf-price"><span class="cf-price__now">' + spec.price_html + '</span></span>');
		}
		var color = $form.find('select[name="attribute_pa_color"]').val();
		var params = window.wc_add_to_cart_variation_params;
		if (!color || !spec.sizes || !spec.sizes.length || !params || !$.fn.wc_variations_image_update) {
			return;
		}
		var key = style + '|' + color;
		var cache = $form.data('cfPreviews') || {};
		$form.data('cfPreviews', cache).data('cfPreviewKey', key);
		var show = function (variation) {
			if (!variation || $form.data('cfPreviewKey') !== key || $form.find('select[name="attribute_pa_size"]').val()) {
				return;
			}
			// Quick View shows the photo in a Flatsome slider rather than the WooCommerce gallery.
			var $slider = $form.closest('.product-lightbox').find('.product-gallery-slider');
			if ($slider.length) {
				if (variation.image && variation.image.src) {
					$slider.find('.slide.first img').attr('src', variation.image.src).attr('srcset', '');
					if ($slider.data('flickity')) {
						$slider.flickity('select', 0);
					}
				}
				return;
			}
			$form.wc_variations_image_update(variation);
		};
		if (cache[key]) {
			show(cache[key]);
			return;
		}
		$.post(params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_variation'), {
			product_id: data.product,
			attribute_pa_style: style,
			attribute_pa_color: color,
			attribute_pa_size: spec.sizes[0]
		}).done(function (variation) {
			if (variation && variation.variation_id) {
				cache[key] = variation;
				show(variation);
			}
		});
	}
	$(document).on('change', '.variations_form select[name="attribute_pa_style"]', function () {
		applyStyle($(this).closest('.variations_form'));
	});
	$(document).on('wc_variation_form', '.variations_form', function () {
		applyStyle($(this));
		previewStyle($(this));
	});
	$(document).on('reset_data', '.variations_form', function () {
		var $form = $(this);
		// "Clear" empties the product picker after this event, so read the pickers once it has.
		window.setTimeout(function () {
			applyStyle($form);
			previewStyle($form);
		}, 0);
	});
	$(function () {
		$('.variations_form').each(function () {
			decorate($(this));
			applyStyle($(this));
			previewStyle($(this));
		});
	});
})(jQuery);
