/**
 * XEN LevelUp — Quest Hub JS (v1.3.0)
 * Handles: tab switching, quest accept, quest complete for hub page.
 * Dependencies: xen-main.js (xenRequest, xenToast, xenEscape)
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;

	var $hub = $('#xen-quest-hub');
	if (!$hub.length) return;

	var nonce = $hub.data('nonce') || xenData.nonce;

	/* ----------------------------------------------------------------
	   Tab switching
	   ---------------------------------------------------------------- */
	$hub.on('click', '.xen-hub-tab', function () {
		var tab = $(this).data('tab');
		$hub.find('.xen-hub-tab').removeClass('xen-hub-tab-active').attr('aria-selected', 'false');
		$(this).addClass('xen-hub-tab-active').attr('aria-selected', 'true');

		$hub.find('.xen-hub-panel').addClass('xen-hub-panel-hidden');
		$('#xen-panel-' + tab).removeClass('xen-hub-panel-hidden');
	});

	/* ----------------------------------------------------------------
	   Accept Quest
	   ---------------------------------------------------------------- */
	$hub.on('click', '.xen-accept-quest', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var questId = parseInt($btn.data('id'), 10);
		if (!questId || $btn.prop('disabled')) return;

		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_accept_quest', { quest_id: questId, nonce: nonce })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data && res.data.message ? res.data.message : 'Could not accept quest.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				var $card = $btn.closest('.xen-hub-card');
				$card.removeClass('xen-hub-card-pending').addClass('xen-hub-card-active');
				$btn.removeClass('xen-btn-accept xen-accept-quest')
				    .addClass('xen-btn-complete xen-complete-quest')
				    .prop('disabled', false)
				    .text(
				    	$card.hasClass('xen-hub-card-legendary')
				    		? 'Claim Victory'
				    		: 'Submit'
				    );
				xenToast('Quest accepted! Begin your journey.', 'success', 'Quest Accepted');
			})
			.fail(function () {
				xenToast('Request failed. Try again.', 'error');
				$btn.prop('disabled', false).text(origText);
			});
	});

	/* ----------------------------------------------------------------
	   Complete Quest (reuses existing handler signature)
	   ---------------------------------------------------------------- */
	$hub.on('click', '.xen-complete-quest', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var questId = parseInt($btn.data('id'), 10);
		if (!questId || $btn.prop('disabled')) return;

		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_complete_quest', { quest_id: questId, nonce: nonce })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data && res.data.message ? res.data.message : 'Could not complete quest.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				var $card  = $btn.closest('.xen-hub-card');
				var $actions = $btn.closest('.xen-hub-card-actions');
				$card.addClass('xen-quest-done');
				$actions.html('<span class="xen-done-badge">✓ Completed</span>');

				var xpGained    = (res.data && res.data.xp_earned)    || 0;
				var coinsGained = (res.data && res.data.coins_earned) || 0;
				xenToast(
					'+' + xpGained + ' XP' + (coinsGained ? '  +' + coinsGained + ' coins' : ''),
					'xp',
					'Quest Complete!'
				);

				if (res.data && res.data.leveled_up) {
					$(document).trigger('xen:levelUp', [{ level: res.data.new_level, rank: res.data.rank_title || '' }]);
				}

				/* Update daily counter */
				if ($card.closest('#xen-panel-daily').length) {
					var $counter = $hub.find('.xen-hub-progress-label');
					var match    = $counter.text().match(/(\d+)\s*\/\s*(\d+)/);
					if (match) {
						var done  = parseInt(match[1], 10) + 1;
						var total = parseInt(match[2], 10);
						$counter.text('Daily: ' + done + ' / ' + total);
						var pct = Math.round(done / total * 100);
						$hub.find('.xen-hub-progress-fill').css('width', pct + '%');
						/* Update tab count */
						$hub.find('[data-tab="daily"] .xen-tab-count').text(done + '/' + total);
					}
				}

				if (typeof XenAnimations !== 'undefined') {
					var off = $card.offset();
					var col = $card.hasClass('xen-hub-card-legendary') ? '#FF6B35' : '#00D4FF';
					XenAnimations.particleBurst(off.left + $card.outerWidth() / 2, off.top + 20, col, 25);
				}
			})
			.fail(function () {
				xenToast('Request failed. Try again.', 'error');
				$btn.prop('disabled', false).text(origText);
			});
	});

})(jQuery);
