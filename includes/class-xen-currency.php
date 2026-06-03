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
}
