<?php
/**
 * Public view: Habits tracker.
 * Loaded by [gamified_habits]
 *
 * Variables: $user_id, $habits, $atts
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="xen-wrap xen-habits-wrap" id="xen-habits">

	<div class="xen-habits-header">
		<h3 class="xen-section-title">🔥 <?php esc_html_e( 'Habit Tracker', 'xen-levelup' ); ?></h3>
	</div>

	<!-- Add Habit Form -->
	<form id="xen-add-habit-form" class="xen-form xen-collapse-form">
		<h4><?php esc_html_e( '+ New Habit', 'xen-levelup' ); ?></h4>
		<?php wp_nonce_field( 'xen_nonce', 'xen_habit_nonce' ); ?>
		<input type="text" name="title" placeholder="<?php esc_attr_e( 'Habit name…', 'xen-levelup' ); ?>" required maxlength="255">
		<select name="category">
			<?php foreach ( array(
				'physical'     => __( 'Physical', 'xen-levelup' ),
				'mental'       => __( 'Mental', 'xen-levelup' ),
				'reading'      => __( 'Reading', 'xen-levelup' ),
				'business'     => __( 'Business', 'xen-levelup' ),
				'productivity' => __( 'Productivity', 'xen-levelup' ),
				'spiritual'    => __( 'Spiritual', 'xen-levelup' ),
				'relationship' => __( 'Relationship', 'xen-levelup' ),
				'custom'       => __( 'Custom', 'xen-levelup' ),
			) as $slug => $label ) : ?>
			<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<button type="submit" class="xen-btn xen-btn-primary"><?php esc_html_e( 'Add Habit', 'xen-levelup' ); ?></button>
	</form>

	<!-- Habit List -->
	<div class="xen-habit-list" id="xen-habit-list">
	<?php if ( $habits ) : foreach ( $habits as $habit ) :
		$today = xen_levelup()->habits->logged_today( $habit->id, $user_id );
	?>
		<div class="xen-habit-card <?php echo $today ? 'xen-habit-logged' : ''; ?>"
			 id="xen-habit-<?php echo esc_attr( $habit->id ); ?>">
			<div class="xen-habit-streak">
				🔥 <strong><?php echo esc_html( $habit->current_streak ); ?></strong>
				<span class="xen-streak-label"><?php esc_html_e( 'day streak', 'xen-levelup' ); ?></span>
			</div>
			<div class="xen-habit-info">
				<span class="xen-habit-title"><?php echo esc_html( $habit->title ); ?></span>
				<span class="xen-habit-category"><?php echo esc_html( ucfirst( $habit->category ) ); ?></span>
			</div>
			<div class="xen-habit-actions">
				<?php if ( ! $today ) : ?>
				<button class="xen-btn xen-btn-log xen-log-habit"
						data-id="<?php echo esc_attr( $habit->id ); ?>"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'xen_nonce' ) ); ?>">
					<?php esc_html_e( '✓ Done Today', 'xen-levelup' ); ?>
				</button>
				<?php else : ?>
				<span class="xen-habit-done"><?php esc_html_e( '✓ Logged', 'xen-levelup' ); ?></span>
				<?php endif; ?>
				<button class="xen-btn xen-btn-ghost xen-deactivate-habit"
						data-id="<?php echo esc_attr( $habit->id ); ?>"
						aria-label="<?php esc_attr_e( 'Remove habit', 'xen-levelup' ); ?>">✕</button>
			</div>
		</div>
	<?php endforeach; else : ?>
		<p class="xen-empty" id="xen-habits-empty"><?php esc_html_e( 'No habits yet. Create your first habit above!', 'xen-levelup' ); ?></p>
	<?php endif; ?>
	</div>

</div><!-- .xen-habits-wrap -->
