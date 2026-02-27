<?php
/**
 * RetainWoo SUMO Subscriptions Wrapper
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SUMO Subscriptions wrapper.
 */
class RetainWoo_Subscription_SUMO extends RetainWoo_Subscription_Base {

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
		$period = get_post_meta( $this->id, 'sumo_subscription_period', true );
		return $period ? $period : 'month';
	}

	/**
	 * Get billing interval.
	 *
	 * @return int
	 */
	public function get_billing_interval() {
		$interval = get_post_meta( $this->id, 'sumo_subscription_period_interval', true );
		return (int) ( $interval ? $interval : 1 );
	}

	/**
	 * Get next payment date.
	 *
	 * @return string
	 */
	public function get_next_payment_date() {
		return get_post_meta( $this->id, 'sumo_next_payment_date', true );
	}

	/**
	 * Pause subscription.
	 *
	 * @param int $months Number of months to pause.
	 * @return bool
	 */
	public function pause( $months ) {
		update_post_meta( $this->id, 'sumo_subscription_status', 'Pause' );
		update_post_meta( $this->id, '_retainwoo_resume_date', gmdate( 'Y-m-d H:i:s', strtotime( "+{$months} months" ) ) );
		wp_schedule_single_event( strtotime( "+{$months} months" ), 'retainwoo_resume_sub', array( $this->id ) );
		return true;
	}

	/**
	 * Skip next payment.
	 *
	 * @return bool
	 */
	public function skip_payment() {
		$next     = $this->get_next_payment_date();
		$period   = $this->get_billing_period();
		$interval = $this->get_billing_interval();
		$new      = gmdate( 'Y-m-d H:i:s', strtotime( "+{$interval} {$period}", strtotime( $next ) ) );
		update_post_meta( $this->id, 'sumo_next_payment_date', $new );
		return true;
	}

	/**
	 * Apply discount to subscription.
	 *
	 * @param float  $amount Discount amount.
	 * @param string $type   Discount type.
	 * @return string
	 */
	public function apply_discount( $amount, $type ) {
		$code      = 'CS-' . strtoupper( substr( md5( $this->id . time() ), 0, 8 ) );
		$coupon_id = wp_insert_post(
			array(
				'post_title'  => $code,
				'post_status' => 'publish',
				'post_type'   => 'shop_coupon',
				'post_author' => 1,
			)
		);
		update_post_meta( $coupon_id, 'discount_type', 'percent' === $type ? 'recurring_percent' : 'recurring_fee' );
		update_post_meta( $coupon_id, 'coupon_amount', $amount );
		update_post_meta( $coupon_id, 'usage_limit', 0 );
		update_post_meta( $coupon_id, 'customer_email', array( $this->get_customer_email() ) );
		update_post_meta( $coupon_id, 'expiry_date', gmdate( 'Y-m-d', strtotime( '+1 year' ) ) );
		update_post_meta( $this->id, '_retainwoo_coupon', $code );
		return $code;
	}

	/**
	 * Cancel subscription.
	 */
	public function cancel() {
		update_post_meta( $this->id, 'sumo_subscription_status', 'Cancelled' );
	}
}
