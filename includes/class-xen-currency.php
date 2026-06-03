<?php
/**
 * Currency system – earn, spend, balance, transaction log.
 *
 * @package XEN_LevelUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Xen_Currency
 */
class Xen_Currency extends Xen_Database {

	public function __construct() {
		parent::__construct();
	}

	// ─── Balance ──────────────────────────────────────────────────────────

	/**
	 * Get the current coin balance for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @return int
	 */
	public function get_balance( $user_id ) {
		$profile = xen_levelup()->user->get_profile( (int) $user_id );
		return $profile ? max( 0, (int) $profile->coins ) : 0;
	}

	// ─── Add ─────────────────────────────────────────────────────────────

	/**
	 * Add coins to a user's balance and log the transaction.
	 *
	 * @param int    $user_id     WP user ID.
	 * @param int    $amount      Positive coin amount.
	 * @param string $type        Transaction type (quest, task, achievement, level_up, …).
	 * @param string $description Human-readable description.
	 * @param int    $ref_id      Optional reference record ID.
	 * @param string $ref_type    Optional reference type.
	 * @return int New balance.
	 */
	public function add( $user_id, $amount, $type = 'general', $description = '', $ref_id = 0, $ref_type = '' ) {
		$user_id = (int) $user_id;
		$amount  = max( 0, (int) $amount );

		if ( ! $amount ) {
			return $this->get_balance( $user_id );
		}

		$old_balance = $this->get_balance( $user_id );
		$new_balance = $old_balance + $amount;

		xen_levelup()->user->update_profile( $user_id, array( 'coins' => $new_balance ) );
		$this->log_transaction( $user_id, $amount, $type, $description, $ref_id, $ref_type, $new_balance );

		return $new_balance;
	}

	// ─── Spend ────────────────────────────────────────────────────────────

	/**
	 * Deduct coins from a user's balance.
	 *
	 * @param int    $user_id     WP user ID.
	 * @param int    $amount      Positive coin amount to deduct.
	 * @param string $type        Transaction type (purchase, …).
	 * @param string $description Human-readable description.
	 * @param int    $ref_id      Optional reference record ID.
	 * @param string $ref_type    Optional reference type.
	 * @return array { success: bool, balance: int, message: string }
	 */
	public function spend( $user_id, $amount, $type = 'purchase', $description = '', $ref_id = 0, $ref_type = '' ) {
		$user_id = (int) $user_id;
		$amount  = max( 0, (int) $amount );

		$old_balance = $this->get_balance( $user_id );

		if ( $old_balance < $amount ) {
			return array(
				'success' => false,
				'balance' => $old_balance,
				'message' => __( 'Insufficient coins.', 'xen-levelup' ),
			);
		}

		$new_balance = $old_balance - $amount;
		xen_levelup()->user->update_profile( $user_id, array( 'coins' => $new_balance ) );
		$this->log_transaction( $user_id, -$amount, $type, $description, $ref_id, $ref_type, $new_balance );

		return array(
			'success' => true,
			'balance' => $new_balance,
			'message' => __( 'Purchase successful.', 'xen-levelup' ),
		);
	}

	// ─── Transaction Log ──────────────────────────────────────────────────

