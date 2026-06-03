<?php
/**
 * Public view: Currency Wallet — balance, transfers, transaction history.
 * Loaded by [gamified_wallet]
 *
 * Variables: $user_id, $balance, $transactions, $transfers, $users
 *
 * @package XEN_LevelUp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$nonce         = wp_create_nonce( 'xen_nonce' );
$currency_name = Xen_Currency::name();
$currency_sym  = Xen_Currency::symbol();
?>
<div class="xen-wrap xen-wallet" id="xen-wallet-wrap" data-nonce="<?php echo esc_attr( $nonce ); ?>">

	<!-- ── Balance Card ─────────────────────────────────────────── -->
	<div class="xen-wallet-hero">
		<div class="xen-wallet-hero-icon"><?php echo esc_html( $currency_sym ); ?></div>
		<div class="xen-wallet-hero-body">
			<div class="xen-wallet-label"><?php esc_html_e( 'Your Balance', 'xen-levelup' ); ?></div>
			<div class="xen-wallet-balance" id="xen-wallet-balance">
				<?php echo esc_html( number_format( $balance ) ); ?>
				<span class="xen-wallet-currency"><?php echo esc_html( $currency_name ); ?></span>
			</div>
		</div>
	</div>

	<!-- ── Tabs ─────────────────────────────────────────────────── -->
	<div class="xen-hub-tabs xen-wallet-tabs" role="tablist">
		<button class="xen-hub-tab xen-hub-tab-active" data-tab="send" role="tab">
			📤 <?php esc_html_e( 'Send', 'xen-levelup' ); ?>
		</button>
		<button class="xen-hub-tab" data-tab="transfers" role="tab">
			🔄 <?php esc_html_e( 'Transfers', 'xen-levelup' ); ?>
		</button>
		<button class="xen-hub-tab" data-tab="history" role="tab">
			📋 <?php esc_html_e( 'History', 'xen-levelup' ); ?>
		</button>
	</div>

	<!-- ── Send Panel ───────────────────────────────────────────── -->
	<div class="xen-hub-panel" id="xen-panel-send">
		<div class="xen-wallet-send-form">
			<h3 class="xen-section-title"><?php esc_html_e( 'Send Coins to Another Hunter', 'xen-levelup' ); ?></h3>
			<div class="xen-form-row">
				<label for="xen-send-to"><?php esc_html_e( 'Recipient', 'xen-levelup' ); ?></label>
				<select id="xen-send-to" class="xen-input">
					<option value=""><?php esc_html_e( '— Select a hunter —', 'xen-levelup' ); ?></option>
					<?php foreach ( (array) $users as $u ) : ?>
					<option value="<?php echo esc_attr( $u->ID ); ?>"><?php echo esc_html( $u->display_name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="xen-form-row">
				<label for="xen-send-amount"><?php esc_html_e( 'Amount', 'xen-levelup' ); ?></label>
				<input type="number" id="xen-send-amount" class="xen-input" min="1" max="<?php echo esc_attr( $balance ); ?>"
					   placeholder="<?php esc_attr_e( 'How many coins?', 'xen-levelup' ); ?>" />
				<span class="xen-form-hint"><?php printf( esc_html__( 'Available: %s', 'xen-levelup' ), esc_html( number_format( $balance ) ) ); ?></span>
			</div>
			<div class="xen-form-row">
				<label for="xen-send-note"><?php esc_html_e( 'Note (optional)', 'xen-levelup' ); ?></label>
				<input type="text" id="xen-send-note" class="xen-input" maxlength="120"
					   placeholder="<?php esc_attr_e( 'Add a message…', 'xen-levelup' ); ?>" />
			</div>
			<div class="xen-form-actions">
				<button class="xen-btn xen-btn-primary" id="xen-send-btn">
					📤 <?php esc_html_e( 'Send Coins', 'xen-levelup' ); ?>
				</button>
			</div>
			<div id="xen-send-result" class="xen-send-result" style="display:none;"></div>
		</div>
	</div>

	<!-- ── Transfers Panel ──────────────────────────────────────── -->
	<div class="xen-hub-panel xen-hub-panel-hidden" id="xen-panel-transfers">
		<?php if ( $transfers ) : ?>
		<div class="xen-wallet-list">
			<?php foreach ( (array) $transfers as $t ) :
				$is_sent    = (int) $t->sender_id === (int) $user_id;
				$other_id   = $is_sent ? $t->receiver_id : $t->sender_id;
				$other_user = get_userdata( $other_id );
				$other_name = $other_user ? $other_user->display_name : ( $other_id ? '#' . $other_id : esc_html__( 'System', 'xen-levelup' ) );
				$type_label = 'admin_reward' === $t->type ? __( 'Admin Reward', 'xen-levelup' ) : ( $is_sent ? __( 'Sent to', 'xen-levelup' ) : __( 'Received from', 'xen-levelup' ) );
				$amount_str = $is_sent && 'admin_reward' !== $t->type ? '- ' . number_format( $t->amount ) : '+ ' . number_format( $t->amount );
				$css_class  = $is_sent && 'admin_reward' !== $t->type ? 'xen-tx-out' : 'xen-tx-in';
			?>
			<div class="xen-wallet-row <?php echo esc_attr( $css_class ); ?>">
				<div class="xen-wallet-row-icon"><?php echo $is_sent && 'admin_reward' !== $t->type ? '📤' : '📥'; ?></div>
				<div class="xen-wallet-row-body">
					<div class="xen-wallet-row-title">
						<?php echo esc_html( $type_label ); ?> <strong><?php echo esc_html( $other_name ); ?></strong>
					</div>
					<?php if ( $t->note ) : ?>
					<div class="xen-wallet-row-note"><?php echo esc_html( $t->note ); ?></div>
					<?php endif; ?>
					<div class="xen-wallet-row-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $t->created_at ) ) ); ?></div>
				</div>
				<div class="xen-wallet-row-amount <?php echo esc_attr( $css_class ); ?>"><?php echo esc_html( $amount_str ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="xen-empty-state">
			<div class="xen-empty-icon">🔄</div>
			<p><?php esc_html_e( 'No transfer history yet.', 'xen-levelup' ); ?></p>
		</div>
		<?php endif; ?>
	</div>

	<!-- ── Transaction History Panel ───────────────────────────── -->
	<div class="xen-hub-panel xen-hub-panel-hidden" id="xen-panel-history">
		<?php if ( $transactions ) : ?>
		<div class="xen-wallet-list">
			<?php foreach ( (array) $transactions as $tx ) :
				$is_positive = $tx->amount >= 0;
				$css_class   = $is_positive ? 'xen-tx-in' : 'xen-tx-out';
				$amount_str  = ( $is_positive ? '+' : '' ) . number_format( $tx->amount );
			?>
			<div class="xen-wallet-row <?php echo esc_attr( $css_class ); ?>">
				<div class="xen-wallet-row-icon"><?php echo $is_positive ? '⬆️' : '⬇️'; ?></div>
				<div class="xen-wallet-row-body">
					<div class="xen-wallet-row-title"><?php echo esc_html( $tx->description ?: ucfirst( str_replace( '_', ' ', $tx->type ) ) ); ?></div>
					<div class="xen-wallet-row-date">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $tx->created_at ) ) ); ?>
						&nbsp;·&nbsp; <?php printf( esc_html__( 'Balance: %s', 'xen-levelup' ), esc_html( number_format( $tx->balance_after ) ) ); ?>
					</div>
				</div>
				<div class="xen-wallet-row-amount <?php echo esc_attr( $css_class ); ?>"><?php echo esc_html( $amount_str ); ?></div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="xen-empty-state">
			<div class="xen-empty-icon">📋</div>
			<p><?php esc_html_e( 'No transaction history yet.', 'xen-levelup' ); ?></p>
		</div>
		<?php endif; ?>
	</div>

</div><!-- .xen-wallet -->
