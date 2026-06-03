/**
 * XEN LevelUp — Onboarding JS
 * Step navigation, drag-drop priority sorting, AJAX form submission.
 * Dependencies: jQuery, jQuery UI Sortable, xen-main.js
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;
	if (!$('#xen-onboarding').length) return;

	var currentStep = parseInt($('#xen-onboarding').data('step'), 10) || 1;

	/* ----------------------------------------------------------------
	   Show a numbered step (1-based)
	   ---------------------------------------------------------------- */
	function showStep(n) {
		$('.xen-step').hide();
		if (n <= 3) {
			$('#xen-step-' + n).fadeIn(250);
		} else {
			$('#xen-step-complete').fadeIn(400);
		}
		currentStep = n;

		/* Update dots */
		$('.xen-dot').each(function (i) {
			$(this).toggleClass('active', i < n);
		});

		/* Animate bars on step transitions */
		setTimeout(function () {
			if (typeof XenAnimations !== 'undefined') {
				/* re-trigger XP bar animations if present on the new step */
			}
		}, 100);
	}

	/* ----------------------------------------------------------------
	   Live range value display
	   ---------------------------------------------------------------- */
	$(document).on('input', '.xen-range', function () {
		$(this).siblings('.xen-range-val').text(this.value);
	});

	/* ----------------------------------------------------------------
	   BACK button
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-prev-step', function () {
		if (currentStep > 1) showStep(currentStep - 1);
	});

	/* ----------------------------------------------------------------
	   jQuery UI Sortable — Priority drag-drop
	   ---------------------------------------------------------------- */
	if ($.fn.sortable && $('#xen-priority-sortable').length) {
		$('#xen-priority-sortable').sortable({
			handle: '.xen-drag-handle',
			placeholder: 'xen-priority-placeholder',
			tolerance: 'pointer',
			start: function (e, ui) {
				ui.item.addClass('xen-dragging');
			},
			stop: function (e, ui) {
				ui.item.removeClass('xen-dragging');
			}
		});
	}

	/* ----------------------------------------------------------------
	   Collect form data helpers
	   ---------------------------------------------------------------- */
	function collectStep1() {
		var data = {};
		$('#xen-step1-form .xen-range').each(function () {
			var name = $(this).attr('name');
			if (name) data[name] = parseInt(this.value, 10);
		});
		return data;
	}

	function collectStep2() {
		var data = {};
		$('#xen-step2-form .xen-range').each(function () {
			var name = $(this).attr('name');
			if (name) data[name] = parseInt(this.value, 10);
		});
		return data;
	}

	function collectStep3() {
		var order = [];
		$('#xen-priority-sortable .xen-priority-item').each(function () {
			order.push($(this).data('value'));
		});
		return order;
	}

	/* ----------------------------------------------------------------
	   Submit helpers (disable/enable button + spinner)
	   ---------------------------------------------------------------- */
	function setSubmitState($btn, loading) {
		$btn.prop('disabled', loading);
		if (loading) {
			$btn.data('orig-text', $btn.text()).html('<span class="xen-spinner"></span>');
		} else {
			$btn.html($btn.data('orig-text') || 'Next →');
		}
	}

	/* ----------------------------------------------------------------
	   STEP 1 submit
	   ---------------------------------------------------------------- */
	$('#xen-step1-form').on('submit', function (e) {
		e.preventDefault();
		var $btn = $(this).find('[type=submit]');
		setSubmitState($btn, true);

		xenRequest('xen_save_onboarding_step', {
			step: 1,
			data: JSON.stringify(collectStep1())
		}).done(function (res) {
			if (res.success) {
				showStep(2);
			} else {
				xenToast(res.data || 'Save failed.', 'error');
			}
		}).always(function () {
			setSubmitState($btn, false);
		});
	});

	/* ----------------------------------------------------------------
	   STEP 2 submit
	   ---------------------------------------------------------------- */
	$('#xen-step2-form').on('submit', function (e) {
		e.preventDefault();
		var $btn = $(this).find('[type=submit]');
		setSubmitState($btn, true);

		xenRequest('xen_save_onboarding_step', {
			step: 2,
			data: JSON.stringify(collectStep2())
		}).done(function (res) {
			if (res.success) {
				showStep(3);
			} else {
				xenToast(res.data || 'Save failed.', 'error');
			}
		}).always(function () {
			setSubmitState($btn, false);
		});
	});

	/* ----------------------------------------------------------------
	   STEP 3 submit → triggers full onboarding completion
	   ---------------------------------------------------------------- */
	$('#xen-step3-form').on('submit', function (e) {
		e.preventDefault();
		var $btn = $(this).find('[type=submit]');
		setSubmitState($btn, true);

		/* Save priorities first, then complete */
		xenRequest('xen_save_onboarding_step', {
			step: 3,
			data: JSON.stringify(collectStep3())
		}).done(function (res) {
			if (!res.success) {
				xenToast(res.data || 'Save failed.', 'error');
				setSubmitState($btn, false);
				return;
			}

			/* Complete onboarding */
			xenRequest('xen_complete_onboarding', {})
				.done(function (res2) {
					if (res2.success) {
						showStep(4);

						/* Update completion message if server provides one */
						if (res2.data && res2.data.message) {
							$('#xen-awaken-msg').text(res2.data.message);
						}

						/* Particle burst effect */
						if (typeof XenAnimations !== 'undefined') {
							var cx = window.innerWidth  / 2;
							var cy = window.innerHeight / 2;
							XenAnimations.particleBurst(cx, cy, '#00D4FF', 80);
							XenAnimations.particleBurst(cx, cy, '#7B61FF', 40);
						}
					} else {
						xenToast(res2.data || 'Completion failed.', 'error');
						setSubmitState($btn, false);
					}
				});
		});
	});

	/* ----------------------------------------------------------------
	   Init: show the correct step
	   ---------------------------------------------------------------- */
	$(document).ready(function () {
		showStep(currentStep <= 0 ? 1 : Math.min(currentStep, 4));
	});

})(jQuery);