	/**
	 * Insert a currency transaction record.
	 *
	 * @param int    $user_id      WP user ID.
	 * @param int    $amount       Signed amount (+/-).
	 * @param string $type         Transaction type.
	 * @param string $description  Description.
	 * @param int    $ref_id       Reference ID.
	 * @param string $ref_type     Reference type.
	 * @param int    $balance_after New balance.
	 */
	private function log_transaction( $user_id, $amount, $type, $description, $ref_id, $ref_type, $balance_after ) {
		$this->insert(
			'currency_transactions',
			array(
				'user_id'        => (int) $user_id,
				'amount'         => (int) $amount,
				'type'           => sanitize_key( $type ),
				'description'    => sanitize_text_field( $description ),
				'reference_id'   => $ref_id   ? (int) $ref_id   : null,
				'reference_type' => $ref_type ? sanitize_key( $ref_type ) : null,
				'balance_after'  => (int) $balance_after,
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%d' )
		);
	}

	/**
	 * Get recent transaction history for a user.
	 *
	 * @param int $user_id WP user ID.
	 * @param int $limit   Max rows to return.
	 * @return array
	 */
	public function get_transactions( $user_id, $limit = 30 ) {
		$t = $this->table( 'currency_transactions' );
		return $this->query(
			"SELECT * FROM {$t} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
			array( (int) $user_id, (int) $limit )
		);
	}

	// ─── Currency Identity ────────────────────────────────────────────────

	// ─── Transfers ────────────────────────────────────────────────────────

	/**
	 * Transfer coins from one user to another atomically.
	 *
	 * @param int    $from_id   Sender user ID.
	 * @param int    $to_id     Receiver user ID.
	 * @param int    $amount    Positive coin amount.
	 * @param string $note      Optional message.
	 * @return array|WP_Error
	 */
	public function transfer( $from_id, $to_id, $amount, $note = '' ) {
		$from_id = (int) $from_id;
		$to_id   = (int) $to_id;
		$amount  = (int) $amount;

		if ( $amount <= 0 ) {
			return new WP_Error( 'invalid_amount', __( 'Transfer amount must be positive.', 'xen-levelup' ) );
		}

		$sender_balance = $this->get_balance( $from_id );
		if ( $sender_balance < $amount ) {
			return new WP_Error( 'insufficient_funds', __( 'Insufficient coins.', 'xen-levelup' ) );
		}

		// Deduct from sender
		$new_sender     = $sender_balance - $amount;
		$receiver_user  = get_userdata( $to_id );
		$receiver_name  = $receiver_user ? $receiver_user->display_name : '#' . $to_id;
		xen_levelup()->user->update_profile( $from_id, array( 'coins' => $new_sender ) );
		$this->log_transaction( $from_id, -$amount, 'transfer_out',
			sprintf( __( 'Sent to %s%s', 'xen-levelup' ), $receiver_name, $note ? ': ' . sanitize_text_field( $note ) : '' ),
			$to_id, 'user', $new_sender
		);

		// Add to receiver
		$sender_user  = get_userdata( $from_id );
		$sender_name  = $sender_user ? $sender_user->display_name : '#' . $from_id;
		$receiver_balance = $this->get_balance( $to_id );
		$new_receiver     = $receiver_balance + $amount;
		xen_levelup()->user->update_profile( $to_id, array( 'coins' => $new_receiver ) );
		$this->log_transaction( $to_id, $amount, 'transfer_in',
			sprintf( __( 'Received from %s%s', 'xen-levelup' ), $sender_name, $note ? ': ' . sanitize_text_field( $note ) : '' ),
			$from_id, 'user', $new_receiver
		);

		// Log transfer record
		$this->insert(
			'currency_transfers',
			array(
				'sender_id'   => $from_id,
				'receiver_id' => $to_id,
				'amount'      => $amount,
				'note'        => sanitize_text_field( $note ),
				'type'        => 'transfer',
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		xen_levelup()->user->flush_profile_cache( $from_id );
		xen_levelup()->user->flush_profile_cache( $to_id );

		return array(
			'success'         => true,
			'sender_balance'  => $new_sender,
			'amount'          => $amount,
		);
	}

	/**
	 * Admin reward: send coins to a user without deducting from anyone.
	 *
	 * @param int    $to_id  Receiver user ID.
	 * @param int    $amount Positive coin amount.
	 * @param string $note   Optional message.
	 * @return int New balance.
	 */
	public function admin_send( $to_id, $amount, $note = '' ) {
		$to_id  = (int) $to_id;
		$amount = max( 0, (int) $amount );

		$new_balance = $this->add( $to_id, $amount, 'admin_reward', $note );

		$this->insert(
			'currency_transfers',
			array(
				'sender_id'   => 0,
				'receiver_id' => $to_id,
				'amount'      => $amount,
				'note'        => sanitize_text_field( $note ),
				'type'        => 'admin_reward',
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		return $new_balance;
	}

	/**
	 * Get transfer history for a user (sent and received).
	 *
	 * @param int $user_id WP user ID.
	 * @param int $limit   Max rows.
	 * @return array
	 */
	public function get_transfer_history( $user_id, $limit = 20 ) {
		$t = $this->table( 'currency_transfers' );
		return $this->query(
			"SELECT * FROM {$t} WHERE sender_id = %d OR receiver_id = %d ORDER BY created_at DESC LIMIT %d",
			array( (int) $user_id, (int) $user_id, (int) $limit )
		);
	}

	// ─── Currency Identity ────────────────────────────────────────────────

	/**
	 * The configured currency name (admin-customisable).
	 *
	 * @return string
	 */
	public static function name() {
		return get_option( 'xen_levelup_currency_name', 'Coins' );
	}

	/**
	 * The configured currency symbol (admin-customisable).
	 *
	 * @return string
	 */
	public static function symbol() {
		return get_option( 'xen_levelup_currency_symbol', '🪙' );
	}
}
