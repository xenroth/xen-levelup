<?php
/**
 * Public view: Quest Hub — unified quest management page.
 * Loaded by [gamified_quest_hub]
 *
 * Variables: $user_id, $daily, $special, $legendary
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nonce = wp_create_nonce( 'xen_nonce' );

// Merge special + legendary for "Side/Legendary" sections
$pending_special   = array_filter( (array) $special,   fn( $q ) => 'pending' === $q->status );
$active_special    = array_filter( (array) $special,   fn( $q ) => 'active'  === $q->status );
$pending_legendary = array_filter( (array) $legendary, fn( $q ) => 'pending' === $q->status );
$active_legendary  = array_filter( (array) $legendary, fn( $q ) => 'active'  === $q->status );

$daily_total     = count( (array) $daily );
$daily_completed = count( array_filter( (array) $daily, fn( $q ) => 'completed' === $q->status ) );

function xen_hub_diff_badge( $difficulty ) {
	$class = Xen_Quests::difficulty_class( $difficulty );
	$label = Xen_Quests::difficulty_label( $difficulty );
	return '<span class="xen-diff-badge xen-diff-' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
}
?>
<div class="xen-wrap xen-quest-hub" id="xen-quest-hub" data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<div class="xen-hub-header">
		<h2 class="xen-hub-title">
			<span class="xen-hub-icon">⚔️</span>
			<?php esc_html_e( 'Quest Hub', 'xen-levelup' ); ?>
		</h2>
		<div class="xen-hub-progress">
			<span class="xen-hub-progress-label"><?php printf( esc_html__( 'Daily: %d / %d', 'xen-levelup' ), $daily_completed, $daily_total ); ?></span>
			<?php if ( $daily_total > 0 ) : ?>
			<div class="xen-hub-progress-bar">
				<div class="xen-hub-progress-fill" style="width:<?php echo esc_attr( round( $daily_completed / $daily_total * 100 ) ); ?>%"></div>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- ── Tabs ─────────────────────────────────────────────────────── -->
	<div class="xen-hub-tabs" role="tablist">
		<button class="xen-hub-tab xen-hub-tab-active" data-tab="daily" role="tab" aria-selected="true">
			📅 <?php esc_html_e( 'Daily', 'xen-levelup' ); ?>
			<?php if ( $daily_total > 0 ) : ?><span class="xen-tab-count"><?php echo esc_html( $daily_completed . '/' . $daily_total ); ?></span><?php endif; ?>
		</button>
		<button class="xen-hub-tab" data-tab="special" role="tab" aria-selected="false">
			🌟 <?php esc_html_e( 'Side Quests', 'xen-levelup' ); ?>
			<?php $sc = count( (array) $special ); if ( $sc ) : ?><span class="xen-tab-count"><?php echo esc_html( $sc ); ?></span><?php endif; ?>
		</button>
		<button class="xen-hub-tab" data-tab="legendary" role="tab" aria-selected="false">
			👑 <?php esc_html_e( 'Legendary', 'xen-levelup' ); ?>
			<?php $lc = count( (array) $legendary ); if ( $lc ) : ?><span class="xen-tab-count xen-tab-legendary"><?php echo esc_html( $lc ); ?></span><?php endif; ?>
		</button>
		<button class="xen-hub-tab" data-tab="history" role="tab" aria-selected="false">
			📜 <?php esc_html_e( 'History', 'xen-levelup' ); ?>
		</button>
	</div>

	<!-- ── Daily Quests Panel ─────────────────────────────────────── -->
	<div class="xen-hub-panel" id="xen-panel-daily" role="tabpanel">
		<div class="xen-quest-list" id="xen-daily-hub-list">
		<?php if ( $daily ) : foreach ( $daily as $quest ) :
			$done = 'completed' === $quest->status;
			$icon = Xen_Stats::LIFE_TREE_ICONS[ $quest->category ] ?? '📋';
		?>
			<div class="xen-hub-card xen-diff-<?php echo esc_attr( $quest->difficulty ); ?> <?php echo $done ? 'xen-quest-done' : ''; ?>"
				 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
				<div class="xen-hub-card-left">
					<span class="xen-hub-cat-icon"><?php echo esc_html( $icon ); ?></span>
				</div>
				<div class="xen-hub-card-body">
					<div class="xen-hub-card-title"><?php echo esc_html( $quest->title ); ?></div>
					<?php if ( $quest->description ) : ?>
					<div class="xen-hub-card-desc"><?php echo esc_html( $quest->description ); ?></div>
					<?php endif; ?>
					<div class="xen-hub-card-meta">
						<?php echo wp_kses_post( xen_hub_diff_badge( $quest->difficulty ) ); ?>
						<span class="xen-reward-tag">⭐ <?php echo esc_html( number_format( $quest->xp_reward ) ); ?> XP</span>
						<span class="xen-reward-tag">🪙 <?php echo esc_html( number_format( $quest->coin_reward ) ); ?></span>
					</div>
				</div>
				<div class="xen-hub-card-actions">
					<?php if ( $done ) : ?>
						<span class="xen-done-badge">✓ <?php esc_html_e( 'Done', 'xen-levelup' ); ?></span>
					<?php else : ?>
						<button class="xen-btn xen-btn-complete xen-complete-quest"
								data-id="<?php echo esc_attr( $quest->id ); ?>">
							<?php esc_html_e( 'Complete', 'xen-levelup' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; else : ?>
			<div class="xen-empty-state">
				<div class="xen-empty-icon">📋</div>
				<p><?php esc_html_e( 'No daily quests assigned yet. Check back tomorrow!', 'xen-levelup' ); ?></p>
			</div>
		<?php endif; ?>
		</div>
	</div>

	<!-- ── Special/Side Quests Panel ─────────────────────────────── -->
	<div class="xen-hub-panel xen-hub-panel-hidden" id="xen-panel-special" role="tabpanel">

		<?php if ( $pending_special ) : ?>
		<div class="xen-hub-section-label"><?php esc_html_e( '📬 Available — Accept to Begin', 'xen-levelup' ); ?></div>
		<div class="xen-quest-list">
		<?php foreach ( $pending_special as $quest ) : ?>
			<div class="xen-hub-card xen-hub-card-pending xen-diff-<?php echo esc_attr( $quest->difficulty ); ?>"
				 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
				<div class="xen-hub-card-left">
					<span class="xen-hub-cat-icon">🌟</span>
				</div>
				<div class="xen-hub-card-body">
					<div class="xen-hub-card-title"><?php echo esc_html( $quest->title ); ?></div>
					<?php if ( $quest->description ) : ?>
					<div class="xen-hub-card-desc"><?php echo esc_html( $quest->description ); ?></div>
					<?php endif; ?>
					<div class="xen-hub-card-meta">
						<?php echo wp_kses_post( xen_hub_diff_badge( $quest->difficulty ) ); ?>
						<span class="xen-reward-tag">⭐ <?php echo esc_html( number_format( $quest->xp_reward ) ); ?> XP</span>
						<span class="xen-reward-tag">🪙 <?php echo esc_html( number_format( $quest->coin_reward ) ); ?></span>
						<?php if ( $quest->expires_at ) : ?>
						<span class="xen-expires-tag">⏰ <?php printf( esc_html__( 'Expires %s', 'xen-levelup' ), esc_html( date_i18n( 'M j', strtotime( $quest->expires_at ) ) ) ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="xen-hub-card-actions">
					<button class="xen-btn xen-btn-accept xen-accept-quest"
							data-id="<?php echo esc_attr( $quest->id ); ?>">
						<?php esc_html_e( 'Accept Quest', 'xen-levelup' ); ?>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $active_special ) : ?>
		<div class="xen-hub-section-label"><?php esc_html_e( '⚔️ In Progress', 'xen-levelup' ); ?></div>
		<div class="xen-quest-list">
		<?php foreach ( $active_special as $quest ) : ?>
			<div class="xen-hub-card xen-hub-card-active xen-diff-<?php echo esc_attr( $quest->difficulty ); ?>"
				 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
				<div class="xen-hub-card-left">
					<span class="xen-hub-cat-icon">🌟</span>
				</div>
				<div class="xen-hub-card-body">
					<div class="xen-hub-card-title"><?php echo esc_html( $quest->title ); ?></div>
					<?php if ( $quest->description ) : ?>
					<div class="xen-hub-card-desc"><?php echo esc_html( $quest->description ); ?></div>
					<?php endif; ?>
					<div class="xen-hub-card-meta">
						<?php echo wp_kses_post( xen_hub_diff_badge( $quest->difficulty ) ); ?>
						<span class="xen-reward-tag">⭐ <?php echo esc_html( number_format( $quest->xp_reward ) ); ?> XP</span>
						<span class="xen-reward-tag">🪙 <?php echo esc_html( number_format( $quest->coin_reward ) ); ?></span>
						<?php if ( $quest->expires_at ) : ?>
						<span class="xen-expires-tag">⏰ <?php printf( esc_html__( 'Expires %s', 'xen-levelup' ), esc_html( date_i18n( 'M j', strtotime( $quest->expires_at ) ) ) ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="xen-hub-card-actions">
					<button class="xen-btn xen-btn-complete xen-complete-quest"
							data-id="<?php echo esc_attr( $quest->id ); ?>">
						<?php esc_html_e( 'Submit', 'xen-levelup' ); ?>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! $pending_special && ! $active_special ) : ?>
		<div class="xen-empty-state">
			<div class="xen-empty-icon">🌟</div>
			<p><?php esc_html_e( 'No side quests available this week. New quests are assigned every Monday.', 'xen-levelup' ); ?></p>
		</div>
		<?php endif; ?>
	</div>

	<!-- ── Legendary Quests Panel ────────────────────────────────── -->
	<div class="xen-hub-panel xen-hub-panel-hidden" id="xen-panel-legendary" role="tabpanel">

		<?php if ( $pending_legendary ) : ?>
		<div class="xen-hub-section-label xen-legendary-label"><?php esc_html_e( '👑 You Have Been Chosen — Accept Your Destiny', 'xen-levelup' ); ?></div>
		<div class="xen-quest-list">
		<?php foreach ( $pending_legendary as $quest ) : ?>
			<div class="xen-hub-card xen-hub-card-legendary xen-hub-card-pending"
				 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
				<div class="xen-legendary-glow"></div>
				<div class="xen-hub-card-left">
					<span class="xen-hub-cat-icon">👑</span>
				</div>
				<div class="xen-hub-card-body">
					<div class="xen-hub-card-title"><?php echo esc_html( $quest->title ); ?></div>
					<?php if ( $quest->description ) : ?>
					<div class="xen-hub-card-desc"><?php echo esc_html( $quest->description ); ?></div>
					<?php endif; ?>
					<div class="xen-hub-card-meta">
						<span class="xen-diff-badge xen-diff-legendary"><?php esc_html_e( 'Legendary', 'xen-levelup' ); ?></span>
						<span class="xen-reward-tag xen-reward-legendary">⭐ <?php echo esc_html( number_format( $quest->xp_reward ) ); ?> XP</span>
						<span class="xen-reward-tag xen-reward-legendary">🪙 <?php echo esc_html( number_format( $quest->coin_reward ) ); ?></span>
					</div>
				</div>
				<div class="xen-hub-card-actions">
					<button class="xen-btn xen-btn-legendary xen-accept-quest"
							data-id="<?php echo esc_attr( $quest->id ); ?>">
						<?php esc_html_e( 'Accept Destiny', 'xen-levelup' ); ?>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $active_legendary ) : ?>
		<div class="xen-hub-section-label xen-legendary-label"><?php esc_html_e( '⚔️ Active — Prove Your Worth', 'xen-levelup' ); ?></div>
		<div class="xen-quest-list">
		<?php foreach ( $active_legendary as $quest ) : ?>
			<div class="xen-hub-card xen-hub-card-legendary xen-hub-card-active"
				 id="xen-quest-<?php echo esc_attr( $quest->id ); ?>">
				<div class="xen-legendary-glow"></div>
				<div class="xen-hub-card-left">
					<span class="xen-hub-cat-icon">⚔️</span>
				</div>
				<div class="xen-hub-card-body">
					<div class="xen-hub-card-title"><?php echo esc_html( $quest->title ); ?></div>
					<?php if ( $quest->description ) : ?>
					<div class="xen-hub-card-desc"><?php echo esc_html( $quest->description ); ?></div>
					<?php endif; ?>
					<div class="xen-hub-card-meta">
						<span class="xen-diff-badge xen-diff-legendary"><?php esc_html_e( 'Legendary', 'xen-levelup' ); ?></span>
						<span class="xen-reward-tag xen-reward-legendary">⭐ <?php echo esc_html( number_format( $quest->xp_reward ) ); ?> XP</span>
						<span class="xen-reward-tag xen-reward-legendary">🪙 <?php echo esc_html( number_format( $quest->coin_reward ) ); ?></span>
					</div>
				</div>
				<div class="xen-hub-card-actions">
					<button class="xen-btn xen-btn-legendary xen-complete-quest"
							data-id="<?php echo esc_attr( $quest->id ); ?>">
						<?php esc_html_e( 'Claim Victory', 'xen-levelup' ); ?>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! $pending_legendary && ! $active_legendary ) : ?>
		<div class="xen-empty-state">
			<div class="xen-empty-icon">👑</div>
			<p><?php esc_html_e( 'You have not been chosen for a Legendary Quest yet. Keep leveling up — the System is watching.', 'xen-levelup' ); ?></p>
		</div>
		<?php endif; ?>
	</div>

	<!-- ── History Panel ─────────────────────────────────────────── -->
	<div class="xen-hub-panel xen-hub-panel-hidden" id="xen-panel-history" role="tabpanel">
		<?php
		global $wpdb;
		$t = $wpdb->prefix . 'xen_user_quests';
		$history = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$t} WHERE user_id = %d AND status = 'completed' ORDER BY completed_at DESC LIMIT 30",
			(int) $user_id
		) );
		?>
		<?php if ( $history ) : ?>
		<div class="xen-quest-list">
		<?php foreach ( $history as $quest ) : ?>
			<div class="xen-hub-card xen-hub-card-history xen-quest-done">
				<div class="xen-hub-card-left">
					<span class="xen-hub-cat-icon">✓</span>
				</div>
				<div class="xen-hub-card-body">
					<div class="xen-hub-card-title"><?php echo esc_html( $quest->title ); ?></div>
					<div class="xen-hub-card-meta">
						<?php echo wp_kses_post( xen_hub_diff_badge( $quest->difficulty ) ); ?>
						<span class="xen-type-tag"><?php echo esc_html( ucfirst( $quest->quest_type ) ); ?></span>
						<span class="xen-reward-tag">⭐ <?php echo esc_html( number_format( $quest->xp_reward ) ); ?> XP</span>
						<span class="xen-history-date"><?php echo esc_html( $quest->completed_at ? date_i18n( 'M j, Y', strtotime( $quest->completed_at ) ) : '' ); ?></span>
					</div>
				</div>
				<div class="xen-hub-card-actions">
					<span class="xen-done-badge">✓ <?php esc_html_e( 'Completed', 'xen-levelup' ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="xen-empty-state">
			<div class="xen-empty-icon">📜</div>
			<p><?php esc_html_e( 'No completed quests yet. Your legend begins now.', 'xen-levelup' ); ?></p>
		</div>
		<?php endif; ?>
	</div>

</div><!-- .xen-quest-hub -->
