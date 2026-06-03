<?php
/**
 * Public view: Special quests.
 * [gamified_special_quests]
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="xen-wrap xen-quests-wrap xen-special-quests">
	<h3 class="xen-section-title">🌟 <?php esc_html_e( 'Special Quests (Weekly)', 'xen-levelup' ); ?></h3>
	<div class="xen-quest-list">
	<?php if ( $quests ) : foreach ( $quests as $quest ) : ?>
		<div class="xen-quest-card xen-quest-special xen-diff-<?php echo esc_attr( $quest->difficulty ); ?> <?php echo 'completed' === $quest->status ? 'xen-quest-done' : ''; ?>"
			 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
			<div class="xen-quest-header">
				<span class="xen-quest-title"><?php echo esc_html( $quest->title ); ?></span>
				<span class="xen-diff-badge"><?php echo esc_html( xen_levelup()->quests->difficulty_label( $quest->difficulty ) ); ?></span>
				<?php if ( $quest->expires_at ) : ?>
				<span class="xen-expires"><?php printf( esc_html__( 'Expires: %s', 'xen-levelup' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $quest->expires_at ) ) ) ); ?></span>
				<?php endif; ?>
			</div>
			<div class="xen-quest-footer">
				<span>⭐ <?php echo esc_html( $quest->xp_reward ); ?> XP &nbsp; 🪙 <?php echo esc_html( $quest->coin_reward ); ?></span>
				<?php if ( 'completed' !== $quest->status ) : ?>
				<button class="xen-btn xen-btn-complete xen-complete-quest" data-id="<?php echo esc_attr( $quest->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>">
					<?php esc_html_e( 'Complete', 'xen-levelup' ); ?>
				</button>
				<?php else : ?>
				<span class="xen-done-tag">✓ <?php esc_html_e( 'Done', 'xen-levelup' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; else : ?>
		<p class="xen-empty"><?php esc_html_e( 'No special quests this week.', 'xen-levelup' ); ?></p>
	<?php endif; ?>
	</div>
</div>
