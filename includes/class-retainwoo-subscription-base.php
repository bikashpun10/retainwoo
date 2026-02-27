<?php
/**
 * RetainWoo Subscription Base
 *
 * Abstract base class for all subscription wrappers.
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base — all wrappers implement this interface.
 */
abstract class RetainWoo_Subscription_Base {

	/**
	 * Get subscription ID.
	 *
	 * @return int
	 */
	abstract public function get_id();

	/**
	 * Get customer ID.
	 *
	 * @return int
	 */
	abstract public function get_customer_id();

	/**
	 * Get customer email.
	 *
	 * @return string
	 */
	abstract public function get_customer_email();

	/**
	 * Get subscription total.
	 *
	 * @return float
	 */
	abstract public function get_total();

	/**
	 * Get billing period (day|week|month|year).
	 *
	 * @return string
	 */
	abstract public function get_billing_period();

	/**
	 * Get billing interval.
	 *
	 * @return int
	 */
	abstract public function get_billing_interval();

	/**
	 * Get next payment date.
	 *
	 * @return string
	 */
	abstract public function get_next_payment_date();

	/**
	 * Pause subscription.
	 *
	 * @param int $months Number of months to pause.
	 * @return bool
	 */
	abstract public function pause( $months );

	/**
	 * Skip next payment.
	 *
	 * @return bool
	 */
	abstract public function skip_payment();

	/**
	 * Apply discount to subscription.
	 *
	 * @param float  $amount Discount amount.
	 * @param string $type   Discount type.
	 * @return string|bool
	 */
	abstract public function apply_discount( $amount, $type );

	/**
	 * Cancel subscription.
	 */
	abstract public function cancel();
}
