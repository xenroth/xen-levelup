<?php
/**
 * Admin view: User list.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$paged   = max( 1, (int) ( $_GET['paged']  ?? 1 ) ); // phpcs:ignore
$search  = sanitize_text_field( $_GET['s'] ?? '' );   // phpcs:ignore
$per     = 20;
$data    = xen_levelup()->user->get_all_users( $per, $paged, $search );
$users   = $data['users'];
$total   = $data['total'];
$pages   = ceil( $total / $per );
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">👤 <?php esc_html_e( 'Hunters', 'xen-levelup' ); ?></h1>

	<form method="get">
		<input type="hidden" name="page" value="xen-levelup-users">
		<p class="search-box">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
				   placeholder="<?php esc_attr_e( 'Search by username…', 'xen-levelup' ); ?>">
			<button class="button"><?php esc_html_e( 'Search', 'xen-levelup' ); ?></button>
		</p>
	</form>

	<table class="wp-list-table widefat striped xen-admin-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'User', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Level', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Rank', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'XP', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Coins', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Quests', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Tasks', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Joined', 'xen-levelup' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $users ) : foreach ( $users as $u ) : ?>
			<tr>
				<td>
					<strong><a href="<?php echo esc_url( get_edit_user_link( $u->ID ) ); ?>"><?php echo esc_html( $u->display_name ); ?></a></strong><br>
					<span class="description"><?php echo esc_html( $u->user_email ); ?></span>
				</td>
				<td><?php echo esc_html( $u->level ?? '—' ); ?></td>
				<td><?php echo esc_html( $u->rank_title ?? '—' ); ?></td>
				<td><?php echo esc_html( number_format( (int) ( $u->experience ?? 0 ) ) ); ?></td>
				<td><?php echo esc_html( number_format( (int) ( $u->coins ?? 0 ) ) ); ?></td>
				<td><?php echo esc_html( (int) ( $u->total_quests ?? 0 ) ); ?></td>
				<td><?php echo esc_html( (int) ( $u->total_tasks ?? 0 ) ); ?></td>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $u->user_registered ) ) ); ?></td>
			</tr>
		<?php endforeach; else : ?>
			<tr><td colspan="8"><?php esc_html_e( 'No users found.', 'xen-levelup' ); ?></td></tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
	<div class="tablenav bottom">
		<div class="tablenav-pages">
			<?php
			echo paginate_links( array( // phpcs:ignore
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $paged,
				'total'   => $pages,
			) );
			?>
		</div>
	</div>
	<?php endif; ?>
</div>
