<?php
/**
 * Public view: Stats.
 * Loaded by [gamified_stats]
 *
 * Variables: $user_id, $stats
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$life_trees = $stats['life_trees'] ?? array();
$rpg        = $stats['rpg']        ?? array();
$icons      = $stats['icons']      ?? array();
?>
<div class="xen-wrap xen-stats-wrap">

	<h3 class="xen-section-title">📊 <?php esc_html_e( 'Character Stats', 'xen-levelup' ); ?></h3>

	<!-- Life Development Trees -->
	<div class="xen-section">
		<h4 class="xen-subsection-title"><?php esc_html_e( 'Life Development Trees', 'xen-levelup' ); ?></h4>
		<div class="xen-life-trees-list">
		<?php foreach ( $life_trees as $key => $value ) :
			$icon  = $icons[ $key ] ?? '';
			$label = xen_levelup()->stats->life_tree_label( $key );
			$pct   = min( 100, round( $value / 100 * 100 ) );
		?>
			<div class="xen-stat-row">
				<div class="xen-stat-label">
					<span class="xen-stat-icon"><?php echo esc_html( $icon ); ?></span>
					<?php echo esc_html( $label ); ?>
				</div>
				<div class="xen-stat-bar-wrap">
					<div class="xen-stat-bar">
						<div class="xen-stat-fill xen-tree-<?php echo esc_attr( $key ); ?>" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
					</div>
					<span class="xen-stat-val"><?php echo esc_html( $value ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
	</div>

	<!-- RPG Stats -->
	<?php if ( $rpg ) : ?>
	<div class="xen-section">
		<h4 class="xen-subsection-title"><?php esc_html_e( 'RPG Attributes', 'xen-levelup' ); ?></h4>
		<div class="xen-rpg-grid">
		<?php foreach ( $rpg as $key => $value ) :
			$label = xen_levelup()->stats->rpg_stat_label( $key );
			$pct   = min( 100, round( $value / 100 * 100 ) );
		?>
			<div class="xen-rpg-stat">
				<div class="xen-rpg-label"><?php echo esc_html( $label ); ?></div>
				<div class="xen-rpg-bar">
					<div class="xen-rpg-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div>
				</div>
				<div class="xen-rpg-val"><?php echo esc_html( $value ); ?></div>
			</div>
		<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

</div><!-- .xen-stats-wrap -->
