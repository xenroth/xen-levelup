<?php
/**
 * Public view: Rankings / Leaderboard.
 * Loaded by [gamified_rankings] / [gamified_leaderboard]
 *
 * Variables: $period, $entries, $atts
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user_id = get_current_user_id();
$my_rank         = $current_user_id ? xen_levelup()->rankings->get_user_rank( $current_user_id, $period ) : 0;
?>
<div class="xen-wrap xen-rankings-wrap">

	<div class="xen-rankings-header">
		<h3 class="xen-section-title">🏅 <?php esc_html_e( 'Hunter Rankings', 'xen-levelup' ); ?></h3>
		<div class="xen-period-tabs">
			<?php foreach ( array( 'global' => __( 'All-Time', 'xen-levelup' ), 'weekly' => __( 'Weekly', 'xen-levelup' ), 'monthly' => __( 'Monthly', 'xen-levelup' ) ) as $p => $label ) : ?>
			<a href="?period=<?php echo esc_attr( $p ); ?>" class="xen-tab <?php echo $p === $period ? 'xen-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( $my_rank && $current_user_id ) : ?>
	<div class="xen-my-rank-banner">
		<?php printf( esc_html__( 'Your rank: #%d', 'xen-levelup' ), $my_rank ); ?>
	</div>
	<?php endif; ?>

	<div class="xen-leaderboard">
	<?php if ( $entries ) : foreach ( $entries as $i => $entry ) :
		$is_me  = $current_user_id && (int) $entry->user_id === $current_user_id;
		$medals = array( 1 => '🥇', 2 => '🥈', 3 => '🥉' );
		$medal  = $medals[ $entry->rank_position ] ?? '';
	?>
		<div class="xen-leaderboard-row <?php echo $is_me ? 'xen-leaderboard-me' : ''; ?>">
			<div class="xen-lb-rank">
				<?php if ( $medal ) echo esc_html( $medal ); else echo esc_html( '#' . $entry->rank_position ); ?>
			</div>
			<div class="xen-lb-avatar">
				<?php echo get_avatar( $entry->user_id, 40, '', '', array( 'class' => 'xen-avatar-sm' ) ); ?>
			</div>
			<div class="xen-lb-name">
				<?php echo esc_html( $entry->display_name ); ?>
				<span class="xen-rank-badge xen-rank-<?php echo esc_attr( sanitize_key( $entry->rank_title ) ); ?>">
					<?php echo esc_html( $entry->rank_title ); ?>
				</span>
			</div>
			<div class="xen-lb-level"><?php printf( esc_html__( 'LV %d', 'xen-levelup' ), $entry->level ); ?></div>
			<div class="xen-lb-score"><?php echo esc_html( number_format( $entry->score ) ); ?> XP</div>
			<div class="xen-lb-quests"><?php echo esc_html( $entry->quests_completed ); ?> <?php esc_html_e( 'quests', 'xen-levelup' ); ?></div>
		</div>
	<?php endforeach; else : ?>
		<p class="xen-empty"><?php esc_html_e( 'Rankings are being calculated. Check back soon!', 'xen-levelup' ); ?></p>
	<?php endif; ?>
	</div>

</div><!-- .xen-rankings-wrap -->
