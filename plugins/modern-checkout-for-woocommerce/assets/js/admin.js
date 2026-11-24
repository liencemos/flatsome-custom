(function ($) {
	'use strict';

	$(document).on('click', '.mcwc-select-logo', function (event) {
		event.preventDefault();
		const frame = wp.media({
			title: mcwcAdmin.mediaTitle,
			button: { text: mcwcAdmin.mediaButton },
			library: { type: 'image' },
			multiple: false
		});
		frame.on('select', function () {
			const attachment = frame.state().get('selection').first().toJSON();
			$('#mcwc_logo_id').val(attachment.id);
			$('.mcwc-logo-preview').html('<img src="' + $('<div>').text(attachment.url).html() + '" alt="">');
		});
		frame.open();
	});

	$(document).on('click', '.mcwc-remove-logo', function (event) {
		event.preventDefault();
		$('#mcwc_logo_id').val('');
		$('.mcwc-logo-preview').empty();
	});
})(jQuery);
