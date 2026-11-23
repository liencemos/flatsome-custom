(function ($) {
	'use strict';

	$('.variations_form').each(function () {
		const $form = $(this);
		const $groups = $form.find('.edit-swatches');
		if (!$groups.length) return;

		function sync() {
			$groups.each(function () {
				const $group = $(this);
				const $select = $form.find('select[name="attribute_' + $group.data('attribute') + '"]');
				if (!$select.length) return;
				const select = $select[0];
				$group.find('button').each(function () {
					const option = Array.from(select.options).find(item => item.value === this.dataset.value);
					this.disabled = !option || option.disabled;
					this.setAttribute('aria-pressed', String(select.value === this.dataset.value));
				});
				$select.prop('hidden', true);
				$group.prop('hidden', false);
			});
		}

		$form.on('click', '.edit-swatch', function () {
			const name = this.closest('.edit-swatches').dataset.attribute;
			$form.find('select[name="attribute_' + name + '"]').val(this.dataset.value).trigger('change');
		});
		$form.on('woocommerce_update_variation_values wc_variation_form change', sync);
		sync();
	});
})(jQuery);
