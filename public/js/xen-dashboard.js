/**
 * XEN LevelUp — Dashboard JS
 * Quest completion on the dashboard view, XP animation trigger.
 * Dependencies: xen-main.js, xen-animations.js
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;
	if (!$('.xen-dashboard').length) return;

	/* ----------------------------------------------------------------
	   Complete a quest from the dashboard's quest list
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-complete-quest', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var questId = parseInt($btn.data('quest-id'), 10);
		if (!questId || $btn.prop('disabled')) return;

		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_complete_quest', { quest_id: questId })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data || 'Could not complete quest.', 'error');
					$btn.prop('disabled', false).text('Complete');
					return;
				}

				var $card = $btn.closest('.xen-quest-card, .xen-daily-card');
				$card.addClass('xen-quest-done xen-quest-complete-flash');
				$btn.replaceWith('<span class="xen-done-badge">✓ Done</span>');

				/* XP + coins feedback */
				var xpGained    = (res.data && res.data.xp)    || 0;
				var coinsGained = (res.data && res.data.coins) || 0;
				if (xpGained) {
					xenToast('+' + xpGained + ' XP' + (coinsGained ? '  +' + coinsGained + ' coins' : ''), 'xp', 'Quest Complete!');
				}

				/* Level-up check */
				if (res.data && res.data.leveled_up) {
					$(document).trigger('xen:levelUp', [{
						level: res.data.new_level,
						rank:  res.data.rank_title || ''
					}]);

					/* Update displayed level number */
					$('.xen-level-number').text(res.data.new_level);
				}

				/* Refresh XP bar */
				if (res.data && typeof res.data.xp_progress !== 'undefined') {
					$('.xen-xp-fill').css('width', res.data.xp_progress + '%');
					$('.xen-xp-text').text(res.data.xp_current + ' / ' + res.data.xp_next + ' XP');
				}

				/* Particle burst at button's position */
				if (typeof XenAnimations !== 'undefined') {
					var offset = $card.offset();
					XenAnimations.particleBurst(
						offset.left + $card.outerWidth() / 2,
						offset.top,
						'#00D4FF', 25
					);
				}
			});
	});

})(jQuery);
