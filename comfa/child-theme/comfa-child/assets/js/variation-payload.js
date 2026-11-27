(function ($) {
	'use strict';
	var init = $.fn.wc_variation_form;
	$.fn.wc_variation_form = function () {
		this.each(function () {
			var variations = $(this).data('product_variations');
			if (!Array.isArray(variations)) {
				return;
			}
			var records = new Map();
			variations.forEach(function (variation) {
				['image', 'price_html'].forEach(function (field) {
					if (typeof variation[field] === 'number' && records.has(variation[field])) {
						variation[field] = records.get(variation[field])[field];
					}
				});
				records.set(variation.variation_id, variation);
			});
		});
		return init.apply(this, arguments);
	};
})(jQuery);
