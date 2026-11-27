(function ($) {
	'use strict';

	$(document).on('found_variation reset_data', '.cf-threadless .variations_form', function (event, variation) {
		var deal = this.closest('.cf-threadless').querySelector('.cf-studio-deal');
		if (!deal) return;
		if (deal.dataset.original === undefined) deal.dataset.original = deal.innerHTML;
		deal.innerHTML = variation && variation.cf_studio_deal || deal.dataset.original;
	});
})(jQuery);
