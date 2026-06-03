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

        /* ── What's New Dismiss ───────────────────────────────────────── */
        $(document).on('click', '#xen-dismiss-whats-new', function () {
                var $card = $('#xen-whats-new');
                $.post(xenData.ajaxUrl, {
                        action : 'xen_dismiss_whats_new',
                        nonce  : xenData.nonce
                }).always(function () {
                        $card.slideUp(300, function () { $card.remove(); });
                });
        });

        /* ── Daily Check-In ───────────────────────────────────────────── */
        $(document).on('click', '#xen-checkin-btn', function () {
                var $btn = $(this);
                $btn.prop('disabled', true).text(xenData.i18n.processing || 'Processing…');

                $.post(xenData.ajaxUrl, {
                        action : 'xen_daily_checkin',
                        nonce  : xenData.nonce
                }, function (res) {
                        if (!res.success) {
                                $btn.prop('disabled', false).text('📅 Check In Today');
                                xenToast(
                                        (res.data && res.data.message) || (xenData.i18n.error || 'Error.'),
                                        'error'
                                );
                                return;
                        }

                        var d = res.data;

                        /* Update streak display */
                        $('#xen-streak-count').text(d.streak);

                        /* Replace button with done message */
                        $btn.replaceWith('<div class="xen-checkin-done">✅ Checked in today!</div>');

                        /* Toast reward */
                        var sym = xenData.currencySymbol || '🪙';
                        xenToast(
                                '📅 Day ' + d.streak + ' streak! +' + d.xp + ' XP  ' + sym + ' +' + d.coins,
                                'xp',
                                'Check-In!'
                        );

                        /* Level-up check */
                        if (d.leveled_up) {
                                $(document).trigger('xen:levelUp', [{
                                        level : d.new_level,
                                        rank  : d.rank_title || ''
                                }]);
                                $('.xen-level-number').text(d.new_level);
                        }

                        /* Particle burst */
                        if (typeof XenAnimations !== 'undefined') {
                                var $checkin = $('#xen-checkin-card');
                                var offset   = $checkin.offset();
                                XenAnimations.particleBurst(
                                        offset.left + $checkin.outerWidth() / 2,
                                        offset.top,
                                        '#7B61FF', 30
                                );
                        }
                }).fail(function () {
                        $btn.prop('disabled', false).text('📅 Check In Today');
                        xenToast(xenData.i18n.error || 'Something went wrong.', 'error');
                });
        });

	/* ── Notification Bell ─────────────────────────────────────── */
	var notifLoaded = false;

	$(document).on('click', '#xen-notif-btn', function (e) {
		e.stopPropagation();
		var $panel = $('#xen-notif-panel');
		var isOpen = $panel.hasClass('xen-notif-open');

		$panel.toggleClass('xen-notif-open', !isOpen);
		$(this).attr('aria-expanded', !isOpen);
		$panel.attr('aria-hidden', isOpen);

		if (!isOpen && !notifLoaded) {
			loadNotifications();
		}
	});

	// Close on outside click
	$(document).on('click', function (e) {
		if (!$(e.target).closest('#xen-notif-wrap').length) {
			$('#xen-notif-panel').removeClass('xen-notif-open').attr('aria-hidden', 'true');
			$('#xen-notif-btn').attr('aria-expanded', 'false');
		}
	});

	// Mark all read
	$(document).on('click', '#xen-notif-mark-all', function () {
		var $btn = $(this);
		$btn.prop('disabled', true);
		$.post(xenData.ajaxUrl, {
			action: 'xen_mark_all_notifications_read',
			nonce : xenData.nonce
		}, function () {
			$('#xen-notif-list .xen-notif-item').removeClass('xen-notif-unread');
			var $cnt = $('#xen-notif-count');
			$cnt.text('0').hide();
			$btn.prop('disabled', false);
		});
	});

	// Mark single notification read on click
	$(document).on('click', '.xen-notif-item', function () {
		var $item = $(this);
		var id    = parseInt($item.data('id'), 10);
		if (!id || !$item.hasClass('xen-notif-unread')) return;

		$.post(xenData.ajaxUrl, {
			action: 'xen_mark_notification_read',
			nonce : xenData.nonce,
			notif_id: id
		}, function () {
			$item.removeClass('xen-notif-unread');
			var $cnt = $('#xen-notif-count');
			var cur = Math.max(0, (parseInt($cnt.text(), 10) || 0) - 1);
			$cnt.text(cur);
			if (cur === 0) $cnt.hide();
		});
	});

	function loadNotifications() {
		notifLoaded = true;
		$.post(xenData.ajaxUrl, {
			action: 'xen_get_notifications',
			nonce : xenData.nonce,
			limit : 20
		}, function (res) {
			var $list = $('#xen-notif-list').empty();
			if (!res.success || !res.data.notifications.length) {
				$list.html('<div class="xen-notif-empty">No notifications yet.</div>');
				return;
			}
			$.each(res.data.notifications, function (i, n) {
				var unread = !parseInt(n.is_read, 10);
				var icons  = { level_up: '⬆️', quest: '⚔️', achievement: '🏆', checkin: '📅', task: '📋' };
				var icon   = icons[n.type] || '🔔';
				$list.append(
					'<div class="xen-notif-item' + (unread ? ' xen-notif-unread' : '') + '" data-id="' + n.id + '">' +
					'<span class="xen-notif-icon">' + icon + '</span>' +
					'<div class="xen-notif-body">' +
					'<div class="xen-notif-title">' + esc(n.title) + '</div>' +
					(n.message ? '<div class="xen-notif-msg">' + esc(n.message) + '</div>' : '') +
					'</div>' +
					'</div>'
				);
			});
		});
	}

	function esc(str) {
		return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

}(jQuery));

