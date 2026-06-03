/**
 * XEN LevelUp — Quests JS
 * Shared handler for daily / special / legendary quest completion.
 * Dependencies: xen-main.js, xen-animations.js
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;
	/* Run on any page that has quest complete buttons */
	if (!$('.xen-complete-quest, .xen-legendary-complete').length) return;

	/* ----------------------------------------------------------------
	   Quest completion (daily, special, legendary)
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-complete-quest, .xen-legendary-complete', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var questId = parseInt($btn.data('quest-id'), 10);
		if (!questId || $btn.prop('disabled')) return;

		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_complete_quest', { quest_id: questId })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data || 'Could not complete quest.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				/* Mark card as done */
				var $card = $btn.closest(
					'.xen-quest-card, .xen-daily-card, .xen-special-card, .xen-legendary-card'
				);
				$card.addClass('xen-quest-done xen-quest-complete-flash');
				$btn.closest('.xen-quest-footer, .xen-daily-card-footer, .xen-special-card-footer, .xen-legendary-card-footer')
					.find('.xen-btn').prop('disabled', true);
				$btn.replaceWith('<span class="xen-done-badge">✓ Completed</span>');

				/* Toast */
				var xpGained    = (res.data && res.data.xp)    || 0;
				var coinsGained = (res.data && res.data.coins) || 0;
				xenToast(
					'+' + xpGained + ' XP' + (coinsGained ? '  +' + coinsGained + ' coins' : ''),
					'xp',
					'Quest Complete!'
				);

				/* Level-up */
				if (res.data && res.data.leveled_up) {
					$(document).trigger('xen:levelUp', [{
						level: res.data.new_level,
						rank:  res.data.rank_title || ''
					}]);
				}

				/* Particles */
				if (typeof XenAnimations !== 'undefined' && $card.length) {
					var off = $card.offset();
					var col = $card.hasClass('xen-legendary-card') ? '#FF6B35' :
					          $card.hasClass('xen-special-card')   ? '#FFD700' : '#00D4FF';
					XenAnimations.particleBurst(
						off.left + $card.outerWidth() / 2,
						off.top  + 20,
						col, 30
					);
				}
			});
	});

	/* ----------------------------------------------------------------
	   Update total completed counter (if present on page)
	   ---------------------------------------------------------------- */
	$(document).on('xen:questCompleted', function () {
		var $counter = $('.xen-daily-counter');
		if (!$counter.length) return;
		var parts = $counter.text().match(/(\d+)\s*\/\s*(\d+)/);
		if (parts) {
			var done  = parseInt(parts[1], 10) + 1;
			var total = parseInt(parts[2], 10);
			$counter.text(done + ' / ' + total + ' completed');
		}
	});

})(jQuery);
