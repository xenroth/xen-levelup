<?php
/**
 * Public view: Achievements.
 * Loaded by [gamified_achievements]
 *
 * Variables: $user_id, $achievements
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$earned = 0;
foreach ( $achievements as $a ) {
	if ( ! empty( $a->earned ) ) $earned++;
}
$total = count( $achievements );
?>
<div class="xen-wrap xen-achievements-wrap">

	<div class="xen-ach-header">
		<h3 class="xen-section-title">🏆 <?php esc_html_e( 'Achievements', 'xen-levelup' ); ?></h3>
		<span class="xen-ach-counter"><?php printf( esc_html__( '%1$d / %2$d Unlocked', 'xen-levelup' ), $earned, $total ); ?></span>
	</div>

	<div class="xen-ach-grid">
	<?php foreach ( $achievements as $a ) :
		$unlocked = ! empty( $a->earned );
	?>
		<div class="xen-ach-card <?php echo $unlocked ? 'xen-ach-unlocked' : 'xen-ach-locked'; ?>">
			<div class="xen-ach-icon"><?php echo esc_html( $a->icon ); ?></div>
			<div class="xen-ach-info">
				<div class="xen-ach-title"><?php echo esc_html( $a->title ); ?></div>
				<div class="xen-ach-desc"><?php echo esc_html( $a->description ); ?></div>
				<?php if ( $unlocked && $a->earned_at ) : ?>
				<div class="xen-ach-date"><?php printf( esc_html__( 'Earned %s', 'xen-levelup' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $a->earned_at ) ) ) ); ?></div>
				<?php endif; ?>
			</div>
			<div class="xen-ach-rewards">
				<?php if ( $a->xp_reward ) : ?><span>⭐ <?php echo esc_html( $a->xp_reward ); ?></span><?php endif; ?>
				<?php if ( $a->coin_reward ) : ?><span>🪙 <?php echo esc_html( $a->coin_reward ); ?></span><?php endif; ?>
			</div>
			<?php if ( ! $unlocked ) : ?>
			<div class="xen-ach-lock">🔒</div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
	</div>

</div><!-- .xen-achievements-wrap -->
