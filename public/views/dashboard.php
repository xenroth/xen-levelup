<?php
/**
 * Public view: Main Dashboard.
 * Loaded by [gamified_dashboard]
 *
 * Variables available: $user_data (array from Xen_User::get_full_data)
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
$stats      = $user_data['stats']     ?? array();
$life_trees = $stats['life_trees']    ?? array();
$icons      = $stats['icons']         ?? array();

$daily_quests   = xen_levelup()->daily_quests->get_today( get_current_user_id() );
$notif_count    = xen_levelup()->notifications->unread_count( get_current_user_id() );
$user_id        = get_current_user_id();
$curr_sym       = Xen_Currency::symbol();
$curr_name      = Xen_Currency::name();
$checkin_data   = array(
	'can_checkin'   => xen_levelup()->daily_checkin->can_checkin( $user_id ),
	'streak'        => xen_levelup()->daily_checkin->get_streak( $user_id ),
	'total'         => xen_levelup()->daily_checkin->get_total_checkins( $user_id ),
);
?>
<div class="xen-wrap xen-dashboard" id="xen-dashboard">

	<!-- Header -->
	<div class="xen-hero-card">
		<div class="xen-hero-avatar">
			<?php echo get_avatar( get_current_user_id(), 80, '', '', array( 'class' => 'xen-avatar' ) ); ?>
		</div>
		<div class="xen-hero-info">
			<h2 class="xen-hero-name">
				<?php echo esc_html( wp_get_current_user()->display_name ); ?>
				<?php if ( $profile && $profile->current_title ) : ?>
					<span class="xen-title-badge"><?php echo esc_html( $profile->current_title ); ?></span>
				<?php endif; ?>
			</h2>
			<div class="xen-rank-badge xen-rank-<?php echo esc_attr( sanitize_key( $rank_title ) ); ?>">
				<?php echo esc_html( $rank_title ); ?>
			</div>
			<div class="xen-level-display">
				<span class="xen-level-label"><?php esc_html_e( 'LV', 'xen-levelup' ); ?></span>
				<span class="xen-level-number"><?php echo esc_html( $level ); ?></span>
			</div>
			<!-- XP Bar -->
			<div class="xen-xp-bar-wrap">
				<div class="xen-xp-bar" role="progressbar" aria-valuenow="<?php echo esc_attr( $progress ); ?>" aria-valuemin="0" aria-valuemax="100">
					<div class="xen-xp-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></div>
				</div>
				<div class="xen-xp-text">
					<?php echo esc_html( number_format( $xp ) ); ?> / <?php echo esc_html( number_format( $xp_next ) ); ?> XP
				</div>
			</div>
		</div>
		<div class="xen-hero-stats">
			<div class="xen-coin-display">
				<?php echo esc_html( $curr_sym ); ?> <strong><?php echo esc_html( number_format( $coins ) ); ?></strong>
				<span class="xen-coin-label"><?php echo esc_html( $curr_name ); ?></span>
			</div>
			<div class="xen-notif-wrap" id="xen-notif-wrap">
				<button class="xen-notif-btn" id="xen-notif-btn"
					aria-label="<?php esc_attr_e( 'Notifications', 'xen-levelup' ); ?>"
					aria-expanded="false" aria-haspopup="true">
					🔔
					<?php if ( $notif_count > 0 ) : ?>
					<span class="xen-notif-count" id="xen-notif-count"><?php echo esc_html( $notif_count ); ?></span>
					<?php else : ?>
					<span class="xen-notif-count xen-notif-hidden" id="xen-notif-count" style="display:none">0</span>
					<?php endif; ?>
				</button>
				<div class="xen-notif-panel" id="xen-notif-panel" aria-hidden="true">
					<div class="xen-notif-panel-header">
						<span><?php esc_html_e( 'Notifications', 'xen-levelup' ); ?></span>
						<button class="xen-notif-mark-all xen-btn-ghost xen-btn-xs" id="xen-notif-mark-all">
							<?php esc_html_e( 'Mark all read', 'xen-levelup' ); ?>
						</button>
					</div>
					<div class="xen-notif-list" id="xen-notif-list">
						<div class="xen-notif-loading">
							<span class="xen-spinner"></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Life Trees Overview -->
	<?php if ( $life_trees ) : ?>
	<div class="xen-section">
		<h3 class="xen-section-title"><?php esc_html_e( 'Life Development Trees', 'xen-levelup' ); ?></h3>
		<div class="xen-life-trees-grid">
		<?php foreach ( $life_trees as $key => $value ) :
			$icon  = $icons[ $key ] ?? '';
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

	<!-- Today's Quests -->
	<div class="xen-section">
		<h3 class="xen-section-title"><?php esc_html_e( "Today's Quests", 'xen-levelup' ); ?></h3>
		<?php if ( $daily_quests ) : ?>
		<div class="xen-quest-list">
		<?php foreach ( $daily_quests as $quest ) : ?>
			<div class="xen-quest-card xen-diff-<?php echo esc_attr( $quest->difficulty ); ?> <?php echo esc_attr( 'completed' === $quest->status ? 'xen-quest-done' : '' ); ?>"
				 data-quest-id="<?php echo esc_attr( $quest->id ); ?>">
				<div class="xen-quest-info">
					<span class="xen-quest-title"><?php echo esc_html( $quest->title ); ?></span>
					<span class="xen-quest-diff-badge"><?php echo esc_html( xen_levelup()->quests->difficulty_label( $quest->difficulty ) ); ?></span>
				</div>
				<div class="xen-quest-rewards">
					⭐ <?php echo esc_html( $quest->xp_reward ); ?> XP &nbsp;
					<?php echo esc_html( $curr_sym ); ?> <?php echo esc_html( $quest->coin_reward ); ?>
				</div>
				<?php if ( 'completed' !== $quest->status ) : ?>
				<button class="xen-btn xen-btn-complete xen-complete-quest" data-id="<?php echo esc_attr( $quest->id ); ?>">
					<?php esc_html_e( 'Complete', 'xen-levelup' ); ?>
				</button>
				<?php else : ?>
				<span class="xen-done-badge">✓ <?php esc_html_e( 'Done', 'xen-levelup' ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		</div>
		<?php else : ?>
		<p class="xen-empty"><?php esc_html_e( 'No quests for today yet.', 'xen-levelup' ); ?></p>
		<?php endif; ?>
	</div>

	<!-- Daily Check-In -->
	<div class="xen-section">
		<h3 class="xen-section-title"><?php esc_html_e( 'Daily Check-In', 'xen-levelup' ); ?></h3>
		<div class="xen-checkin-card" id="xen-checkin-card">
			<div class="xen-checkin-streak">
				<span class="xen-streak-icon">🔥</span>
				<span class="xen-streak-count" id="xen-streak-count"><?php echo esc_html( $checkin_data['streak'] ); ?></span>
				<span class="xen-streak-label"><?php esc_html_e( 'Day Streak', 'xen-levelup' ); ?></span>
			</div>
			<div class="xen-checkin-reward-preview">
				<?php
				$milestones = floor( ( $checkin_data['streak'] + 1 ) / 7 );
				$preview_xp    = 50 + ( $milestones * 25 );
				$preview_coins = 10 + ( $milestones * 10 );
				?>
				<span>⭐ +<?php echo esc_html( $preview_xp ); ?> XP</span>
				<span><?php echo esc_html( $curr_sym ); ?> +<?php echo esc_html( $preview_coins ); ?> <?php echo esc_html( $curr_name ); ?></span>
			</div>
			<?php if ( $checkin_data['can_checkin'] ) : ?>
			<button class="xen-btn xen-btn-checkin" id="xen-checkin-btn">
				📅 <?php esc_html_e( 'Check In Today', 'xen-levelup' ); ?>
			</button>
			<?php else : ?>
			<div class="xen-checkin-done">
				✅ <?php esc_html_e( 'Checked in today!', 'xen-levelup' ); ?>
			</div>
			<?php endif; ?>
			<div class="xen-checkin-total">
				<?php printf(
					esc_html( _n( '%d total check-in', '%d total check-ins', $checkin_data['total'], 'xen-levelup' ) ),
					(int) $checkin_data['total']
				); ?>
			</div>
		</div>
	</div>

</div><!-- .xen-dashboard -->
