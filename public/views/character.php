<?php
/**
 * Public view: Character sheet.
 * Loaded by [gamified_character]
 *
 * Variables: $user_data, $stats
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$profile    = $user_data['profile']   ?? null;
$level      = $user_data['level']     ?? 1;
$rank_title = $user_data['rank_title']?? '';
$progress   = $user_data['xp_progress'] ?? 0;
$uid        = $user_data['user_id']   ?? 0;
$user       = get_userdata( $uid );
$life_trees = $stats['life_trees']    ?? array();
$rpg        = $stats['rpg']           ?? array();
$icons      = $stats['icons']         ?? array();
?>
<div class="xen-wrap xen-character-wrap">
	<div class="xen-character-sheet">

		<!-- Left panel: identity -->
		<div class="xen-char-identity">
			<?php echo get_avatar( $uid, 120, '', '', array( 'class' => 'xen-char-avatar' ) ); ?>
			<h3 class="xen-char-name"><?php echo esc_html( $user ? $user->display_name : '' ); ?></h3>
			<?php if ( $profile && $profile->current_title ) : ?>
			<div class="xen-char-title"><?php echo esc_html( $profile->current_title ); ?></div>
			<?php endif; ?>
			<div class="xen-rank-badge xen-rank-<?php echo esc_attr( sanitize_key( $rank_title ) ); ?> xen-rank-lg">
				<?php echo esc_html( $rank_title ); ?>
			</div>
			<div class="xen-char-level">LV <span><?php echo esc_html( $level ); ?></span></div>
			<div class="xen-xp-bar">
				<div class="xen-xp-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></div>
			</div>
		</div>

		<!-- Right panel: trees + RPG -->
		<div class="xen-char-stats">
			<h4><?php esc_html_e( 'Life Trees', 'xen-levelup' ); ?></h4>
			<?php foreach ( $life_trees as $key => $value ) :
				$icon  = $icons[ $key ] ?? '';
				$label = xen_levelup()->stats->life_tree_label( $key );
				$pct   = min( 100, round( $value / 100 * 100 ) );
			?>
			<div class="xen-stat-row">
				<span class="xen-stat-label"><?php echo esc_html( $icon . ' ' . $label ); ?></span>
				<div class="xen-stat-bar"><div class="xen-stat-fill xen-tree-<?php echo esc_attr( $key ); ?>" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
				<span class="xen-stat-val"><?php echo esc_html( $value ); ?></span>
			</div>
			<?php endforeach; ?>

			<?php if ( $rpg ) : ?>
			<h4 style="margin-top:1.5rem;"><?php esc_html_e( 'RPG Attributes', 'xen-levelup' ); ?></h4>
			<div class="xen-rpg-grid">
			<?php foreach ( $rpg as $key => $value ) : ?>
				<div class="xen-rpg-stat">
					<div class="xen-rpg-label"><?php echo esc_html( xen_levelup()->stats->rpg_stat_label( $key ) ); ?></div>
					<div class="xen-rpg-bar"><div class="xen-rpg-fill" style="width:<?php echo esc_attr( min( 100, round( $value / 100 * 100 ) ) ); ?>%"></div></div>
					<div class="xen-rpg-val"><?php echo esc_html( $value ); ?></div>
				</div>
			<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

	</div><!-- .xen-character-sheet -->
</div><!-- .xen-character-wrap -->
