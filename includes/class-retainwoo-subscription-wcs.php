<?php
/**
 * RetainWoo WooCommerce Subscriptions Wrapper
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Subscriptions wrapper.
 */
class RetainWoo_Subscription_WCS extends RetainWoo_Subscription_Base {

	/**
	 * Subscription object.
	 *
	 * @var object
	 */
	private $sub;

	/**
	 * Constructor.
	 *
	 * @param object $sub Subscription object.
	 */
	public function __construct( $sub ) {
		$this->sub = $sub;
	}

	/**
	 * Get subscription ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->sub->get_id();
	}

	/**
	 * Get customer ID.
	 *
	 * @return int
	 */
	public function get_customer_id() {
		return $this->sub->get_customer_id();
	}

	/**
	 * Get customer email.
	 *
	 * @return string
	 */
	public function get_customer_email() {
		return $this->sub->get_billing_email();
	}

	/**
	 * Get subscription total.
	 *
	 * @return float
	 */
	public function get_total() {
		return (float) $this->sub->get_total();
	}

	/**
	 * Get billing period.
	 *
	 * @return string
	 */
	public function get_billing_period() {
		return $this->sub->get_billing_period();
	}

	/**
	 * Get billing interval.
	 *
	 * @return int
	 */
	public function get_billing_interval() {
		return (int) $this->sub->get_billing_interval();
	}

	/**
	 * Get next payment date.
	 *
	 * @return string
	 */
	public function get_next_payment_date() {
		return $this->sub->get_date( 'next_payment' );
	}

	/**
	 * Pause subscription.
	 *
	 * @param int $months Number of months to pause.
	 * @return bool
	 */
	public function pause( $months ) {
		$this->sub->update_status( 'on-hold' );
		$resume = gmdate( 'Y-m-d H:i:s', strtotime( "+{$months} months" ) );
		$this->sub->update_meta_data( '_retainwoo_resume_date', $resume );
		$this->sub->save();
		wp_schedule_single_event( strtotime( "+{$months} months" ), 'retainwoo_resume_sub', array( $this->get_id() ) );
		return true;
	}

	/**
	 * Skip next payment.
	 *
	 * @return bool
	 */
	public function skip_payment() {
		$next = $this->get_next_payment_date();
		$new  = gmdate( 'Y-m-d H:i:s', wcs_add_time( $this->get_billing_interval(), $this->get_billing_period(), strtotime( $next ) ) );
		$this->sub->update_dates( array( 'next_payment' => $new ) );
		$this->sub->save();
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
		$code = $this->create_coupon( $amount, $type );
		$this->sub->apply_coupon( $code );
		if ( method_exists( $this->sub, 'calculate_totals' ) ) {
			$this->sub->calculate_totals();
		}
		$this->sub->save();
		return $code;
	}

	/**
	 * Cancel subscription.
	 */
	public function cancel() {
		$this->sub->update_status( 'cancelled' );
		$this->sub->save();
	}

	/**
	 * Create a coupon for the subscription.
	 *
	 * @param float  $amount Discount amount.
	 * @param string $type   Discount type.
	 * @return string Coupon code.
	 */
	private function create_coupon( $amount, $type ) {
		$code      = 'CS-' . strtoupper( substr( md5( $this->get_id() . time() ), 0, 8 ) );
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
		return $code;
	}
}
