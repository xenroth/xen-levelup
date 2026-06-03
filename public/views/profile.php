<?php
/**
 * Public view: Character profile.
 * Loaded by [gamified_profile]
 *
 * Variables: $user_data, $atts
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$profile    = $user_data['profile']   ?? null;
$level      = $user_data['level']     ?? 1;
$xp         = $user_data['xp']        ?? 0;
$xp_next    = $user_data['xp_next_level'] ?? 100;
$progress   = $user_data['xp_progress']  ?? 0;
$coins      = $user_data['coins']     ?? 0;
$rank_title = $user_data['rank_title']?? '';
$uid        = $user_data['user_id']   ?? 0;

$user      = get_userdata( $uid );
$stats     = xen_levelup()->stats->get_all_stats( $uid );
$achs      = xen_levelup()->achievements->get_user_achievements( $uid );
$inventory = xen_levelup()->shop->get_inventory( $uid );
$rank_pos  = xen_levelup()->rankings->get_user_rank( $uid );
?>
<div class="xen-wrap xen-profile">

	<div class="xen-profile-header">
		<?php echo get_avatar( $uid, 100, '', '', array( 'class' => 'xen-profile-avatar' ) ); ?>
		<div class="xen-profile-meta">
			<h2 class="xen-profile-name"><?php echo esc_html( $user ? $user->display_name : '' ); ?></h2>
			<?php if ( $profile && $profile->current_title ) : ?>
				<div class="xen-profile-title"><?php echo esc_html( $profile->current_title ); ?></div>
			<?php endif; ?>
			<div class="xen-rank-badge xen-rank-<?php echo esc_attr( sanitize_key( $rank_title ) ); ?>"><?php echo esc_html( $rank_title ); ?></div>
			<div class="xen-profile-rank-pos"><?php printf( esc_html__( 'Global Rank #%d', 'xen-levelup' ), $rank_pos ?: '—' ); ?></div>
		</div>
		<div class="xen-profile-numbers">
			<div class="xen-stat-pill"><span><?php esc_html_e( 'Level', 'xen-levelup' ); ?></span><strong><?php echo esc_html( $level ); ?></strong></div>
			<div class="xen-stat-pill"><span><?php esc_html_e( 'Coins', 'xen-levelup' ); ?></span><strong>🪙 <?php echo esc_html( number_format( $coins ) ); ?></strong></div>
			<div class="xen-stat-pill"><span><?php esc_html_e( 'Quests', 'xen-levelup' ); ?></span><strong><?php echo esc_html( (int) ( $profile->total_quests ?? 0 ) ); ?></strong></div>
			<div class="xen-stat-pill"><span><?php esc_html_e( 'Tasks', 'xen-levelup' ); ?></span><strong><?php echo esc_html( (int) ( $profile->total_tasks ?? 0 ) ); ?></strong></div>
		</div>
	</div>

	<!-- XP Bar -->
	<div class="xen-xp-section">
		<div class="xen-xp-label"><?php printf( esc_html__( 'LV %d — %s / %s XP', 'xen-levelup' ), $level, number_format( $xp ), number_format( $xp_next ) ); ?></div>
		<div class="xen-xp-bar" role="progressbar" aria-valuenow="<?php echo esc_attr( $progress ); ?>" aria-valuemin="0" aria-valuemax="100">
			<div class="xen-xp-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></div>
		</div>
	</div>

	<!-- Life Trees -->
	<?php if ( ! empty( $stats['life_trees'] ) ) : ?>
	<div class="xen-section">
		<h3 class="xen-section-title"><?php esc_html_e( 'Life Development Trees', 'xen-levelup' ); ?></h3>
		<div class="xen-life-trees-grid">
		<?php foreach ( $stats['life_trees'] as $key => $value ) :
			$icon  = $stats['icons'][ $key ] ?? '';
			$label = xen_levelup()->stats->life_tree_label( $key );
			$pct   = min( 100, round( $value / 100 * 100 ) );
		?>
			<div class="xen-tree-item">
				<div class="xen-tree-icon"><?php echo esc_html( $icon ); ?></div>
				<div class="xen-tree-label"><?php echo esc_html( $label ); ?></div>
				<div class="xen-tree-bar">
					<div class="xen-tree-fill xen-tree-<?php echo esc_attr( $key ); ?>" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
				</div>
				<div class="xen-tree-value"><?php echo esc_html( $value ); ?></div>
			</div>
		<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- Achievements -->
	<?php if ( $achs ) : ?>
	<div class="xen-section">
		<h3 class="xen-section-title"><?php esc_html_e( 'Achievements', 'xen-levelup' ); ?></h3>
		<div class="xen-achievements-grid">
		<?php foreach ( $achs as $ach ) : ?>
			<div class="xen-achievement-badge" title="<?php echo esc_attr( $ach->description ); ?>">
				<span class="xen-ach-icon"><?php echo esc_html( $ach->icon ); ?></span>
				<span class="xen-ach-title"><?php echo esc_html( $ach->title ); ?></span>
			</div>
		<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

</div><!-- .xen-profile -->
