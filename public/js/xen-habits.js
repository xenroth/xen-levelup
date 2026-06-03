/**
 * XEN LevelUp — Habits JS
 * Log habit, add habit form, deactivate, streak updates.
 * Dependencies: xen-main.js
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;
	if (!$('.xen-habits').length) return;

	/* ----------------------------------------------------------------
	   Toggle "Add habit" form
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-toggle-form-btn', function () {
		var $form = $($(this).data('target') || '.xen-add-form-wrap .xen-form-collapse');
		$form.toggleClass('xen-open');
		var isOpen = $form.hasClass('xen-open');
		$(this).text(isOpen ? '− Close' : '+ Add Habit');
	});

	/* ----------------------------------------------------------------
	   Add habit form submit
	   ---------------------------------------------------------------- */
	$(document).on('submit', '#xen-add-habit-form', function (e) {
		e.preventDefault();
		var $form  = $(this);
		var $btn   = $form.find('[type=submit]');
		var title    = $.trim($form.find('input[name=title]').val());
		var category = $form.find('select[name=category]').val();
		var freq     = $form.find('select[name=frequency]').val() || 'daily';

		if (!title) {
			xenToast('Please enter a habit title.', 'error');
			return;
		}

		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_create_habit', {
			title:    title,
			category: category,
			frequency: freq
		}).done(function (res) {
			if (!res.success) {
				xenToast(res.data || 'Could not add habit.', 'error');
				return;
			}
			xenToast('Habit added!', 'success');
			/* Reload to show new habit */
			window.location.reload();
		}).always(function () {
			$btn.prop('disabled', false).text(origText);
		});
	});

	/* ----------------------------------------------------------------
	   Log habit (done for today)
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-log-habit', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var habitId = parseInt($btn.data('habit-id'), 10);
		if (!habitId || $btn.prop('disabled')) return;

		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_log_habit', { habit_id: habitId })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data || 'Could not log habit.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				var $card = $btn.closest('.xen-habit-card');
				$card.addClass('xen-habit-logged');
				$btn.replaceWith('<span class="xen-habit-done">✓ Logged</span>');

				/* Update streak display */
				var newStreak = (res.data && res.data.streak) || 0;
				$card.find('.xen-streak-value').text(newStreak);

				/* Toast */
				var xpGained = (res.data && res.data.xp) || 0;
				xenToast('+' + xpGained + ' XP  Habit logged!', 'xp', 'Well done!');

				/* Level-up */
				if (res.data && res.data.leveled_up) {
					$(document).trigger('xen:levelUp', [{
						level: res.data.new_level,
						rank:  res.data.rank_title || ''
					}]);
				}
			});
	});

	/* ----------------------------------------------------------------
	   Deactivate habit
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-deactivate-habit', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var habitId = parseInt($btn.data('habit-id'), 10);
		if (!habitId) return;

		if (!window.confirm(xenData.i18n.confirm_deactivate || 'Deactivate this habit?')) return;

		$btn.prop('disabled', true);

		xenRequest('xen_deactivate_habit', { habit_id: habitId })
			.done(function (res) {
				if (res.success) {
					$btn.closest('.xen-habit-card').fadeOut(300, function () { $(this).remove(); });
					xenToast('Habit deactivated.', 'success');
				} else {
					xenToast(res.data || 'Could not deactivate habit.', 'error');
					$btn.prop('disabled', false);
				}
			});
	});

})(jQuery);
