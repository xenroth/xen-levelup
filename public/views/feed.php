<?php
/**
 * Public view: Activity Feed.
 * Loaded by [gamified_feed] shortcode via Xen_Social::shortcode().
 *
 * Variables: $feed, $friends, $pending_requests, $atts, $user_id
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nonce        = wp_create_nonce( 'xen_nonce' );
$friend_ids   = wp_list_pluck( $friends, 'user_id' );
$pending_uids = wp_list_pluck( $pending_requests, 'requester_id' );
?>
<div class="xen-wrap xen-feed-wrap" id="xen-feed"
	data-nonce="<?php echo esc_attr( $nonce ); ?>"
	data-uid="<?php echo esc_attr( $user_id ); ?>"
	data-mode="<?php echo esc_attr( $atts['mode'] ); ?>">

	<!-- ── Post Box ───────────────────────────────────────────────────── -->
	<div class="xen-feed-post-box xen-card">
		<textarea id="xen-feed-post-text" class="xen-textarea" rows="2"
			maxlength="500"
			placeholder="<?php esc_attr_e( 'Share something with the community…', 'xen-levelup' ); ?>"></textarea>
		<div class="xen-feed-post-actions">
			<button class="xen-btn xen-btn-primary" id="xen-feed-post-btn">
				📢 <?php esc_html_e( 'Post', 'xen-levelup' ); ?>
			</button>
		</div>
	</div>

	<!-- ── Feed Items ─────────────────────────────────────────────────── -->
	<div class="xen-feed-list" id="xen-feed-list">
	<?php if ( $feed ) : ?>
		<?php foreach ( $feed as $item ) :
			$meta        = (array) $item->meta_data;
			$time_diff   = human_time_diff( strtotime( $item->created_at ), current_time( 'timestamp' ) );
			$is_own      = (int) $item->user_id === (int) $user_id;
			$is_friend   = in_array( (int) $item->user_id, $friend_ids, true );
			$profile_url = get_author_posts_url( (int) $item->user_id );
		?>
		<div class="xen-feed-item xen-card" id="xen-feed-item-<?php echo esc_attr( $item->id ); ?>"
			 data-id="<?php echo esc_attr( $item->id ); ?>">

			<div class="xen-feed-header">
				<img class="xen-feed-avatar" src="<?php echo esc_url( $item->avatar_url ); ?>"
					 alt="<?php echo esc_attr( $item->display_name ); ?>" width="40" height="40">
				<div class="xen-feed-meta">
					<a href="<?php echo esc_url( $profile_url ); ?>" class="xen-feed-name">
						<?php echo esc_html( $item->display_name ); ?>
					</a>
					<span class="xen-feed-time"><?php printf( esc_html__( '%s ago', 'xen-levelup' ), $time_diff ); ?></span>
				</div>
				<?php if ( ! $is_own && ! $is_friend && ! in_array( (int) $item->user_id, $pending_uids, true ) ) : ?>
				<button class="xen-btn xen-btn-xs xen-add-friend-btn" data-uid="<?php echo esc_attr( $item->user_id ); ?>">
					➕ <?php esc_html_e( 'Add Friend', 'xen-levelup' ); ?>
				</button>
				<?php endif; ?>
			</div>

			<div class="xen-feed-content">
				<?php echo esc_html( $item->content ); ?>
			</div>

			<!-- Rewards snippet for game events -->
			<?php if ( ! empty( $meta['xp'] ) || ! empty( $meta['streak'] ) ) : ?>
			<div class="xen-feed-rewards">
				<?php if ( ! empty( $meta['streak'] ) ) : ?>
				<span class="xen-feed-streak">🔥 Day <?php echo esc_html( $meta['streak'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $meta['xp'] ) ) : ?>
				<span class="xen-feed-xp">+<?php echo esc_html( $meta['xp'] ); ?> XP</span>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<!-- Reactions -->
			<div class="xen-feed-footer">
				<button class="xen-like-btn <?php echo $item->liked_by_me ? 'xen-liked' : ''; ?>"
						data-id="<?php echo esc_attr( $item->id ); ?>">
					<?php echo $item->liked_by_me ? '❤️' : '🤍'; ?>
					<span class="xen-like-count"><?php echo esc_html( $item->like_count ); ?></span>
				</button>
				<button class="xen-comment-toggle-btn xen-btn-ghost xen-btn-xs"
						data-id="<?php echo esc_attr( $item->id ); ?>">
					💬 <span class="xen-comment-count"><?php echo esc_html( $item->comment_count ); ?></span>
				</button>
			</div>

			<!-- Comments (lazy-loaded) -->
			<div class="xen-comments-area" id="xen-comments-<?php echo esc_attr( $item->id ); ?>" style="display:none;">
				<div class="xen-comments-list"></div>
				<div class="xen-add-comment-row">
					<input type="text" class="xen-comment-input xen-input" maxlength="500"
						   placeholder="<?php esc_attr_e( 'Write a comment…', 'xen-levelup' ); ?>">
					<button class="xen-btn xen-btn-sm xen-post-comment-btn" data-id="<?php echo esc_attr( $item->id ); ?>">
						<?php esc_html_e( 'Send', 'xen-levelup' ); ?>
					</button>
				</div>
			</div>

		</div>
		<?php endforeach; ?>
	<?php else : ?>
		<p class="xen-empty" id="xen-feed-empty">
			<?php esc_html_e( 'No activity yet. Be the first to post!', 'xen-levelup' ); ?>
		</p>
	<?php endif; ?>
	</div>

	<!-- ── Load More ──────────────────────────────────────────────────── -->
	<?php if ( count( $feed ) >= (int) $atts['limit'] ) : ?>
	<div class="xen-feed-loadmore">
		<button class="xen-btn xen-btn-outline" id="xen-feed-load-more"
				data-offset="<?php echo esc_attr( count( $feed ) ); ?>">
			<?php esc_html_e( 'Load More', 'xen-levelup' ); ?>
		</button>
	</div>
	<?php endif; ?>

	<!-- ── Friend Requests ────────────────────────────────────────────── -->
	<?php if ( $pending_requests ) : ?>
	<div class="xen-feed-friend-requests xen-card">
		<h4><?php esc_html_e( 'Friend Requests', 'xen-levelup' ); ?></h4>
		<?php foreach ( $pending_requests as $req ) : ?>
		<div class="xen-friend-request-row" data-uid="<?php echo esc_attr( $req->requester_id ); ?>">
			<span><?php echo esc_html( $req->display_name ); ?></span>
			<button class="xen-btn xen-btn-sm xen-btn-primary xen-accept-friend-btn"
					data-uid="<?php echo esc_attr( $req->requester_id ); ?>">
				<?php esc_html_e( 'Accept', 'xen-levelup' ); ?>
			</button>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

</div><!-- .xen-feed-wrap -->
