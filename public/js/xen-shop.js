/**
 * XEN LevelUp — Shop JS
 * AJAX type filtering, AJAX pagination, purchase, equip/unequip.
 * Dependencies: xen-main.js (provides xenRequest, xenToast, xenEscape)
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;
	if (!$('#xen-shop').length) return;

	var $shop      = $('#xen-shop');
	var $grid      = $('#xen-shop-grid');
	var $pager     = $('#xen-shop-pagination');
	var perPage    = parseInt($shop.data('per-page'), 10) || 12;
	var currentType = $shop.data('type') || 'all';
	var currentPage = parseInt($shop.data('page'), 10) || 1;

	/* ----------------------------------------------------------------
	   Build item card HTML from AJAX data
	   ---------------------------------------------------------------- */
	function renderItems(items, ownedIds, equippedIds) {
		if (!items || !items.length) {
			return '<p class="xen-empty">' + xenEscape(xenData.i18n.shopEmpty || 'Shop is empty.') + '</p>';
		}

		ownedIds    = ownedIds    || [];
		equippedIds = equippedIds || [];

		return items.map(function (item) {
			var id         = parseInt(item.id, 10);
			var isOwned    = ownedIds.indexOf(id)    !== -1;
			var isEquipped = equippedIds.indexOf(id) !== -1;
			var price      = parseInt(item.price, 10);
			var typeLabel  = (item.item_type || '').replace(/_/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
			var priceStr   = price.toLocaleString();
			var symbol     = xenData.currencySymbol || '🪙';

			var imgHtml = item.image_url
				? '<img src="' + xenEscape(item.image_url) + '" alt="' + xenEscape(item.title) + '" loading="lazy">'
				: '<span class="xen-item-placeholder">🎁</span>';

			var btnHtml;
			if (!xenData.isLoggedIn || xenData.isLoggedIn === 'no') {
				btnHtml = '<a href="' + xenEscape(xenData.loginUrl || '') + '" class="xen-btn xen-btn-secondary">' +
					(xenData.i18n.loginToBuy || 'Login to Buy') + '</a>';
			} else if (isEquipped) {
				btnHtml = '<button class="xen-btn xen-btn-equipped xen-equip-item" ' +
					'data-item-id="' + id + '" data-equip="0">✓ ' +
					(xenData.i18n.equipped || 'Equipped') + '</button>';
			} else if (isOwned) {
				btnHtml = '<button class="xen-btn xen-btn-owned xen-equip-item" ' +
					'data-item-id="' + id + '" data-equip="1">' +
					(xenData.i18n.equip || 'Equip') + '</button>';
			} else {
				btnHtml = '<button class="xen-btn xen-btn-buy xen-purchase-item" ' +
					'data-item-id="' + id + '" data-price="' + price + '">' +
					(xenData.i18n.buy || 'Buy') + '</button>';
			}

			return '<div class="xen-shop-item" data-item-type="' + xenEscape(item.item_type) + '" id="xen-item-' + id + '">' +
				'<div class="xen-item-icon">' + imgHtml + '</div>' +
				'<div class="xen-item-info">' +
					'<div class="xen-item-title">' + xenEscape(item.title) + '</div>' +
					'<div class="xen-item-type">' + xenEscape(typeLabel) + '</div>' +
					(item.description ? '<div class="xen-item-desc">' + xenEscape(item.description) + '</div>' : '') +
				'</div>' +
				'<div class="xen-item-footer">' +
					'<span class="xen-item-price">' + xenEscape(symbol) + ' ' + priceStr + '</span>' +
					btnHtml +
				'</div>' +
			'</div>';
		}).join('');
	}

	/* ----------------------------------------------------------------
	   Build pagination HTML
	   ---------------------------------------------------------------- */
	function renderPager(page, pages) {
		if (pages <= 1) return '';

		var html = '<div class="xen-pager">';

		if (page > 1) {
			html += '<button class="xen-page-btn" data-page="' + (page - 1) + '">&laquo; ' + (xenData.i18n.prev || 'Prev') + '</button>';
		}

		var start = Math.max(1, page - 2);
		var end   = Math.min(pages, page + 2);

		if (start > 1) {
			html += '<button class="xen-page-btn" data-page="1">1</button>';
			if (start > 2) html += '<span class="xen-page-gap">…</span>';
		}

		for (var p = start; p <= end; p++) {
			if (p === page) {
				html += '<button class="xen-page-btn xen-page-current" data-page="' + p + '" disabled>' + p + '</button>';
			} else {
				html += '<button class="xen-page-btn" data-page="' + p + '">' + p + '</button>';
			}
		}

		if (end < pages) {
			if (end < pages - 1) html += '<span class="xen-page-gap">…</span>';
			html += '<button class="xen-page-btn" data-page="' + pages + '">' + pages + '</button>';
		}

		if (page < pages) {
			html += '<button class="xen-page-btn" data-page="' + (page + 1) + '">' + (xenData.i18n.next || 'Next') + ' &raquo;</button>';
		}

		html += '</div>';
		return html;
	}

	/* ----------------------------------------------------------------
	   AJAX load items
	   ---------------------------------------------------------------- */
	function loadItems(type, page) {
		currentType = type || 'all';
		currentPage = page || 1;

		$grid.addClass('xen-loading').css('opacity', '0.5');
		$pager.html('');

		xenRequest('xen_get_shop_items', {
			type:     currentType,
			page:     currentPage,
			per_page: perPage,
		}).done(function (res) {
			$grid.removeClass('xen-loading').css('opacity', '1');
			if (!res.success) {
				xenToast(res.data && res.data.message ? res.data.message : (xenData.i18n.error || 'Error'), 'error');
				return;
			}
			var d = res.data;
			$grid.html(renderItems(d.items, d.owned_ids, d.equipped_ids));
			$pager.html(renderPager(d.page, d.pages));
			$shop.data('page',  d.page);
			$shop.data('pages', d.pages);
		});
	}

	/* ----------------------------------------------------------------
	   Type filter buttons
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-filter-btn', function () {
		var $btn = $(this);
		var type = $btn.data('type') || 'all';

		$('.xen-filter-btn').removeClass('xen-filter-active');
		$btn.addClass('xen-filter-active');

		loadItems(type, 1);
	});

	/* ----------------------------------------------------------------
	   Pagination buttons (delegated — buttons are rendered dynamically)
	   ---------------------------------------------------------------- */
	$pager.on('click', '.xen-page-btn', function () {
		var page = parseInt($(this).data('page'), 10);
		if (!page) return;
		loadItems(currentType, page);
		$shop[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
	});

	/* ----------------------------------------------------------------
	   Purchase item
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-purchase-item', function (e) {
		e.preventDefault();
		var $btn   = $(this);
		var itemId = parseInt($btn.data('item-id'), 10);
		var price  = parseInt($btn.data('price'), 10) || 0;
		if (!itemId || $btn.prop('disabled')) return;

		var confirmMsg = (xenData.i18n.confirm_purchase || 'Purchase for {coins} coins?').replace('{coins}', price);
		if (!window.confirm(confirmMsg)) return;

		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_purchase_item', { item_id: itemId })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data && res.data.message ? res.data.message : 'Purchase failed.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				xenToast(xenData.i18n.purchaseSuccess || 'Item purchased!', 'success', 'Shop');

				$btn.removeClass('xen-purchase-item xen-btn-buy')
					.addClass('xen-equip-item xen-btn-owned')
					.text(xenData.i18n.equip || 'Equip')
					.data('equip', 1)
					.prop('disabled', false);

				// Update coin balance display
				var newBal = res.data && res.data.new_balance;
				if (typeof newBal !== 'undefined') {
					$('#xen-coin-balance').text(parseInt(newBal, 10).toLocaleString());
				}
			});
	});

	/* ----------------------------------------------------------------
	   Equip / unequip item
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-equip-item', function (e) {
		e.preventDefault();
		var $btn       = $(this);
		var itemId     = parseInt($btn.data('item-id'), 10);
		var isEquipped = $btn.hasClass('xen-btn-equipped');
		if (!itemId || $btn.prop('disabled')) return;

		var action   = isEquipped ? 0 : 1;
		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_equip_item', { item_id: itemId, equip: action })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data && res.data.message ? res.data.message : 'Could not equip item.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				if (action) {
					var itemType = $btn.closest('.xen-shop-item').data('item-type');
					$('.xen-shop-item[data-item-type="' + itemType + '"] .xen-equip-item')
						.not($btn)
						.removeClass('xen-btn-equipped')
						.addClass('xen-btn-owned')
						.text(xenData.i18n.equip || 'Equip');

					$btn.removeClass('xen-btn-owned')
						.addClass('xen-btn-equipped')
						.text('✓ ' + (xenData.i18n.equipped || 'Equipped'))
						.data('equip', 0)
						.prop('disabled', false);
					xenToast(xenData.i18n.equipSuccess || 'Item equipped!', 'success');
				} else {
					$btn.removeClass('xen-btn-equipped')
						.addClass('xen-btn-owned')
						.text(xenData.i18n.equip || 'Equip')
						.data('equip', 1)
						.prop('disabled', false);
					xenToast(xenData.i18n.unequipSuccess || 'Item unequipped.', 'info');
				}
			});
	});

	/* ----------------------------------------------------------------
	   Render initial pagination (server-rendered items already in DOM)
	   ---------------------------------------------------------------- */
	(function initPager() {
		var pages = parseInt($shop.data('pages'), 10) || 1;
		var page  = parseInt($shop.data('page'),  10) || 1;
		if (pages > 1) {
			$pager.html(renderPager(page, pages));
		}
	}());

})(jQuery);

