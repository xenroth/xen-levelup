<?php
/**
 * Public view: Character profile — game-style character sheet.
 * Loaded by [gamified_profile]
 *
 * Variables: $user_data, $atts
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$profile     = $user_data['profile']      ?? null;
$level       = $user_data['level']        ?? 1;
$xp          = $user_data['xp']           ?? 0;
$xp_next     = $user_data['xp_next_level']?? 100;
$progress    = $user_data['xp_progress']  ?? 0;
$coins       = $user_data['coins']        ?? 0;
$rank_title  = $user_data['rank_title']   ?? '';
$uid         = $user_data['user_id']      ?? 0;
$name_color  = $user_data['name_color']   ?? null;

$user        = get_userdata( $uid );
$stats       = xen_levelup()->stats->get_all_stats( $uid );
$rpg         = $stats['rpg']        ?? array();
$life_trees  = $stats['life_trees'] ?? array();
$icons       = $stats['icons']      ?? array();
$achs        = xen_levelup()->achievements->get_user_achievements( $uid );
$inventory   = xen_levelup()->shop->get_inventory( $uid );
$rank_pos    = xen_levelup()->rankings->get_user_rank( $uid );
$bio         = get_user_meta( $uid, 'xen_bio', true );
$is_own      = is_user_logged_in() && (int) get_current_user_id() === (int) $uid;
$nonce       = $is_own ? wp_create_nonce( 'xen_nonce' ) : '';

// Equipped items
$equipped = array_filter( $inventory, fn( $i ) => $i->is_equipped );

// Name color CSS
$name_style = '';
if ( $name_color ) {
	$nc = is_string( $name_color ) ? json_decode( $name_color, true ) : (array) $name_color;
	if ( ! empty( $nc['color'] ) && 'rainbow' !== $nc['color'] ) {
		$name_style = 'color:' . esc_attr( $nc['color'] ) . ';';
	}
}

// RPG stat icons
$rpg_icons = array(
	'strength'     => '⚔️',
	'intelligence' => '🧠',
	'discipline'   => '🎯',
	'endurance'    => '🛡️',
	'wisdom'       => '📚',
	'charisma'     => '✨',
	'focus'        => '🔮',
	'vitality'     => '❤️',
);
?>
<div class="xen-wrap xen-profile xen-profile-v2" id="xen-profile-wrap" data-uid="<?php echo esc_attr( $uid ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<!-- ── Character Status Window ─────────────────────────────────── -->
	<div class="xen-profile-status-window">

		<!-- Left Panel: Identity -->
		<div class="xen-profile-identity">
			<div class="xen-profile-avatar-wrap <?php echo $profile && $profile->profile_frame ? 'xen-frame-' . esc_attr( sanitize_key( $profile->profile_frame ) ) : ''; ?>">
				<?php echo get_avatar( $uid, 120, '', '', array( 'class' => 'xen-profile-avatar' ) ); ?>
				<div class="xen-level-orb"><?php echo esc_html( $level ); ?></div>
			</div>

			<h2 class="xen-profile-name <?php echo ! empty( $nc['animated'] ) ? 'xen-name-rainbow' : ''; ?>"
				style="<?php echo esc_attr( $name_style ); ?>">
				<?php echo esc_html( $user ? $user->display_name : '' ); ?>
			</h2>

			<?php if ( $profile && $profile->current_title ) : ?>
			<div class="xen-profile-hunter-title">「<?php echo esc_html( $profile->current_title ); ?>」</div>
			<?php endif; ?>

			<div class="xen-rank-badge xen-rank-<?php echo esc_attr( sanitize_key( $rank_title ) ); ?> xen-rank-glow">
				<?php echo esc_html( $rank_title ); ?>
			</div>

			<?php if ( $rank_pos ) : ?>
			<div class="xen-profile-global-rank"># <?php echo esc_html( $rank_pos ); ?> <?php esc_html_e( 'Global', 'xen-levelup' ); ?></div>
			<?php endif; ?>

			<?php if ( $bio ) : ?>
			<p class="xen-profile-bio" id="xen-bio-display"><?php echo esc_html( $bio ); ?></p>
			<?php else : ?>
			<p class="xen-profile-bio xen-bio-empty" id="xen-bio-display"><?php echo $is_own ? esc_html__( 'No bio yet. Click Edit to add one.', 'xen-levelup' ) : ''; ?></p>
			<?php endif; ?>

			<!-- Stats Counters -->
			<div class="xen-profile-counters">
				<div class="xen-counter-item">
					<span class="xen-counter-val"><?php echo esc_html( number_format( $coins ) ); ?></span>
					<span class="xen-counter-lbl">🪙 <?php esc_html_e( 'Coins', 'xen-levelup' ); ?></span>
				</div>
				<div class="xen-counter-item">
					<span class="xen-counter-val"><?php echo esc_html( (int) ( $profile->total_quests ?? 0 ) ); ?></span>
					<span class="xen-counter-lbl">⚔️ <?php esc_html_e( 'Quests', 'xen-levelup' ); ?></span>
				</div>
				<div class="xen-counter-item">
					<span class="xen-counter-val"><?php echo esc_html( (int) ( $profile->total_tasks ?? 0 ) ); ?></span>
					<span class="xen-counter-lbl">✓ <?php esc_html_e( 'Tasks', 'xen-levelup' ); ?></span>
				</div>
			</div>

			<?php if ( $is_own ) : ?>
			<button class="xen-btn xen-btn-outline xen-profile-edit-btn" id="xen-profile-edit-toggle">
				✏️ <?php esc_html_e( 'Edit Profile', 'xen-levelup' ); ?>
			</button>
			<?php endif; ?>
		</div>

		<!-- Right Panel: Stats -->
		<div class="xen-profile-stats-panel">

			<!-- XP Progress -->
			<div class="xen-profile-xp-block">
				<div class="xen-xp-label-row">
					<span class="xen-xp-level-label"><?php printf( esc_html__( 'Level %d', 'xen-levelup' ), $level ); ?></span>
					<span class="xen-xp-numbers"><?php echo esc_html( number_format( $xp ) . ' / ' . number_format( $xp_next ) . ' XP' ); ?></span>
				</div>
				<div class="xen-xp-bar" role="progressbar" aria-valuenow="<?php echo esc_attr( $progress ); ?>" aria-valuemin="0" aria-valuemax="100">
					<div class="xen-xp-fill" style="width:<?php echo esc_attr( $progress ); ?>%"></div>
					<span class="xen-xp-pct"><?php echo esc_html( $progress ); ?>%</span>
				</div>
			</div>

			<!-- RPG Stats -->
			<?php if ( $rpg ) : ?>
			<div class="xen-profile-rpg-stats">
				<div class="xen-stats-grid-title"><?php esc_html_e( 'Combat Stats', 'xen-levelup' ); ?></div>
				<div class="xen-rpg-stats-grid">
				<?php foreach ( $rpg as $key => $val ) :
					$max   = max( 100, (int) $val );
					$pct   = min( 100, round( $val / $max * 100 ) );
					$icon  = $rpg_icons[ $key ] ?? '⚡';
					$label = ucfirst( $key );
				?>
					<div class="xen-rpg-stat-row">
						<span class="xen-rpg-icon"><?php echo esc_html( $icon ); ?></span>
						<span class="xen-rpg-label"><?php echo esc_html( $label ); ?></span>
						<div class="xen-rpg-bar">
							<div class="xen-rpg-fill xen-rpg-<?php echo esc_attr( $key ); ?>" style="width:<?php echo esc_attr( min(100, round( $val / 200 * 100 )) ); ?>%"></div>
						</div>
						<span class="xen-rpg-val"><?php echo esc_html( $val ); ?></span>
					</div>
				<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

		</div>
	</div>

	<!-- ── Edit Profile Panel (own profile only) ──────────────────── -->
	<?php if ( $is_own ) : ?>
	<div class="xen-profile-edit-panel" id="xen-profile-edit-panel" style="display:none;">
		<h3 class="xen-section-title">✏️ <?php esc_html_e( 'Edit Profile', 'xen-levelup' ); ?></h3>
		<div class="xen-edit-form">
			<div class="xen-form-row">
				<label for="xen-edit-display-name"><?php esc_html_e( 'Display Name', 'xen-levelup' ); ?></label>
				<input type="text" id="xen-edit-display-name" class="xen-input"
					   value="<?php echo esc_attr( $user ? $user->display_name : '' ); ?>"
					   maxlength="60" />
			</div>
			<div class="xen-form-row">
				<label for="xen-edit-bio"><?php esc_html_e( 'Bio', 'xen-levelup' ); ?></label>
				<textarea id="xen-edit-bio" class="xen-textarea" rows="3" maxlength="300"><?php echo esc_textarea( $bio ); ?></textarea>
			</div>
			<div class="xen-form-row">
				<label for="xen-edit-title"><?php esc_html_e( 'Hunter Title', 'xen-levelup' ); ?></label>
				<input type="text" id="xen-edit-title" class="xen-input"
					   value="<?php echo esc_attr( $profile ? $profile->current_title : '' ); ?>"
					   maxlength="80"
					   placeholder="<?php esc_attr_e( 'Your title (e.g. Shadow Monarch)', 'xen-levelup' ); ?>" />
			</div>
			<div class="xen-form-row xen-avatar-upload-row">
				<label><?php esc_html_e( 'Profile Photo', 'xen-levelup' ); ?></label>
				<div class="xen-avatar-preview-wrap">
					<?php echo get_avatar( $uid, 80, '', '', array( 'class' => 'xen-avatar-preview', 'id' => 'xen-avatar-preview-img' ) ); ?>
				</div>
				<input type="file" id="xen-avatar-file" accept="image/jpeg,image/png,image/gif,image/webp" class="xen-input-file">
				<button type="button" class="xen-btn xen-btn-outline xen-btn-sm" id="xen-upload-avatar-btn">
					📷 <?php esc_html_e( 'Upload Photo', 'xen-levelup' ); ?>
				</button>
				<span class="xen-avatar-upload-status" id="xen-avatar-upload-status"></span>
			</div>
			<div class="xen-form-actions">
				<button class="xen-btn xen-btn-primary" id="xen-profile-save-btn">
					<?php esc_html_e( 'Save Changes', 'xen-levelup' ); ?>
				</button>
				<button class="xen-btn xen-btn-ghost" id="xen-profile-cancel-btn">
					<?php esc_html_e( 'Cancel', 'xen-levelup' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- ── Life Trees ─────────────────────────────────────────────── -->
	<?php if ( $life_trees ) : ?>
	<div class="xen-section">
		<h3 class="xen-section-title">🌿 <?php esc_html_e( 'Life Development Trees', 'xen-levelup' ); ?></h3>
		<div class="xen-life-trees-grid">
		<?php foreach ( $life_trees as $key => $value ) :
			$icon  = $icons[ $key ] ?? '🌿';
			$label = xen_levelup()->stats->life_tree_label( $key );
			$pct   = min( 100, round( $value / 200 * 100 ) );
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

	<!-- ── Equipped Items ─────────────────────────────────────────── -->
	<?php if ( $equipped ) : ?>
	<div class="xen-section">
		<h3 class="xen-section-title">🎒 <?php esc_html_e( 'Equipment', 'xen-levelup' ); ?></h3>
		<div class="xen-equipment-grid">
		<?php foreach ( $equipped as $item ) : ?>
			<div class="xen-equipment-slot" title="<?php echo esc_attr( $item->description ); ?>">
				<div class="xen-equip-icon"><?php echo esc_html( $item->icon ?? '🔸' ); ?></div>
				<div class="xen-equip-name"><?php echo esc_html( $item->name ); ?></div>
				<div class="xen-equip-type"><?php echo esc_html( $item->item_type ); ?></div>
			</div>
		<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<!-- ── Achievements ───────────────────────────────────────────── -->
	<?php if ( $achs ) : ?>
	<div class="xen-section">
		<h3 class="xen-section-title">🏆 <?php esc_html_e( 'Achievements', 'xen-levelup' ); ?></h3>
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
