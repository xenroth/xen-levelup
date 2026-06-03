<?php
/**
 * Admin view: Dashboard overview.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$total_users    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_profiles" );
$active_quests  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_quests WHERE status='active'" );
$quests_done    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_quests WHERE status='completed'" );
$tasks_done     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_user_tasks WHERE status='completed'" );
$habits_logged  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}xen_habit_logs" );
$total_coins    = (int) $wpdb->get_var( "SELECT SUM(coins) FROM {$wpdb->prefix}xen_user_profiles" );
?>
<div class="wrap xen-admin-wrap">
	<h1 class="xen-admin-title">⚔️ <?php esc_html_e( 'XEN LevelUp — Dashboard', 'xen-levelup' ); ?></h1>

	<!-- ── Plugin Info ──────────────────────────────────────────────────── -->
	<div class="xen-plugin-info-card">
		<div class="xen-plugin-brand">
			<span class="xen-plugin-logo">⚔️</span>
			<div>
				<div class="xen-plugin-name">XEN LevelUp</div>
				<div class="xen-plugin-version">
					<?php esc_html_e( 'Version', 'xen-levelup' ); ?>
					<strong><?php echo esc_html( XEN_LEVELUP_VERSION ); ?></strong>
					&nbsp;·&nbsp;
					<a href="https://github.com/xenroth/xen-levelup/releases" target="_blank" rel="noopener">
						<?php esc_html_e( 'Release Notes', 'xen-levelup' ); ?>
					</a>
				</div>
			</div>
		</div>
		<div class="xen-plugin-meta">
			<span class="xen-credit-full">
				<?php esc_html_e( 'Developed by', 'xen-levelup' ); ?>
				Richard C. Cupal, LPT (Xenroth) — Xenroth Digital Innovations
			</span>
			<span class="xen-plugin-links">
				<a href="https://xenroth.com" target="_blank" rel="noopener">xenroth.com</a>
				&nbsp;·&nbsp;
				<a href="https://github.com/xenroth/xen-levelup" target="_blank" rel="noopener">GitHub</a>
			</span>
		</div>
		<div class="xen-plugin-actions">
			<a href="<?php echo esc_url( admin_url( 'update-core.php?force-check=1' ) ); ?>" class="button button-primary">
				🔄 <?php esc_html_e( 'Check for Updates', 'xen-levelup' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=xen-levelup-settings' ) ); ?>" class="button">
				⚙️ <?php esc_html_e( 'Settings', 'xen-levelup' ); ?>
			</a>
		</div>
	</div>

	<!-- ── What's New ──────────────────────────────────────────────────── -->
	<?php
	$whats_new         = Xen_Overview::whats_new();
	$admin_dismissed   = get_option( 'xen_admin_whats_new_dismissed' ) === XEN_LEVELUP_VERSION;
	$show_admin_new    = ! empty( $whats_new ) && ! $admin_dismissed;
	?>
	<?php if ( $show_admin_new ) : ?>
	<div class="xen-whats-new-admin-card" id="xen-admin-whats-new">
		<div class="xen-whats-new-header">
			<span class="xen-whats-new-title">⚡ <?php esc_html_e( "What's New in v", 'xen-levelup' ); ?><?php echo esc_html( XEN_LEVELUP_VERSION ); ?></span>
			<button class="xen-whats-new-dismiss button" id="xen-admin-dismiss-whats-new"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_admin_dismiss_whats_new' ) ); ?>"
				aria-label="<?php esc_attr_e( 'Dismiss', 'xen-levelup' ); ?>">
				✕ <?php esc_html_e( 'Dismiss', 'xen-levelup' ); ?>
			</button>
		</div>
		<div class="xen-whats-new-items">
			<?php foreach ( $whats_new as $item ) : ?>
			<div class="xen-whats-new-item">
				<span class="xen-whats-new-icon"><?php echo esc_html( $item['icon'] ); ?></span>
				<div class="xen-whats-new-text">
					<strong><?php echo esc_html( $item['title'] ); ?></strong>
					<p><?php echo esc_html( $item['desc'] ); ?></p>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- ── Stats ────────────────────────────────────────────────────────── -->
	<div class="xen-admin-cards">
		<div class="xen-admin-card">
			<span class="xen-card-icon">👤</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $total_users ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Total Hunters', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">📋</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $active_quests ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Active Quests', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">✅</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $quests_done ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Quests Completed', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">⚡</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $tasks_done ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Tasks Done', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon">🔥</span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $habits_logged ) ); ?></div>
			<div class="xen-card-label"><?php esc_html_e( 'Habit Entries', 'xen-levelup' ); ?></div>
		</div>
		<div class="xen-admin-card">
			<span class="xen-card-icon"><?php echo esc_html( Xen_Currency::symbol() ); ?></span>
			<div class="xen-card-value"><?php echo esc_html( number_format( $total_coins ) ); ?></div>
			<div class="xen-card-label"><?php echo esc_html( Xen_Currency::name() ); ?> <?php esc_html_e( 'in Circulation', 'xen-levelup' ); ?></div>
		</div>
	</div>

	<!-- ── Top 10 Hunters ───────────────────────────────────────────────── -->
	<h2><?php esc_html_e( 'Top 10 Hunters', 'xen-levelup' ); ?></h2>
	<?php
	$top = xen_levelup()->rankings->get_leaderboard( 'global', 'all', 10 );
	if ( $top ) :
	?>
	<table class="wp-list-table widefat striped xen-admin-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Rank', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Hunter', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Level', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Title', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'XP', 'xen-levelup' ); ?></th>
				<th><?php esc_html_e( 'Quests', 'xen-levelup' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $top as $row ) : ?>
			<tr>
				<td><strong>#<?php echo esc_html( $row->rank_position ); ?></strong></td>
				<td><?php echo esc_html( $row->display_name ); ?></td>
				<td><?php echo esc_html( $row->level ); ?></td>
				<td><?php echo esc_html( $row->rank_title ); ?></td>
				<td><?php echo esc_html( number_format( $row->score ) ); ?></td>
				<td><?php echo esc_html( $row->quests_completed ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
	<p><?php esc_html_e( 'No rankings data yet. Rankings update twice daily.', 'xen-levelup' ); ?></p>
	<?php endif; ?>

	<!-- ── Two-column lower section ─────────────────────────────────────── -->
	<div class="xen-admin-info-grid">

		<!-- Available Shortcodes -->
		<div class="xen-admin-info-box">
			<h2>🔖 <?php esc_html_e( 'Available Shortcodes', 'xen-levelup' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Place these shortcodes on any WordPress page. Assign the pages in Settings → Page Assignments.', 'xen-levelup' ); ?></p>
			<table class="wp-list-table widefat striped xen-admin-table">
				<thead><tr>
					<th><?php esc_html_e( 'Shortcode', 'xen-levelup' ); ?></th>
					<th><?php esc_html_e( 'Description', 'xen-levelup' ); ?></th>
				</tr></thead>
				<tbody>
					<tr>
						<td><code>[gamified_dashboard]</code></td>
						<td><?php esc_html_e( 'Main hunter dashboard — XP bar, life trees, daily quests, check-in, overview stats.', 'xen-levelup' ); ?></td>
					</tr>
					<tr>
						<td><code>[gamified_profile]</code></td>
						<td><?php esc_html_e( 'Hunter profile — stats, achievements, inventory, quest history.', 'xen-levelup' ); ?></td>
					</tr>
					<tr>
						<td><code>[gamified_shop]</code></td>
						<td><?php esc_html_e( 'In-game shop — browse and purchase items with earned coins.', 'xen-levelup' ); ?></td>
					</tr>
					<tr>
						<td><code>[gamified_rankings]</code></td>
						<td><?php esc_html_e( 'Leaderboard — global and weekly hunter rankings.', 'xen-levelup' ); ?></td>
					</tr>
					<tr>
						<td><code>[gamified_onboarding]</code></td>
						<td><?php esc_html_e( 'Onboarding wizard — shown automatically to new users on first login.', 'xen-levelup' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Features -->
		<div class="xen-admin-info-box">
			<h2>🗡️ <?php esc_html_e( 'Features', 'xen-levelup' ); ?></h2>
			<ul class="xen-feature-list">
				<li>⚔️ <strong><?php esc_html_e( 'Hunter Progression', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Level 1–100 with rank titles (E-Rank → Shadow Monarch), 8 RPG stats, XP formula.', 'xen-levelup' ); ?></li>
				<li>🌳 <strong><?php esc_html_e( 'Life Development Trees', 'xen-levelup' ); ?></strong> — <?php esc_html_e( '10 life areas tracked: Physique, Intelligence, Discipline, Wealth, and more.', 'xen-levelup' ); ?></li>
				<li>📜 <strong><?php esc_html_e( 'Quest System', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Daily, random (hourly), special, and legendary quest types with XP & coin rewards.', 'xen-levelup' ); ?></li>
				<li>✅ <strong><?php esc_html_e( 'Task Manager', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Personal task lists with XP rewards on completion.', 'xen-levelup' ); ?></li>
				<li>🔥 <strong><?php esc_html_e( 'Habit Tracker', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Daily habit logging with streaks and XP rewards.', 'xen-levelup' ); ?></li>
				<li>🏆 <strong><?php esc_html_e( 'Achievements', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Unlock badges for milestones in leveling, quests, tasks, and habits.', 'xen-levelup' ); ?></li>
				<li>🏪 <strong><?php esc_html_e( 'In-Game Shop', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Items purchasable with coins; equip profile frames, titles, badges.', 'xen-levelup' ); ?></li>
				<li>📅 <strong><?php esc_html_e( 'Daily Check-In', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Streak-based daily rewards; bonus every 7-day milestone.', 'xen-levelup' ); ?></li>
				<li>📊 <strong><?php esc_html_e( 'Rankings', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Global and weekly leaderboards updated twice daily via cron.', 'xen-levelup' ); ?></li>
				<li>🔔 <strong><?php esc_html_e( 'Notifications', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'In-plugin notification bell with real-time unread count.', 'xen-levelup' ); ?></li>
				<li>💎 <strong><?php esc_html_e( 'Custom Currency', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'Rename and re-symbol the in-game currency from Settings.', 'xen-levelup' ); ?></li>
				<li>🔄 <strong><?php esc_html_e( 'Auto-Updates', 'xen-levelup' ); ?></strong> — <?php esc_html_e( 'GitHub-based auto-updater; updates appear in the standard WP Updates screen.', 'xen-levelup' ); ?></li>
			</ul>
		</div>

	</div><!-- .xen-admin-info-grid -->

	<!-- ── Getting Started ───────────────────────────────────────────────── -->
	<div class="xen-admin-info-box xen-getting-started">
		<h2>🚀 <?php esc_html_e( 'Getting Started', 'xen-levelup' ); ?></h2>
		<div class="xen-steps">

			<div class="xen-step">
				<div class="xen-step-num">1</div>
				<div class="xen-step-body">
					<strong><?php esc_html_e( 'Create Pages', 'xen-levelup' ); ?></strong>
					<p><?php esc_html_e( 'Create five WordPress pages and add the matching shortcodes: Dashboard, Profile, Shop, Rankings, Onboarding.', 'xen-levelup' ); ?></p>
				</div>
			</div>

			<div class="xen-step">
				<div class="xen-step-num">2</div>
				<div class="xen-step-body">
					<strong><?php esc_html_e( 'Assign Pages in Settings', 'xen-levelup' ); ?></strong>
					<p><?php
					printf(
						/* translators: %s = settings page link */
						esc_html__( 'Go to %s and map each page to its shortcode so the plugin can redirect users correctly.', 'xen-levelup' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=xen-levelup-settings' ) ) . '">' . esc_html__( 'XEN LevelUp → Settings', 'xen-levelup' ) . '</a>'
					);
					?></p>
				</div>
			</div>

			<div class="xen-step">
				<div class="xen-step-num">3</div>
				<div class="xen-step-body">
					<strong><?php esc_html_e( 'Add Quest Templates', 'xen-levelup' ); ?></strong>
					<p><?php esc_html_e( 'Use the Quests admin tab to create quest templates. The cron system uses these to generate daily and random quests automatically.', 'xen-levelup' ); ?></p>
				</div>
			</div>

			<div class="xen-step">
				<div class="xen-step-num">4</div>
				<div class="xen-step-body">
					<strong><?php esc_html_e( 'Add Shop Items', 'xen-levelup' ); ?></strong>
					<p><?php esc_html_e( 'Populate the Shop tab with purchasable items (profile frames, titles, badges) so hunters have something to spend their coins on.', 'xen-levelup' ); ?></p>
				</div>
			</div>

			<div class="xen-step">
				<div class="xen-step-num">5</div>
				<div class="xen-step-body">
					<strong><?php esc_html_e( 'Invite Users', 'xen-levelup' ); ?></strong>
					<p><?php esc_html_e( 'New users are automatically prompted through the onboarding wizard on first login. Their hunter profile is created immediately after completing onboarding.', 'xen-levelup' ); ?></p>
				</div>
			</div>

			<div class="xen-step">
				<div class="xen-step-num">6</div>
				<div class="xen-step-body">
					<strong><?php esc_html_e( 'Customise (Optional)', 'xen-levelup' ); ?></strong>
					<p><?php esc_html_e( 'Rename the in-game currency, toggle random quests, adjust legendary quest recipients per week, and set up a GitHub token for private-repo update checks — all in Settings.', 'xen-levelup' ); ?></p>
				</div>
			</div>

		</div>
	</div><!-- .xen-getting-started -->

</div><!-- .xen-admin-wrap -->
