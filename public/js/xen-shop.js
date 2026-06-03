/**
 * XEN LevelUp — Shop JS
 * Type filtering, purchase, equip/unequip.
 * Dependencies: xen-main.js
 */
(function ($) {
	'use strict';

	if (typeof xenData === 'undefined') return;
	if (!$('.xen-shop').length) return;

	/* ----------------------------------------------------------------
	   Type filter buttons
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-filter-btn', function () {
		var $btn  = $(this);
		var type  = $btn.data('type') || 'all';

		$('.xen-filter-btn').removeClass('xen-filter-active');
		$btn.addClass('xen-filter-active');

		if (type === 'all') {
			$('.xen-shop-item').show();
		} else {
			$('.xen-shop-item').each(function () {
				$(this).toggle($(this).data('item-type') === type);
			});
		}
	});

	/* ----------------------------------------------------------------
	   Purchase item
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-purchase-item', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var itemId  = parseInt($btn.data('item-id'), 10);
		var price   = parseInt($btn.data('price'),   10) || 0;
		if (!itemId || $btn.prop('disabled')) return;

		var confirmMsg = (xenData.i18n.confirm_purchase || 'Purchase for {coins} coins?').replace('{coins}', price);
		if (!window.confirm(confirmMsg)) return;

		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_purchase_shop_item', { item_id: itemId })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data || 'Purchase failed.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				xenToast('Item purchased!', 'success', 'Shop');

				/* Update button to Equip */
				$btn.removeClass('xen-purchase-item xen-btn-buy')
					.addClass('xen-equip-item xen-btn-owned')
					.text('Equip')
					.prop('disabled', false);

				/* Update coin balance */
				var newBal = (res.data && res.data.new_balance);
				if (typeof newBal !== 'undefined') {
					$('#xen-coin-balance').text('🪙 ' + parseInt(newBal, 10).toLocaleString());
					$('.xen-shop-balance').text('🪙 ' + parseInt(newBal, 10).toLocaleString() + ' coins');
				}
			});
	});

	/* ----------------------------------------------------------------
	   Equip / unequip item
	   ---------------------------------------------------------------- */
	$(document).on('click', '.xen-equip-item', function (e) {
		e.preventDefault();
		var $btn   = $(this);
		var itemId = parseInt($btn.data('item-id'), 10);
		var isEquipped = $btn.hasClass('xen-btn-equipped');
		if (!itemId || $btn.prop('disabled')) return;

		var action = isEquipped ? 0 : 1;
		var origText = $btn.text();
		$btn.prop('disabled', true).html('<span class="xen-spinner"></span>');

		xenRequest('xen_equip_shop_item', { item_id: itemId, equip: action })
			.done(function (res) {
				if (!res.success) {
					xenToast(res.data || 'Could not equip item.', 'error');
					$btn.prop('disabled', false).text(origText);
					return;
				}

				if (action) {
					/* Equip: unequip other items of same type first in UI */
					var itemType = $btn.closest('.xen-shop-item').data('item-type');
					$('.xen-shop-item[data-item-type="' + itemType + '"] .xen-equip-item')
						.not($btn)
						.removeClass('xen-btn-equipped')
						.addClass('xen-btn-owned')
						.text('Equip');

					$btn.removeClass('xen-btn-owned')
						.addClass('xen-btn-equipped')
						.text('Equipped ✓')
						.prop('disabled', false);
					xenToast('Item equipped!', 'success');
				} else {
					$btn.removeClass('xen-btn-equipped')
						.addClass('xen-btn-owned')
						.text('Equip')
						.prop('disabled', false);
					xenToast('Item unequipped.', 'info');
				}
			});
	});

})(jQuery);
