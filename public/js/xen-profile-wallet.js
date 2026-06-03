/**
 * XEN LevelUp — Profile Edit + Wallet JS (v1.3.0)
 * Handles: profile inline editing, wallet send, wallet tab switching.
 * Dependencies: xen-main.js (xenRequest, xenToast)
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;

	/* ================================================================
	   PROFILE EDITING
	   ================================================================ */
	var $wrap   = $('#xen-profile-wrap');
	if ($wrap.length) {
		var profileNonce = $wrap.data('nonce');

		$('#xen-profile-edit-toggle').on('click', function () {
			$('#xen-profile-edit-panel').slideDown(200);
			$(this).hide();
		});

		$('#xen-profile-cancel-btn').on('click', function () {
			$('#xen-profile-edit-panel').slideUp(200);
			$('#xen-profile-edit-toggle').show();
		});

		$('#xen-profile-save-btn').on('click', function () {
			var $btn         = $(this);
			var displayName  = $('#xen-edit-display-name').val().trim();
			var bio          = $('#xen-edit-bio').val().trim();
			var title        = $('#xen-edit-title').val().trim();

			if (!displayName) {
				xenToast('Display name cannot be empty.', 'error');
				return;
			}

			$btn.prop('disabled', true).text('Saving…');

			xenRequest('xen_update_profile', {
				nonce:        profileNonce,
				display_name: displayName,
				bio:          bio,
				title:        title
			}).done(function (res) {
				if (!res.success) {
					xenToast(res.data && res.data.message ? res.data.message : 'Could not save.', 'error');
					$btn.prop('disabled', false).text('Save Changes');
					return;
				}

				/* Update displayed values in-page */
				$wrap.find('.xen-profile-name').text(res.data.display_name);
				var $bio = $('#xen-bio-display');
				if (res.data.bio) {
					$bio.text(res.data.bio).removeClass('xen-bio-empty');
				} else {
					$bio.text('No bio yet. Click Edit to add one.').addClass('xen-bio-empty');
				}
				if (res.data.title) {
					var $titleEl = $wrap.find('.xen-profile-hunter-title');
					if ($titleEl.length) {
						$titleEl.text('\u300c' + res.data.title + '\u300d');
					}
				}

				$btn.prop('disabled', false).text('Save Changes');
				$('#xen-profile-edit-panel').slideUp(200);
				$('#xen-profile-edit-toggle').show();
				xenToast('Profile updated!', 'success', 'Saved');
			}).fail(function () {
				xenToast('Request failed. Try again.', 'error');
				$btn.prop('disabled', false).text('Save Changes');
			});
		});
	}

	/* ================================================================
	   WALLET TABS (shared tab component — also used by quest hub)
	   ================================================================ */
	$(document).on('click', '.xen-wallet-tabs .xen-hub-tab', function () {
		var $container = $(this).closest('.xen-wallet');
		var tab        = $(this).data('tab');

		$(this).closest('.xen-hub-tabs').find('.xen-hub-tab')
			.removeClass('xen-hub-tab-active').attr('aria-selected', 'false');
		$(this).addClass('xen-hub-tab-active').attr('aria-selected', 'true');

		$container.find('.xen-hub-panel').addClass('xen-hub-panel-hidden');
		$container.find('#xen-panel-' + tab).removeClass('xen-hub-panel-hidden');
	});

	/* ================================================================
	   CURRENCY TRANSFER (SEND)
	   ================================================================ */
	var $wallet = $('#xen-wallet-wrap');
	if ($wallet.length) {
		var walletNonce = $wallet.data('nonce');

		$('#xen-send-btn').on('click', function () {
			var $btn      = $(this);
			var toUserId  = parseInt($('#xen-send-to').val(), 10);
			var amount    = parseInt($('#xen-send-amount').val(), 10);
			var note      = $('#xen-send-note').val().trim();
			var $result   = $('#xen-send-result');

			if (!toUserId) {
				$result.removeClass('success').addClass('error').text('Please select a recipient.').show();
				return;
			}
			if (!amount || amount < 1) {
				$result.removeClass('success').addClass('error').text('Please enter a valid amount.').show();
				return;
			}

			$btn.prop('disabled', true).text('Sending…');
			$result.hide();

			xenRequest('xen_transfer_currency', {
				nonce:      walletNonce,
				to_user_id: toUserId,
				amount:     amount,
				note:       note
			}).done(function (res) {
				if (!res.success) {
					var msg = res.data && res.data.message ? res.data.message : 'Transfer failed.';
					$result.removeClass('success').addClass('error').text(msg).show();
					$btn.prop('disabled', false).text('📤 Send Coins');
					return;
				}

				/* Update balance display */
				var newBalance = res.data && res.data.sender_balance;
				if (typeof newBalance !== 'undefined') {
					$('#xen-wallet-balance').html(
						newBalance.toLocaleString() +
						'<span class="xen-wallet-currency">' +
						$('#xen-wallet-balance').find('.xen-wallet-currency').text() +
						'</span>'
					);
					$('#xen-send-amount').attr('max', newBalance);
					$('.xen-form-hint').text('Available: ' + newBalance.toLocaleString());
				}

				$result.removeClass('error').addClass('success')
					.text('Sent ' + amount + ' coins successfully!').show();
				$('#xen-send-to').val('');
				$('#xen-send-amount').val('');
				$('#xen-send-note').val('');
				$btn.prop('disabled', false).text('📤 Send Coins');
				xenToast('Sent ' + amount + ' coins!', 'success', 'Transfer Complete');
			}).fail(function () {
				$result.removeClass('success').addClass('error').text('Request failed. Try again.').show();
				$btn.prop('disabled', false).text('📤 Send Coins');
			});
		});
	}

}(jQuery));
