<?php
/**
 * Public view: Generic quest list.
 * Loaded by [gamified_quests]
 *
 * Variables: $user_id, $quests, $atts
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$type_label = ucfirst( $atts['type'] ?? '' );
?>
<div class="xen-wrap xen-quests-wrap">
	<h3 class="xen-section-title">📋 <?php echo esc_html( $type_label ); ?> <?php esc_html_e( 'Quests', 'xen-levelup' ); ?></h3>
	<div class="xen-quest-list">
	<?php if ( $quests ) : foreach ( $quests as $quest ) : ?>
		<div class="xen-quest-card xen-diff-<?php echo esc_attr( $quest->difficulty ); ?> <?php echo 'completed' === $quest->status ? 'xen-quest-done' : ''; ?>"
			 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
			<div class="xen-quest-header">
				<span class="xen-quest-title"><?php echo esc_html( $quest->title ); ?></span>
				<span class="xen-diff-badge"><?php echo esc_html( xen_levelup()->quests->difficulty_label( $quest->difficulty ) ); ?></span>
			</div>
			<div class="xen-quest-footer">
				<span>⭐ <?php echo esc_html( $quest->xp_reward ); ?> XP &nbsp; 🪙 <?php echo esc_html( $quest->coin_reward ); ?></span>
				<?php if ( 'completed' !== $quest->status ) : ?>
				<button class="xen-btn xen-btn-complete xen-complete-quest" data-id="<?php echo esc_attr( $quest->id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>">
					<?php esc_html_e( 'Complete', 'xen-levelup' ); ?>
				</button>
				<?php else : ?>
				<span class="xen-done-tag">✓</span>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; else : ?>
		<p class="xen-empty"><?php esc_html_e( 'No active quests.', 'xen-levelup' ); ?></p>
	<?php endif; ?>
	</div>
</div>
