<?php
/**
 * Public view: Onboarding wizard.
 * Place the shortcode [gamified_dashboard] on the onboarding page OR create a dedicated
 * page containing [gamified_dashboard] and redirect here.
 *
 * This file is rendered by Xen_Shortcodes::render_dashboard() if onboarding is not yet done.
 * It can also be included directly from that page template.
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$user_id    = get_current_user_id();
$step       = xen_levelup()->onboarding->get_current_step( $user_id );
$priorities = Xen_Onboarding::priority_categories();
$interests  = Xen_Onboarding::interest_slugs();
$traits     = Xen_Onboarding::personality_traits();
?>
<div class="xen-wrap xen-onboarding" id="xen-onboarding" data-step="<?php echo esc_attr( $step ); ?>">

	<div class="xen-onboarding-hero">
		<h2 class="xen-onboarding-title"><?php esc_html_e( '⚔️ Awakening Ceremony', 'xen-levelup' ); ?></h2>
		<p class="xen-onboarding-subtitle"><?php esc_html_e( 'Answer the System\'s questions to generate your Hunter profile.', 'xen-levelup' ); ?></p>
		<div class="xen-step-dots">
			<span class="xen-dot <?php echo $step >= 0 ? 'active' : ''; ?>"></span>
			<span class="xen-dot <?php echo $step >= 1 ? 'active' : ''; ?>"></span>
			<span class="xen-dot <?php echo $step >= 2 ? 'active' : ''; ?>"></span>
			<span class="xen-dot <?php echo $step >= 3 ? 'active' : ''; ?>"></span>
		</div>
	</div>

	<!-- Step 1: Personality -->
	<div class="xen-step" id="xen-step-1" <?php echo $step > 0 ? 'style="display:none"' : ''; ?>>
		<h3><?php esc_html_e( 'Step 1 — Personality Assessment', 'xen-levelup' ); ?></h3>
		<p><?php esc_html_e( 'Rate yourself on each spectrum (1 = left extreme, 10 = right extreme).', 'xen-levelup' ); ?></p>
		<form id="xen-step1-form" class="xen-onboarding-form" data-step="1">
			<div class="xen-trait-list">
				<div class="xen-trait-row">
					<span class="xen-trait-left"><?php esc_html_e( 'Introvert', 'xen-levelup' ); ?></span>
					<input type="range" name="introvert_extrovert" min="1" max="10" value="5" class="xen-range">
					<span class="xen-trait-right"><?php esc_html_e( 'Extrovert', 'xen-levelup' ); ?></span>
				</div>
				<div class="xen-trait-row">
					<span class="xen-trait-left"><?php esc_html_e( 'Analytical', 'xen-levelup' ); ?></span>
					<input type="range" name="analytical_creative" min="1" max="10" value="5" class="xen-range">
					<span class="xen-trait-right"><?php esc_html_e( 'Creative', 'xen-levelup' ); ?></span>
				</div>
				<div class="xen-trait-row">
					<span class="xen-trait-left"><?php esc_html_e( 'Competitive', 'xen-levelup' ); ?></span>
					<input type="range" name="competitive_cooperative" min="1" max="10" value="5" class="xen-range">
					<span class="xen-trait-right"><?php esc_html_e( 'Cooperative', 'xen-levelup' ); ?></span>
				</div>
				<div class="xen-trait-row">
					<span class="xen-trait-left"><?php esc_html_e( 'Passive', 'xen-levelup' ); ?></span>
					<input type="range" name="active_passive" min="1" max="10" value="5" class="xen-range">
					<span class="xen-trait-right"><?php esc_html_e( 'Active', 'xen-levelup' ); ?></span>
				</div>
				<div class="xen-trait-row">
					<span class="xen-trait-left"><?php esc_html_e( 'Flexible', 'xen-levelup' ); ?></span>
					<input type="range" name="structured_flexible" min="1" max="10" value="5" class="xen-range">
					<span class="xen-trait-right"><?php esc_html_e( 'Structured', 'xen-levelup' ); ?></span>
				</div>
			</div>
			<button type="submit" class="xen-btn xen-btn-primary xen-btn-lg"><?php esc_html_e( 'Next →', 'xen-levelup' ); ?></button>
		</form>
	</div>

	<!-- Step 2: Interests -->
	<div class="xen-step" id="xen-step-2" style="display:none">
		<h3><?php esc_html_e( 'Step 2 — Your Interests', 'xen-levelup' ); ?></h3>
		<p><?php esc_html_e( 'Rate your interest level (1 = not interested, 10 = obsessed).', 'xen-levelup' ); ?></p>
		<form id="xen-step2-form" class="xen-onboarding-form" data-step="2">
			<div class="xen-interest-grid">
			<?php
			$interest_labels = array(
				'physical_fitness' => '🏋️ ' . __( 'Physical Fitness', 'xen-levelup' ),
				'strength_training'=> '💪 ' . __( 'Strength Training', 'xen-levelup' ),
				'sports'           => '⚽ ' . __( 'Sports', 'xen-levelup' ),
				'reading'          => '📚 ' . __( 'Reading', 'xen-levelup' ),
				'learning'         => '🧠 ' . __( 'Learning', 'xen-levelup' ),
				'career_success'   => '💼 ' . __( 'Career Success', 'xen-levelup' ),
				'business'         => '💰 ' . __( 'Business', 'xen-levelup' ),
				'leadership'       => '👑 ' . __( 'Leadership', 'xen-levelup' ),
				'communication'    => '🗣 ' . __( 'Communication', 'xen-levelup' ),
				'productivity'     => '⚡ ' . __( 'Productivity', 'xen-levelup' ),
				'mental_health'    => '🧘 ' . __( 'Mental Health', 'xen-levelup' ),
				'spiritual_growth' => '🕊 ' . __( 'Spiritual Growth', 'xen-levelup' ),
				'longevity'        => '🛡 ' . __( 'Longevity', 'xen-levelup' ),
				'relationships'    => '❤️ ' . __( 'Relationships', 'xen-levelup' ),
				'creativity'       => '🎨 ' . __( 'Creativity', 'xen-levelup' ),
			);
			foreach ( $interest_labels as $slug => $label ) : ?>
			<div class="xen-interest-item">
				<label for="interest_<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></label>
				<input type="range" id="interest_<?php echo esc_attr( $slug ); ?>" name="<?php echo esc_attr( $slug ); ?>" min="1" max="10" value="5" class="xen-range">
				<span class="xen-range-val">5</span>
			</div>
			<?php endforeach; ?>
			</div>
			<div class="xen-step-nav">
				<button type="button" class="xen-btn xen-btn-ghost xen-prev-step"><?php esc_html_e( '← Back', 'xen-levelup' ); ?></button>
				<button type="submit" class="xen-btn xen-btn-primary xen-btn-lg"><?php esc_html_e( 'Next →', 'xen-levelup' ); ?></button>
			</div>
		</form>
	</div>

	<!-- Step 3: Priorities drag-drop -->
	<div class="xen-step" id="xen-step-3" style="display:none">
		<h3><?php esc_html_e( 'Step 3 — Life Priorities', 'xen-levelup' ); ?></h3>
		<p><?php esc_html_e( 'Drag to rank from most important (top) to least important (bottom).', 'xen-levelup' ); ?></p>
		<form id="xen-step3-form" class="xen-onboarding-form" data-step="3">
			<ul class="xen-priority-list" id="xen-priority-sortable">
			<?php
			$priority_labels = array(
				'physique'      => '🏋️ ' . __( 'Physique', 'xen-levelup' ),
				'intelligence'  => '🧠 ' . __( 'Intelligence', 'xen-levelup' ),
				'knowledge'     => '📚 ' . __( 'Knowledge', 'xen-levelup' ),
				'discipline'    => '⚡ ' . __( 'Discipline', 'xen-levelup' ),
				'wealth'        => '💰 ' . __( 'Wealth', 'xen-levelup' ),
				'communication' => '🗣 ' . __( 'Communication', 'xen-levelup' ),
				'leadership'    => '👑 ' . __( 'Leadership', 'xen-levelup' ),
				'relationships' => '❤️ ' . __( 'Relationships', 'xen-levelup' ),
				'spirituality'  => '🕊 ' . __( 'Spirituality', 'xen-levelup' ),
				'longevity'     => '🛡 ' . __( 'Longevity', 'xen-levelup' ),
			);
			foreach ( $priority_labels as $slug => $label ) : ?>
			<li class="xen-priority-item" data-value="<?php echo esc_attr( $slug ); ?>">
				<span class="xen-drag-handle">☰</span>
				<?php echo esc_html( $label ); ?>
			</li>
			<?php endforeach; ?>
			</ul>
			<div class="xen-step-nav">
				<button type="button" class="xen-btn xen-btn-ghost xen-prev-step"><?php esc_html_e( '← Back', 'xen-levelup' ); ?></button>
				<button type="submit" class="xen-btn xen-btn-primary xen-btn-lg"><?php esc_html_e( 'Finalize & Awaken →', 'xen-levelup' ); ?></button>
			</div>
		</form>
	</div>

	<!-- Step 4: Completion screen (shown after AJAX complete) -->
	<div class="xen-step" id="xen-step-complete" style="display:none">
		<div class="xen-awaken-complete">
			<div class="xen-awaken-anim" id="xen-awaken-anim">⚔️</div>
			<h2><?php esc_html_e( '[ YOU ARE AWAKE ]', 'xen-levelup' ); ?></h2>
			<p id="xen-awaken-msg"><?php esc_html_e( 'Your stats have been generated. Your journey begins now.', 'xen-levelup' ); ?></p>
			<a href="<?php echo esc_url( get_option( 'xen_levelup_dashboard_page' ) ? get_permalink( (int) get_option( 'xen_levelup_dashboard_page' ) ) : home_url() ); ?>" class="xen-btn xen-btn-primary xen-btn-lg">
				<?php esc_html_e( 'Enter the System ▶', 'xen-levelup' ); ?>
			</a>
		</div>
	</div>

</div><!-- .xen-onboarding -->
