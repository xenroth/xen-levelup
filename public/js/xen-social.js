/**
 * XEN LevelUp — Social Feed JS
 * Handles activity feed: post, like, comment, load more, friend requests.
 *
 * @package XEN_LevelUp
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;
	if (!$('#xen-feed').length) return;

	var $feed  = $('#xen-feed');
	var nonce  = $feed.data('nonce') || xenData.nonce;

	/* ── Post Activity ─────────────────────────────────────────────────── */
	$(document).on('click', '#xen-feed-post-btn', function () {
		var $btn     = $(this);
		var $textarea = $('#xen-feed-post-text');
		var content  = $.trim($textarea.val());
		if (!content) return;

		$btn.prop('disabled', true).text(xenData.i18n.processing || 'Posting…');

		$.post(xenData.ajaxUrl, {
			action : 'xen_post_activity',
			nonce  : nonce,
			content: content
		}, function (res) {
			if (!res.success) {
				xenToast((res.data && res.data.message) || 'Error posting.', 'error');
				return;
			}
			$textarea.val('');
			/* Prepend the new post at the top */
			var $empty = $('#xen-feed-empty');
			if ($empty.length) $empty.remove();

			var user    = xenData.currentUser || {};
			var $item   = buildFeedItem({
				id          : res.data.activity_id,
				display_name: user.name || '',
				avatar_url  : user.avatar || '',
				content     : content,
				like_count  : 0,
				comment_count: 0,
				liked_by_me : false,
				time_diff   : '0 seconds',
			});
			$('#xen-feed-list').prepend($item);
			xenToast('Posted!', 'xp');
		}).always(function () {
			$btn.prop('disabled', false).text(xenData.i18n.post || 'Post');
		});
	});

	/* ── Like / Unlike ─────────────────────────────────────────────────── */
	$(document).on('click', '.xen-like-btn', function () {
		var $btn        = $(this);
		var activityId  = parseInt($btn.data('id'), 10);
		if (!activityId) return;

		$.post(xenData.ajaxUrl, {
			action     : 'xen_like_activity',
			nonce      : nonce,
			activity_id: activityId
		}, function (res) {
			if (!res.success) return;
			var liked = res.data.liked;
			var count = res.data.count;
			$btn.toggleClass('xen-liked', liked);
			$btn.find('.xen-like-count').text(count);
			$btn.html((liked ? '❤️' : '🤍') + ' <span class="xen-like-count">' + count + '</span>');
		});
	});

	/* ── Toggle Comments ───────────────────────────────────────────────── */
	$(document).on('click', '.xen-comment-toggle-btn', function () {
		var $btn       = $(this);
		var activityId = parseInt($btn.data('id'), 10);
		var $area      = $('#xen-comments-' + activityId);

		if ($area.is(':visible')) {
			$area.slideUp(200);
			return;
		}

		// Lazy-load comments
		var $list = $area.find('.xen-comments-list');
		if (!$list.data('loaded')) {
			$list.html('<span class="xen-spinner"></span>');
			$.post(xenData.ajaxUrl, {
				action     : 'xen_get_comments',
				nonce      : nonce,
				activity_id: activityId
			}, function (res) {
				$list.empty();
				if (res.success && res.data.comments.length) {
					$.each(res.data.comments, function (i, c) {
						$list.append(buildCommentHTML(c));
					});
				} else {
					$list.html('<p class="xen-empty xen-empty-sm">' + (xenData.i18n.noComments || 'No comments yet.') + '</p>');
				}
				$list.data('loaded', true);
			});
		}

		$area.slideDown(200);
	});

	/* ── Post Comment ──────────────────────────────────────────────────── */
	$(document).on('click', '.xen-post-comment-btn', function () {
		var $btn       = $(this);
		var activityId = parseInt($btn.data('id'), 10);
		var $area      = $('#xen-comments-' + activityId);
		var $input     = $area.find('.xen-comment-input');
		var content    = $.trim($input.val());
		if (!content) return;

		$btn.prop('disabled', true);

		$.post(xenData.ajaxUrl, {
			action     : 'xen_add_comment',
			nonce      : nonce,
			activity_id: activityId,
			content    : content
		}, function (res) {
			if (!res.success) {
				xenToast((res.data && res.data.message) || 'Error.', 'error');
				return;
			}
			var $list = $area.find('.xen-comments-list');
			$list.find('.xen-empty-sm').remove();
			var user = xenData.currentUser || {};
			$list.append(buildCommentHTML({
				display_name: user.name || '',
				avatar_url  : user.avatar || '',
				content     : content,
			}));
			$input.val('');
			// Update comment count badge
			var $card  = $btn.closest('.xen-feed-item');
			var $cnt   = $card.find('.xen-comment-count');
			$cnt.text((parseInt($cnt.text(), 10) || 0) + 1);
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	/* ── Load More ─────────────────────────────────────────────────────── */
	$(document).on('click', '#xen-feed-load-more', function () {
		var $btn   = $(this);
		var offset = parseInt($btn.data('offset'), 10) || 0;
		$btn.prop('disabled', true).text(xenData.i18n.processing || 'Loading…');

		$.post(xenData.ajaxUrl, {
			action: 'xen_get_feed',
			nonce : nonce,
			offset: offset,
			mode  : 'friends'
		}, function (res) {
			if (!res.success || !res.data.items.length) {
				$btn.closest('.xen-feed-loadmore').remove();
				return;
			}
			$.each(res.data.items, function (i, item) {
				$('#xen-feed-list').append(buildFeedItem(item));
			});
			$btn.data('offset', offset + res.data.items.length);
			if (!res.data.has_more) {
				$btn.closest('.xen-feed-loadmore').remove();
			} else {
				$btn.prop('disabled', false).text(xenData.i18n.loadMore || 'Load More');
			}
		}).fail(function () {
			$btn.prop('disabled', false).text(xenData.i18n.loadMore || 'Load More');
		});
	});

	/* ── Add Friend ────────────────────────────────────────────────────── */
	$(document).on('click', '.xen-add-friend-btn', function () {
		var $btn = $(this);
		var uid  = parseInt($btn.data('uid'), 10);
		if (!uid) return;
		$btn.prop('disabled', true);

		$.post(xenData.ajaxUrl, {
			action : 'xen_send_friend_request',
			nonce  : nonce,
			user_id: uid
		}, function (res) {
			if (res.success) {
				$btn.text('✔ Sent').addClass('xen-btn-muted');
				xenToast(xenData.i18n.friendRequestSent || 'Friend request sent!', 'xp');
			} else {
				xenToast((res.data && res.data.message) || 'Error.', 'error');
				$btn.prop('disabled', false);
			}
		});
	});

	/* ── Accept Friend ─────────────────────────────────────────────────── */
	$(document).on('click', '.xen-accept-friend-btn', function () {
		var $btn = $(this);
		var uid  = parseInt($btn.data('uid'), 10);
		if (!uid) return;
		$btn.prop('disabled', true);

		$.post(xenData.ajaxUrl, {
			action : 'xen_accept_friend_request',
			nonce  : nonce,
			user_id: uid
		}, function (res) {
			if (res.success) {
				$btn.closest('.xen-friend-request-row').slideUp(200, function () { $(this).remove(); });
				xenToast(xenData.i18n.friendAccepted || 'Friend accepted!', 'xp');
			} else {
				xenToast((res.data && res.data.message) || 'Error.', 'error');
				$btn.prop('disabled', false);
			}
		});
	});

	/* ── Avatar Upload ─────────────────────────────────────────────────── */
	$(document).on('click', '#xen-upload-avatar-btn', function () {
		var $file  = $('#xen-avatar-file');
		var file   = $file[0].files[0];
		var $status = $('#xen-avatar-upload-status');

		if (!file) {
			$status.text('Please select a file.').css('color', 'var(--xen-error, #ef4444)');
			return;
		}

		var formData = new FormData();
		formData.append('action', 'xen_upload_avatar');
		formData.append('nonce', nonce);
		formData.append('avatar', file);

		$status.text(xenData.i18n.processing || 'Uploading…').css('color', '');

		$.ajax({
			url        : xenData.ajaxUrl,
			type       : 'POST',
			data       : formData,
			contentType: false,
			processData: false,
			success    : function (res) {
				if (res.success) {
					$('#xen-avatar-preview-img').attr('src', res.data.url);
					$('.xen-profile-avatar').attr('src', res.data.url);
					$('.xen-avatar').attr('src', res.data.url);
					$status.text('✔ Photo updated!').css('color', 'var(--xen-success, #22c55e)');
				} else {
					$status.text((res.data && res.data.message) || 'Upload failed.').css('color', 'var(--xen-error, #ef4444)');
				}
			},
			error: function () {
				$status.text('Upload failed.').css('color', 'var(--xen-error, #ef4444)');
			}
		});
	});

	/* ── Convert Task to Quest ─────────────────────────────────────────── */
	$(document).on('click', '.xen-convert-task-btn', function () {
		var $btn   = $(this);
		var taskId = parseInt($btn.data('id'), 10);
		if (!taskId || $btn.prop('disabled')) return;
		$btn.prop('disabled', true);

		$.post(xenData.ajaxUrl, {
			action : 'xen_convert_task_to_quest',
			nonce  : nonce,
			task_id: taskId
		}, function (res) {
			if (!res.success) {
				xenToast((res.data && res.data.message) || 'Error.', 'error');
				$btn.prop('disabled', false);
				return;
			}
			xenToast(res.data.message || 'Converted to Quest!', 'xp', '⚔️ Quest Created!');
			$btn.closest('.xen-task-item').slideUp(300, function () { $(this).remove(); });
		});
	});

	/* ── Helpers ────────────────────────────────────────────────────────── */

	function buildFeedItem(item) {
		var liked  = item.liked_by_me;
		var avatar = item.avatar_url ? '<img class="xen-feed-avatar" src="' + escAttr(item.avatar_url) + '" width="40" height="40" alt="">' : '';
		return $('<div class="xen-feed-item xen-card" id="xen-feed-item-' + item.id + '" data-id="' + item.id + '">' +
			'<div class="xen-feed-header">' + avatar +
			'<div class="xen-feed-meta"><span class="xen-feed-name">' + esc(item.display_name) + '</span>' +
			'<span class="xen-feed-time">' + esc(item.time_diff || '') + '</span></div></div>' +
			'<div class="xen-feed-content">' + esc(item.content) + '</div>' +
			'<div class="xen-feed-footer">' +
			'<button class="xen-like-btn' + (liked ? ' xen-liked' : '') + '" data-id="' + item.id + '">' +
			(liked ? '❤️' : '🤍') + ' <span class="xen-like-count">' + (item.like_count || 0) + '</span></button>' +
			'<button class="xen-comment-toggle-btn xen-btn-ghost xen-btn-xs" data-id="' + item.id + '">💬 <span class="xen-comment-count">' + (item.comment_count || 0) + '</span></button>' +
			'</div>' +
			'<div class="xen-comments-area" id="xen-comments-' + item.id + '" style="display:none;">' +
			'<div class="xen-comments-list"></div>' +
			'<div class="xen-add-comment-row">' +
			'<input type="text" class="xen-comment-input xen-input" maxlength="500" placeholder="Write a comment…">' +
			'<button class="xen-btn xen-btn-sm xen-post-comment-btn" data-id="' + item.id + '">Send</button>' +
			'</div></div>' +
			'</div>');
	}

	function buildCommentHTML(c) {
		var avatar = c.avatar_url ? '<img src="' + escAttr(c.avatar_url) + '" class="xen-comment-avatar" width="32" height="32" alt="">' : '';
		return '<div class="xen-comment-row">' + avatar +
			'<div class="xen-comment-body"><span class="xen-comment-author">' + esc(c.display_name || '') + '</span>' +
			'<span class="xen-comment-text">' + esc(c.content) + '</span></div></div>';
	}

	function esc(str) {
		return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function escAttr(str) { return esc(str); }

}(jQuery));
