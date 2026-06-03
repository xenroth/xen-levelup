<?php
/**
 * Public view: Legendary quests.
 * [gamified_legendary_quests]
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="xen-wrap xen-quests-wrap xen-legendary-quests">
	<h3 class="xen-section-title">⭐ <?php esc_html_e( 'Legendary Quests — Chosen Few', 'xen-levelup' ); ?></h3>
	<?php if ( $quests ) : ?>
	<p class="xen-legendary-intro"><?php esc_html_e( 'You have been chosen. Complete this quest to prove your worth.', 'xen-levelup' ); ?></p>
	<div class="xen-quest-list">
	<?php foreach ( $quests as $quest ) : ?>
		<div class="xen-quest-card xen-quest-legendary <?php echo 'completed' === $quest->status ? 'xen-quest-done' : ''; ?>"
			 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
			<div class="xen-legendary-glow"></div>
			<div class="xen-quest-header">
				<span class="xen-quest-title">⭐ <?php echo esc_html( $quest->title ); ?></span>
			</div>
			<?php if ( $quest->description ) : ?>
			<p class="xen-quest-desc"><?php echo esc_html( $quest->description ); ?></p>
			<?php endif; ?>
			<div class="xen-quest-footer">
				<span class="xen-legendary-reward">⭐ <?php echo esc_html( $quest->xp_reward ); ?> XP &nbsp; 🪙 <?php echo esc_html( $quest->coin_reward ); ?></span>
				<?php if ( 'completed' !== $quest->status ) : ?>
				<button class="xen-btn xen-btn-legendary xen-complete-quest" data-id="<?php echo esc_attr( $quest->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>">
					<?php esc_html_e( 'Claim Victory', 'xen-levelup' ); ?>
				</button>
				<?php else : ?>
				<span class="xen-done-tag">✓ <?php esc_html_e( 'Legendary Complete!', 'xen-levelup' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
	</div>
	<?php else : ?>
	<div class="xen-empty-state">
		<p><?php esc_html_e( 'You have not been chosen for a legendary quest yet. Keep leveling up!', 'xen-levelup' ); ?></p>
	</div>
	<?php endif; ?>
</div>
