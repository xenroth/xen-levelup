<?php
/**
 * Public view: Tasks.
 * Loaded by [gamified_tasks]
 *
 * Variables: $user_id, $tasks, $atts
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$remaining = xen_levelup()->tasks->get_remaining_today( $user_id );
?>
<div class="xen-wrap xen-tasks-wrap" id="xen-tasks">

	<div class="xen-tasks-header">
		<h3 class="xen-section-title">⚡ <?php esc_html_e( 'Tasks', 'xen-levelup' ); ?></h3>
		<span class="xen-tasks-remaining">
			<?php printf( esc_html__( '%d / %d remaining today', 'xen-levelup' ), $remaining, XEN_MAX_DAILY_TASKS ); ?>
		</span>
	</div>

	<!-- Add Task Form -->
	<?php if ( $remaining > 0 ) : ?>
	<form id="xen-add-task-form" class="xen-form">
		<?php wp_nonce_field( 'xen_nonce', 'xen_task_nonce' ); ?>
		<input type="text" id="xen-task-title" name="title" placeholder="<?php esc_attr_e( 'Task title…', 'xen-levelup' ); ?>" required maxlength="255">
		<select name="priority">
			<option value="medium"><?php esc_html_e( 'Medium', 'xen-levelup' ); ?></option>
			<option value="high"><?php esc_html_e( 'High', 'xen-levelup' ); ?></option>
			<option value="critical"><?php esc_html_e( 'Critical', 'xen-levelup' ); ?></option>
			<option value="low"><?php esc_html_e( 'Low', 'xen-levelup' ); ?></option>
		</select>
		<input type="date" name="due_date" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
		<button type="submit" class="xen-btn xen-btn-primary">+ <?php esc_html_e( 'Add Task', 'xen-levelup' ); ?></button>
	</form>
	<?php endif; ?>

	<!-- Task List -->
	<div class="xen-task-list" id="xen-task-list">
	<?php if ( $tasks ) : foreach ( $tasks as $task ) : ?>
		<div class="xen-task-item xen-priority-<?php echo esc_attr( $task->priority ); ?> <?php echo 'completed' === $task->status ? 'xen-done' : ''; ?>"
			 id="xen-task-<?php echo esc_attr( $task->id ); ?>">
			<div class="xen-task-check">
				<?php if ( 'completed' !== $task->status ) : ?>
				<button class="xen-check-btn xen-complete-task" data-id="<?php echo esc_attr( $task->id ); ?>" aria-label="<?php esc_attr_e( 'Complete', 'xen-levelup' ); ?>">○</button>
				<?php else : ?>
				<span class="xen-check-done">✓</span>
				<?php endif; ?>
			</div>
			<div class="xen-task-body">
				<span class="xen-task-title"><?php echo esc_html( $task->title ); ?></span>
				<?php if ( $task->due_date ) : ?>
				<span class="xen-task-due"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $task->due_date ) ) ); ?></span>
				<?php endif; ?>
			</div>
			<div class="xen-task-priority-badge xen-priority-<?php echo esc_attr( $task->priority ); ?>">
				<?php echo esc_html( ucfirst( $task->priority ) ); ?>
			</div>
			<?php if ( 'completed' !== $task->status ) : ?>
			<button class="xen-delete-btn xen-delete-task" data-id="<?php echo esc_attr( $task->id ); ?>" aria-label="<?php esc_attr_e( 'Delete', 'xen-levelup' ); ?>">✕</button>
			<?php endif; ?>
		</div>
	<?php endforeach; else : ?>
		<p class="xen-empty" id="xen-tasks-empty"><?php esc_html_e( 'No tasks yet. Add your first task above!', 'xen-levelup' ); ?></p>
	<?php endif; ?>
	</div>

</div><!-- .xen-tasks-wrap -->
