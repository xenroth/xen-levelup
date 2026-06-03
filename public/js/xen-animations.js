/**
 * XEN LevelUp — Animations JS
 * Floating particles, XP bar fill, level-up modal, achievement popups.
 * Depends on: xen-main.js (xenData)
 */
(function ($) {
	'use strict';

	/* ============================================================
	   PARTICLES CANVAS
	   ============================================================ */
	var XenParticles = {
		canvas:  null,
		ctx:     null,
		particles: [],

		init: function () {
			this.canvas = document.createElement('canvas');
			this.canvas.id = 'xen-particles-canvas';
			Object.assign(this.canvas.style, {
				position: 'fixed', top: '0', left: '0',
				width: '100%', height: '100%',
				pointerEvents: 'none', zIndex: '99998'
			});
			document.body.appendChild(this.canvas);
			this.ctx = this.canvas.getContext('2d');
			this.resize();
			$(window).on('resize', this.resize.bind(this));
			this.loop();
		},

		resize: function () {
			this.canvas.width  = window.innerWidth;
			this.canvas.height = window.innerHeight;
		},

		burst: function (x, y, color, count) {
			color = color || '#00D4FF';
			count = count || 30;
			for (var i = 0; i < count; i++) {
				var angle = Math.random() * Math.PI * 2;
				var speed = Math.random() * 6 + 2;
				this.particles.push({
					x: x, y: y,
					vx: Math.cos(angle) * speed,
					vy: Math.sin(angle) * speed,
					alpha: 1,
					size: Math.random() * 5 + 2,
					color: color,
					decay: Math.random() * 0.025 + 0.015
				});
			}
		},

		loop: function () {
			var self = this;
			requestAnimationFrame(function () { self.loop(); });
			if (!this.ctx) return;
			this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
			this.particles = this.particles.filter(function (p) {
				p.x     += p.vx;
				p.y     += p.vy;
				p.vy    += 0.12; /* gravity */
				p.alpha -= p.decay;
				self.ctx.globalAlpha = Math.max(0, p.alpha);
				self.ctx.fillStyle   = p.color;
				self.ctx.beginPath();
				self.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
				self.ctx.fill();
				return p.alpha > 0;
			});
			this.ctx.globalAlpha = 1;
		}
	};

	/* ============================================================
	   XP BAR ANIMATION
	   Finds all .xen-xp-fill and .xen-stat-fill elements and
	   animates them from 0 to their data-width attribute.
	   ============================================================ */
	function animateXpBars() {
		$('.xen-xp-fill, .xen-stat-fill, .xen-tree-fill, .xen-rpg-fill').each(function () {
			var $bar = $(this);
			var target = parseFloat($bar.data('width') || $bar.attr('style').match(/width:\s*([\d.]+)%/)?.[1] || 0);
			$bar.css('width', '0%');
			setTimeout(function () {
				$bar.css({
					'transition': 'width 1.2s cubic-bezier(.25,0,.2,1)',
					'width': target + '%'
				});
			}, 200);
		});
	}

	/* ============================================================
	   LEVEL-UP MODAL
	   ============================================================ */
	function showLevelUpModal(level, rankTitle) {
		var $overlay = $('<div class="xen-level-modal-overlay"></div>');
		var $modal   = $(
			'<div class="xen-level-modal">' +
				'<button class="xen-level-modal-close" aria-label="Close">✕</button>' +
				'<span class="xen-level-modal-icon">⚔️</span>' +
				'<div class="xen-level-modal-title">[ LEVEL UP ]</div>' +
				'<div class="xen-level-modal-level">' + level + '</div>' +
				'<div class="xen-level-modal-rank">' + (rankTitle || '') + '</div>' +
				'<div class="xen-level-modal-msg">Your power has increased.</div>' +
			'</div>'
		);
		$overlay.append($modal);
		$('body').append($overlay);

		/* Particle burst from modal centre */
		var offset = $modal.offset();
		XenParticles.burst(
			offset.left + $modal.outerWidth() / 2,
			offset.top  + $modal.outerHeight() / 2,
			'#00D4FF', 60
		);

		/* Close handlers */
		$modal.find('.xen-level-modal-close').on('click', function () { $overlay.remove(); });
		$overlay.on('click', function (e) { if ($(e.target).is($overlay)) { $overlay.remove(); } });

		/* Auto-dismiss after 6 s */
		setTimeout(function () { $overlay.fadeOut(300, function () { $overlay.remove(); }); }, 6000);
	}

	/* ============================================================
	   ACHIEVEMENT POPUP
	   ============================================================ */
	function showAchievementPopup(title, icon, coins, xp) {
		var $popup = $(
			'<div class="xen-toast xen-toast-achievement xen-achievement-popup">' +
				'<div class="xen-toast-title">' + (icon || '🏆') + ' ' + title + '</div>' +
				'<div class="xen-toast-message">+' + (xp || 0) + ' XP  +' + (coins || 0) + ' coins</div>' +
			'</div>'
		);

		ensureToastContainer();
		$('#xen-toast-container').prepend($popup);

		setTimeout(function () {
			$popup.addClass('xen-dismiss').on('animationend', function () { $popup.remove(); });
		}, 5000);
	}

	function ensureToastContainer() {
		if (!document.getElementById('xen-toast-container')) {
			$('body').append('<div id="xen-toast-container"></div>');
		}
	}

	/* ============================================================
	   GLOBAL EVENTS (fired by other modules)
	   ============================================================ */
	$(document).on('xen:levelUp', function (e, data) {
		showLevelUpModal(data.level, data.rank);
	});

	$(document).on('xen:achievementUnlocked', function (e, data) {
		showAchievementPopup(data.title, data.icon, data.coins, data.xp);
	});

	/* ============================================================
	   INIT
	   ============================================================ */
	$(document).ready(function () {
		XenParticles.init();
		animateXpBars();
	});

	/* Expose for external use */
	window.XenAnimations = {
		particleBurst: XenParticles.burst.bind(XenParticles),
		showLevelUp:   showLevelUpModal,
		showAchievement: showAchievementPopup
	};

})(jQuery);
