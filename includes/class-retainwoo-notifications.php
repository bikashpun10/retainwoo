<?php
/**
 * RetainWoo Email Notifications
 *
 * Sends instant email alerts to store owners when a subscriber is saved
 * or a win-back email is sent. Uses WordPress wp_mail() — zero setup needed.
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RetainWoo Notifications class.
 */
class RetainWoo_Notifications {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_retainwoo_test_notification', array( __CLASS__, 'ajax_test' ) );
	}

	/**
	 * Get the notification recipient email.
	 * Uses custom email if set, otherwise falls back to admin email.
	 *
	 * @return string
	 */
	private static function get_recipient() {
		$custom = get_option( 'retainwoo_notify_email', '' );
		return ! empty( $custom ) ? sanitize_email( $custom ) : get_option( 'admin_email' );
	}

	/**
	 * Send a notification email to the store owner.
	 *
	 * @param string $subject Email subject.
	 * @param string $message Email body (HTML).
	 * @return bool
	 */
	public static function send( $subject, $message ) {
		if ( '1' !== get_option( 'retainwoo_notify_enabled', '1' ) ) {
			return false;
		}

		$to      = self::get_recipient();
		$store   = get_bloginfo( 'name' );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $store . ' <' . get_option( 'admin_email' ) . '>',
		);

		return wp_mail( $to, $subject, self::build_email( $message, $store ), $headers );
	}

	/**
	 * Notify store owner when a subscriber is saved.
	 *
	 * @param int    $sub_id Subscription ID.
	 * @param string $offer  Offer type accepted.
	 */
	public static function notify_saved( $sub_id, $offer ) {
		$labels = array(
			'pause_1'  => 'Pause 1 month',
			'pause_3'  => 'Pause 3 months',
			'skip'     => 'Skip next payment',
			'discount' => 'Discount offer',
		);

		$offer_label = isset( $labels[ $offer ] ) ? $labels[ $offer ] : ucfirst( str_replace( '_', ' ', $offer ) );
		$store       = get_bloginfo( 'name' );
		$time        = current_time( 'h:i A' );
		$admin_url   = admin_url( 'admin.php?page=retainwoo' );

		$subject = sprintf(
			/* translators: %s: store name */
			__( '✅ Subscriber saved — %s', 'retainwoo' ),
			$store
		);

		$body = '
			<p>Good news! RetainWoo just saved a subscriber from cancelling.</p>
			<table style="width:100%;border-collapse:collapse;margin:16px 0;">
				<tr>
					<td style="padding:10px 14px;background:#f4f6fb;border-radius:6px 6px 0 0;font-weight:600;width:40%;">' . esc_html__( 'Offer Accepted', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#f4f6fb;border-radius:6px 6px 0 0;">' . esc_html( $offer_label ) . '</td>
				</tr>
				<tr>
					<td style="padding:10px 14px;background:#ffffff;border-top:1px solid #e8eaf2;font-weight:600;">' . esc_html__( 'Subscription ID', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#ffffff;border-top:1px solid #e8eaf2;">#' . esc_html( $sub_id ) . '</td>
				</tr>
				<tr>
					<td style="padding:10px 14px;background:#f4f6fb;border-top:1px solid #e8eaf2;font-weight:600;">' . esc_html__( 'Time', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#f4f6fb;border-top:1px solid #e8eaf2;">' . esc_html( $time ) . '</td>
				</tr>
			</table>
			<p><a href="' . esc_url( $admin_url ) . '" style="display:inline-block;background:#1a1a2e;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600;">View Dashboard</a></p>
		';

		self::send( $subject, $body );
	}

	/**
	 * Notify store owner when a win-back email is sent.
	 *
	 * @param string $email  Customer email address.
	 * @param int    $sub_id Subscription ID.
	 */
	public static function notify_winback( $email, $sub_id ) {
		$at     = strpos( $email, '@' );
		$masked = substr( $email, 0, min( 3, $at ) ) . '***' . substr( $email, $at );
		$store  = get_bloginfo( 'name' );
		$time   = current_time( 'h:i A' );

		$subject = sprintf(
			/* translators: %s: store name */
			__( '📧 Win-back email sent — %s', 'retainwoo' ),
			$store
		);

		$body = '
			<p>A win-back email with a unique coupon has been sent to a cancelled subscriber.</p>
			<table style="width:100%;border-collapse:collapse;margin:16px 0;">
				<tr>
					<td style="padding:10px 14px;background:#f4f6fb;border-radius:6px 6px 0 0;font-weight:600;width:40%;">' . esc_html__( 'Sent To', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#f4f6fb;border-radius:6px 6px 0 0;">' . esc_html( $masked ) . '</td>
				</tr>
				<tr>
					<td style="padding:10px 14px;background:#ffffff;border-top:1px solid #e8eaf2;font-weight:600;">' . esc_html__( 'Subscription ID', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#ffffff;border-top:1px solid #e8eaf2;">#' . esc_html( $sub_id ) . '</td>
				</tr>
				<tr>
					<td style="padding:10px 14px;background:#f4f6fb;border-top:1px solid #e8eaf2;font-weight:600;">' . esc_html__( 'Time', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#f4f6fb;border-top:1px solid #e8eaf2;">' . esc_html( $time ) . '</td>
				</tr>
			</table>
		';

		self::send( $subject, $body );
	}

	/**
	 * Build the notification email HTML wrapper.
	 *
	 * @param string $body_html Inner content HTML.
	 * @param string $store_name Store name.
	 * @return string
	 */
	private static function build_email( $body_html, $store_name ) {
		return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;background:#f4f6fb;margin:0;padding:0;">
<div style="max-width:520px;margin:40px auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
	<div style="background:linear-gradient(135deg,#1a1a2e,#0f3460);padding:28px 32px;text-align:center;">
		<p style="margin:0;font-size:13px;color:rgba(255,255,255,0.7);letter-spacing:0.5px;text-transform:uppercase;">RetainWoo</p>
		<p style="margin:4px 0 0;font-size:11px;color:rgba(255,255,255,0.4);">' . esc_html( $store_name ) . '</p>
	</div>
	<div style="padding:28px 32px;color:#1a1a2e;font-size:15px;line-height:1.7;">
		' . $body_html . '
	</div>
	<div style="padding:16px 32px;background:#f4f6fb;text-align:center;font-size:12px;color:#aaa;border-top:1px solid #e8eaf2;">
		Sent by RetainWoo &mdash; WooCommerce Subscription Retention
	</div>
</div>
</body>
</html>';
	}

	/**
	 * AJAX handler — send a test notification email.
	 */
	public static function ajax_test() {
		check_ajax_referer( 'retainwoo_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'retainwoo' ) ) );
		}

		$store   = get_bloginfo( 'name' );
		$time    = current_time( 'h:i A' );
		$subject = sprintf(
			/* translators: %s: store name */
			__( '🔔 RetainWoo test notification — %s', 'retainwoo' ),
			$store
		);

		$body = '
			<p>This is a test notification from RetainWoo. If you received this email, notifications are working correctly.</p>
			<table style="width:100%;border-collapse:collapse;margin:16px 0;">
				<tr>
					<td style="padding:10px 14px;background:#f4f6fb;border-radius:6px;font-weight:600;width:40%;">' . esc_html__( 'Store', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#f4f6fb;border-radius:6px;">' . esc_html( $store ) . '</td>
				</tr>
				<tr>
					<td style="padding:10px 14px;background:#ffffff;border-top:1px solid #e8eaf2;font-weight:600;">' . esc_html__( 'Time', 'retainwoo' ) . '</td>
					<td style="padding:10px 14px;background:#ffffff;border-top:1px solid #e8eaf2;">' . esc_html( $time ) . '</td>
				</tr>
			</table>
		';

		$sent = self::send( $subject, $body );
		$to   = self::get_recipient();

		if ( $sent ) {
			wp_send_json_success(
				array(
					/* translators: %s: email address */
					'message' => sprintf( __( '✅ Test email sent to %s', 'retainwoo' ), $to ),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( '❌ Failed. Please check your WordPress email configuration.', 'retainwoo' ) ) );
		}
	}
}