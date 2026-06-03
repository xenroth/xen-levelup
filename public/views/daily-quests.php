<?php
/**
 * Public view: Daily quests.
 * Loaded by [gamified_daily_quests]
 *
 * Variables: $user_id, $quests
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="xen-wrap xen-quests-wrap" id="xen-daily-quests">

	<h3 class="xen-section-title">
		📅 <?php esc_html_e( "Today's Daily Quests", 'xen-levelup' ); ?>
		<span class="xen-quest-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></span>
	</h3>

	<div class="xen-quest-list" id="xen-daily-list">
	<?php if ( $quests ) : foreach ( $quests as $quest ) : ?>
		<div class="xen-quest-card xen-diff-<?php echo esc_attr( $quest->difficulty ); ?> <?php echo esc_attr( 'completed' === $quest->status ? 'xen-quest-done' : '' ); ?>"
			 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
			<div class="xen-quest-header">
				<span class="xen-quest-category-icon"><?php echo esc_html( Xen_Stats::LIFE_TREE_ICONS[ $quest->category ] ?? '' ); ?></span>
				<span class="xen-quest-title"><?php echo esc_html( $quest->title ); ?></span>
				<span class="xen-diff-badge xen-diff-<?php echo esc_attr( $quest->difficulty ); ?>">
					<?php echo esc_html( xen_levelup()->quests->difficulty_label( $quest->difficulty ) ); ?>
				</span>
			</div>
			<?php if ( $quest->description ) : ?>
			<p class="xen-quest-desc"><?php echo esc_html( $quest->description ); ?></p>
			<?php endif; ?>
			<div class="xen-quest-footer">
				<span class="xen-quest-reward">⭐ <?php echo esc_html( $quest->xp_reward ); ?> XP</span>
				<span class="xen-quest-reward">🪙 <?php echo esc_html( $quest->coin_reward ); ?></span>
				<?php if ( 'completed' !== $quest->status ) : ?>
				<button class="xen-btn xen-btn-complete xen-complete-quest"
						data-id="<?php echo esc_attr( $quest->id ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>">
					<?php esc_html_e( 'Mark Complete', 'xen-levelup' ); ?>
				</button>
				<?php else : ?>
				<span class="xen-done-tag">✓ <?php esc_html_e( 'Completed', 'xen-levelup' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; else : ?>
		<div class="xen-empty-state">
			<p><?php esc_html_e( 'No quests available for today. Check back later!', 'xen-levelup' ); ?></p>
		</div>
	<?php endif; ?>
	</div>

</div>
