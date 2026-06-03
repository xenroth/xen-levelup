<?php
/**
 * Admin view: Quest templates.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$category = sanitize_key( $_GET['category'] ?? '' ); // phpcs:ignore
$type     = sanitize_key( $_GET['type'] ?? '' );     // phpcs:ignore
$templates = xen_levelup()->quests->get_templates( $category ?: null, $type ?: null );
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">📋 <?php esc_html_e( 'Quest Templates', 'xen-levelup' ); ?></h1>

	<form method="get" style="margin-bottom:1em;">
		<input type="hidden" name="page" value="xen-levelup-quests">
		<select name="category">
			<option value=""><?php esc_html_e( 'All Categories', 'xen-levelup' ); ?></option>
			<?php foreach ( Xen_Stats::LIFE_TREES as $slug => $label ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $category, $slug ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<select name="type">
			<option value=""><?php esc_html_e( 'All Types', 'xen-levelup' ); ?></option>
			<?php foreach ( Xen_Quests::TYPES as $slug => $label ) : ?>
				<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $type, $slug ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button class="button"><?php esc_html_e( 'Filter', 'xen-levelup' ); ?></button>
	</form>

	<p><?php printf( esc_html__( '%d templates found.', 'xen-levelup' ), count( $templates ) ); ?></p>

	<table class="wp-list-table widefat striped xen-admin-table">
		<thead>
			<tr>
				<th>ID</th>
				<th><?php esc_html_e( 'Title', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Category', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Type', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Difficulty', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'XP', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Coins', 'xen-levelup' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $templates ) : foreach ( $templates as $t ) : ?>
			<tr>
				<td><?php echo esc_html( $t->id ); ?></td>
				<td><?php echo esc_html( $t->title ); ?></td>
				<td><?php echo esc_html( $t->category ); ?></td>
				<td><?php echo esc_html( $t->quest_type ); ?></td>
				<td><span class="xen-diff-<?php echo esc_attr( $t->difficulty ); ?>"><?php echo esc_html( xen_levelup()->quests->difficulty_label( $t->difficulty ) ); ?></span></td>
				<td><?php echo esc_html( $t->xp_reward ); ?></td>
				<td><?php echo esc_html( $t->coin_reward ); ?></td>
			</tr>
		<?php endforeach; else : ?>
			<tr><td colspan="7"><?php esc_html_e( 'No templates found.', 'xen-levelup' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
