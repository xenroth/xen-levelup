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

	// Admin What's New dismiss
	$(document).on('click', '#xen-admin-dismiss-whats-new', function () {
		var $btn  = $(this);
		var nonce = $btn.data('nonce');
		$btn.prop('disabled', true).text('…');
		$.post(xenAdmin.ajaxUrl, {
			action: 'xen_admin_dismiss_whats_new',
			nonce:  nonce,
		}, function () {
			$('#xen-admin-whats-new').fadeOut(300, function () { $(this).remove(); });
		});
	});

	// ── Shop: WP Media Library image picker ───────────────────────────────
	var mediaFrame = null;

	$(document).on('click', '.xen-media-upload-btn', function (e) {
		e.preventDefault();
		var targetId  = $(this).data('target');
		var previewId = $(this).data('preview');

		if (typeof wp === 'undefined' || !wp.media) return;

		if (mediaFrame) {
			mediaFrame.open();
			return;
		}

		mediaFrame = wp.media({
			title   : 'Select or Upload Item Image',
			button  : { text: 'Use this image' },
			library : { type: 'image' },
			multiple: false
		});

		mediaFrame.on('select', function () {
			var attachment = mediaFrame.state().get('selection').first().toJSON();
			var url = attachment.url;
			$('#' + targetId).val(url);
			var $preview = $('#' + previewId);
			$preview.html('<img src="' + url + '" style="max-width:100px;max-height:100px;border-radius:6px;display:block;margin-bottom:6px">');
		});

		mediaFrame.open();
	});

	// Preview image URL typed manually
	$(document).on('change blur', '#xen-shop-img-url', function () {
		var url = $(this).val().trim();
		var $preview = $('#xen-shop-img-preview');
		if (url) {
			$preview.html('<img src="' + url + '" style="max-width:100px;max-height:100px;border-radius:6px;display:block;margin-bottom:6px">');
		} else {
			$preview.empty();
		}
	});

}(jQuery));
