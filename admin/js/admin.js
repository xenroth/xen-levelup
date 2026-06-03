/* XEN LevelUp — Admin JS */
(function ($) {
	'use strict';

	// Generic confirm for destructive actions
	$(document).on('click', '.xen-confirm-action', function (e) {
		if (!confirm(xenAdmin.i18n.confirmReset)) {
			e.preventDefault();
		}
	});

	// Inline AJAX save (future use)
	$(document).on('submit', '.xen-ajax-form', function (e) {
		e.preventDefault();
		var $form = $(this);
		var $btn  = $form.find('[type=submit]');
		$btn.text(xenAdmin.i18n.saving).prop('disabled', true);
		$.post(xenAdmin.ajaxUrl, $form.serialize(), function (res) {
			$btn.text(xenAdmin.i18n.saved).prop('disabled', false);
			setTimeout(function () { $btn.text($btn.data('label') || $btn.text()); }, 2000);
		});
	});

}(jQuery));
