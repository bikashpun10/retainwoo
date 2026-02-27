<?php
/**
 * RetainWoo Generic Subscription Wrapper
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic fallback wrapper for unknown/future plugins.
 */
class RetainWoo_Subscription_Generic extends RetainWoo_Subscription_Base {

	/**
	 * Subscription ID.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Constructor.
	 *
	 * @param int $id Subscription ID.
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * Get subscription ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get customer ID.
	 *
	 * @return int
	 */
	public function get_customer_id() {
		return (int) get_post_meta( $this->id, '_customer_user', true );
	}

	/**
	 * Get customer email.
	 *
	 * @return string
	 */
	public function get_customer_email() {
		$user = get_user_by( 'id', $this->get_customer_id() );
		return $user ? $user->user_email : '';
	}

	/**
	 * Get subscription total.
	 *
	 * @return float
	 */
	public function get_total() {
		return (float) get_post_meta( $this->id, '_order_total', true );
	}

	/**
	 * Get billing period.
	 *
	 * @return string
	 */
	public function get_billing_period() {
		return 'month';
	}

	/**
	 * Get billing interval.
	 *
	 * @return int
	 */
	public function get_billing_interval() {
		return 1;
	}

	/**
	 * Get next payment date.
	 *
	 * @return string
	 */
	public function get_next_payment_date() {
		return gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
	}

	/**
	 * Pause subscription.
	 *
	 * @param int $months Number of months to pause.
	 * @return bool
	 */
	public function pause( $months ) {
		return false;
	}

	/**
	 * Skip next payment.
	 *
	 * @return bool
	 */
	public function skip_payment() {
		return false;
	}

	/**
	 * Apply discount to subscription.
	 *
	 * @param float  $amount Discount amount.
	 * @param string $type   Discount type.
	 * @return bool
	 */
	public function apply_discount( $amount, $type ) {
		return false;
	}

	/**
	 * Cancel subscription.
	 */
	public function cancel() {}
}
