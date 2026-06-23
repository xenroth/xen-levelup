/**
 * XEN LevelUp — Main JS
 * Global utilities: AJAX helper, toast, notifications, nonce management.
 * Must be loaded first (or at least before other XEN scripts).
 */
(function ($) {
	'use strict';

	 /* -----------------------------------------------------------------
		 Guard: ensure localized data object exists. Don't abort — make
		 the runtime tolerant so other frontend modules can initialize
		 (they may still no-op if required fields are missing).
		 ----------------------------------------------------------------- */
	 if (typeof window.xenData === 'undefined') window.xenData = {};
	 var xenData = window.xenData;

	/* -----------------------------------------------------------------
	   Ensure toast container exists
	   ----------------------------------------------------------------- */
	function ensureToastContainer() {
		if (!$('#xen-toast-container').length) {
			$('body').append('<div id="xen-toast-container"></div>');
		}
	}

	/* -----------------------------------------------------------------
	   showToast(message, type [, title])
	   type: 'success' | 'error' | 'xp' | 'achievement' | 'info'
	   ----------------------------------------------------------------- */
	function showToast(message, type, title) {
		ensureToastContainer();
		type  = type  || 'info';
		title = title || '';

		var $t = $(
			'<div class="xen-toast xen-toast-' + type + '">' +
				(title ? '<div class="xen-toast-title">' + escapeHtml(title) + '</div>' : '') +
				'<div class="xen-toast-message">' + escapeHtml(message) + '</div>' +
			'</div>'
		);

		$('#xen-toast-container').prepend($t);
		setTimeout(function () {
			$t.css({ transition: 'opacity .3s', opacity: 0 });
			setTimeout(function () { $t.remove(); }, 350);
		}, 4000);
	}

	/* -----------------------------------------------------------------
	   xenRequest(action, data) → jQuery Deferred
	   All AJAX calls go through this helper.
	   ----------------------------------------------------------------- */
	function xenRequest(action, data) {
		data = data || {};
		data.action = action;
		data.nonce  = xenData.nonce;

		return $.post(xenData.ajaxUrl, data)
			.fail(function () {
				showToast(xenData.i18n.error || 'An error occurred.', 'error');
			});
	}

	/* -----------------------------------------------------------------
	   HTML escape helper (never insert raw strings)
	   ----------------------------------------------------------------- */
	function escapeHtml(str) {
		if (!str) return '';
		return String(str)
			.replace(/&/g,  '&amp;')
			.replace(/</g,  '&lt;')
			.replace(/>/g,  '&gt;')
			.replace(/"/g,  '&quot;')
			.replace(/'/g,  '&#039;');
	}

	/* -----------------------------------------------------------------
	   NOTIFICATIONS
	   ----------------------------------------------------------------- */
	var notifPollInterval = null;

	function loadNotifications() {
		if (!xenData.isLoggedIn) return;

		xenRequest('xen_get_notifications', { unread_only: 1 })
			.done(function (res) {
				if (!res.success) return;
				var items  = res.data.notifications || [];
				var unread = res.data.unread_count   || 0;

				/* Badge */
				var $badge = $('.xen-notif-count');
				if (unread > 0) {
					$badge.text(unread > 99 ? '99+' : unread).show();
				} else {
					$badge.hide();
				}

				/* Dropdown list */
				var $list = $('.xen-notif-list');
				if (!$list.length) return;
				$list.empty();

				if (items.length === 0) {
					$list.append('<div class="xen-notif-empty">' + escapeHtml(xenData.i18n.no_notifications || 'No new notifications.') + '</div>');
					return;
				}

				$.each(items, function (i, n) {
					var cls = n.is_read == 0 ? 'xen-notif-item xen-unread' : 'xen-notif-item';
					$list.append(
						'<div class="' + cls + '" data-id="' + parseInt(n.id, 10) + '">' +
							'<div class="xen-notif-title">'   + escapeHtml(n.title)   + '</div>' +
							'<div class="xen-notif-msg">'     + escapeHtml(n.message) + '</div>' +
							'<div class="xen-notif-time">'    + escapeHtml(n.created_at || '') + '</div>' +
						'</div>'
					);
				});
			});
	}

	function startNotifPolling() {
		if (!xenData.isLoggedIn) return;
		loadNotifications();
		notifPollInterval = setInterval(loadNotifications, 60000);
	}

	/* -----------------------------------------------------------------
	   NOTIFICATION DROPDOWN INTERACTIONS
	   ----------------------------------------------------------------- */
	$(document).on('click', '.xen-notif-btn', function (e) {
		e.stopPropagation();
		var $dd = $(this).closest('.xen-notif-wrap').find('.xen-notif-dropdown');
		$dd.toggleClass('xen-open');
		if ($dd.hasClass('xen-open')) loadNotifications();
	});

	$(document).on('click', function (e) {
		if (!$(e.target).closest('.xen-notif-wrap').length) {
			$('.xen-notif-dropdown').removeClass('xen-open');
		}
	});

	/* Mark single notification read */
	$(document).on('click', '.xen-notif-item', function () {
		var id = parseInt($(this).data('id'), 10);
		if (!id) return;
		$(this).removeClass('xen-unread');
		xenRequest('xen_mark_notification_read', { notification_id: id });
	});

	/* Mark all read */
	$(document).on('click', '.xen-mark-all-read', function (e) {
		e.preventDefault();
		xenRequest('xen_mark_all_notifications_read')
			.done(function (res) {
				if (res.success) {
					$('.xen-notif-item').removeClass('xen-unread');
					$('.xen-notif-count').hide();
				}
			});
	});

	/* -----------------------------------------------------------------
	   GENERIC CONFIRM ACTIONS (delete, etc.)
	   ----------------------------------------------------------------- */
	$(document).on('click', '.xen-confirm-action', function (e) {
		var msg = $(this).data('confirm') || 'Are you sure?';
		if (!window.confirm(msg)) {
			e.preventDefault();
		}
	});

	/* -----------------------------------------------------------------
	   INIT
	   ----------------------------------------------------------------- */
	$(document).ready(function () {
		ensureToastContainer();
		startNotifPolling();
	});

	/* -----------------------------------------------------------------
	   EXPOSE GLOBALS (consumed by other XEN scripts)
	   ----------------------------------------------------------------- */
	window.xenRequest  = xenRequest;
	window.xenToast    = showToast;
	window.xenEscape   = escapeHtml;

})(jQuery);
