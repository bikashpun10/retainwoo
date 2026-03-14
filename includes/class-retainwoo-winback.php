<?php
/**
 * RetainWoo WinBack
 *
 * Handles win-back email sending for cancelled subscriptions.
 *
 * @package RetainWoo
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * RetainWoo WinBack class.
 */
class RetainWoo_WinBack
{

	/**
	 * Initialize the win-back handler.
	 */
	public static function init()
	{
		add_action('retainwoo_send_winback', array(__CLASS__, 'send'), 10, 2);
	}

	/**
	 * Send a win-back email to a cancelled subscriber.
	 *
	 * @param int    $sub_id Subscription ID.
	 * @param string $email  Customer email address.
	 */
	public static function send($sub_id, $email)
	{
		if (!is_email($email)) {
			return;
		}
		if ('1' !== RetainWoo_Settings::get('retainwoo_winback_enabled')) {
			return;
		}

		$subject = RetainWoo_Settings::get('retainwoo_winback_subject', __("We miss you — here's something special", 'retainwoo'));
		$blogname = get_bloginfo('name');
		$shop_url = wc_get_page_permalink('shop');

		// Build a one-time coupon for the win-back email.
		$amount = RetainWoo_Settings::get('retainwoo_discount_amount', 20);
		$type = RetainWoo_Settings::get('retainwoo_discount_type', 'percent');
		$code = 'BACK-' . strtoupper(substr(md5($sub_id . $email . time()), 0, 8));

		$coupon_id = wp_insert_post(
			array(
				'post_title' => $code,
				'post_status' => 'publish',
				'post_type' => 'shop_coupon',
				'post_author' => 1,
			)
		);
		update_post_meta($coupon_id, 'discount_type', 'percent' === $type ? 'percent' : 'fixed_cart');
		update_post_meta($coupon_id, 'coupon_amount', $amount);
		update_post_meta($coupon_id, 'usage_limit', 1);
		update_post_meta($coupon_id, 'customer_email', array($email));
		update_post_meta($coupon_id, 'expiry_date', gmdate('Y-m-d', strtotime('+14 days')));

		/* translators: %s: discount amount number */
		$label = 'percent' === $type ? sprintf(__('%s%% off', 'retainwoo'), $amount) : sprintf(__('$%s off', 'retainwoo'), $amount);

		// Editable email content from settings.
		$heading = RetainWoo_Settings::get('retainwoo_winback_heading', __('We miss you 💙', 'retainwoo'));
		$body = RetainWoo_Settings::get('retainwoo_winback_body', __("Hi there,\n\nWe noticed you recently cancelled your subscription with {store_name}. We completely understand \xe2\x80\x94 life gets busy and priorities shift.\n\nBut if there's any chance we could win you back, we'd love to offer you something special:", 'retainwoo'));
		$btn_text = RetainWoo_Settings::get('retainwoo_winback_button', __('Reactivate My Subscription', 'retainwoo'));

		// Replace {store_name} placeholder in body.
		$body = str_replace('{store_name}', $blogname, $body);

		$message = self::build_email($blogname, $label, $code, $shop_url, $heading, $body, $btn_text);

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			"From: {$blogname} <" . get_option('admin_email') . '>',
		);

		wp_mail($email, $subject, $message, $headers);

		// Log the win-back send event.
		RetainWoo_Tracker::track($sub_id, 0, 'winback_sent', 'email', 0);
	}

	/**
	 * Build the win-back email HTML.
	 *
	 * @param string $store_name      Store name.
	 * @param string $discount_label  Discount label e.g. "20% off".
	 * @param string $coupon_code     Coupon code.
	 * @param string $shop_url        Shop URL.
	 * @param string $heading         Email heading.
	 * @param string $body_text       Main body text.
	 * @param string $btn_text        CTA button text.
	 * @return string
	 */
	private static function build_email($store_name, $discount_label, $coupon_code, $shop_url, $heading, $body_text, $btn_text)
	{
		// Convert newlines in body text to HTML paragraphs.
		$body_paragraphs = array_filter(array_map('trim', preg_split('/\n{2,}/', $body_text)));
		ob_start();
		?>
		<!DOCTYPE html>
		<html>

		<head>
			<meta charset="UTF-8">
			<style>
				body {
					font-family: Arial, sans-serif;
					background: #f5f5f5;
					margin: 0;
					padding: 0;
				}

				.wrap {
					max-width: 560px;
					margin: 40px auto;
					background: #fff;
					border-radius: 12px;
					overflow: hidden;
					box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
				}

				.header {
					background: linear-gradient(135deg, #667eea, #764ba2);
					padding: 40px 32px;
					text-align: center;
					color: #fff;
				}

				.header h1 {
					margin: 0;
					font-size: 26px;
				}

				.body {
					padding: 36px 32px;
					background: #fafbff;
					color: #333;
				}

				.body p {
					font-size: 15px;
					line-height: 1.7;
					margin: 0 0 16px;
				}

				.coupon-box {
					background: #f0f4ff;
					border: 2px dashed #667eea;
					border-radius: 10px;
					padding: 20px;
					text-align: center;
					margin: 24px 0;
				}

				.coupon-box .label {
					font-size: 13px;
					color: #888;
					margin-bottom: 8px;
				}

				.coupon-box .code {
					font-size: 28px;
					font-weight: 800;
					letter-spacing: 3px;
					color: #4f46e5;
				}

				.coupon-box .expiry {
					font-size: 12px;
					color: #aaa;
					margin-top: 8px;
				}

				.cta {
					display: block;
					text-align: center;
					background: #667eea;
					color: #fff !important;
					padding: 16px 32px;
					border-radius: 8px;
					text-decoration: none;
					font-size: 16px;
					font-weight: 700;
					margin: 24px 0;
				}

				.footer {
					padding: 20px 32px;
					font-size: 12px;
					color: #aaa;
					text-align: center;
					border-top: 1px solid #f0f0f0;
				}
			</style>
		</head>

		<body>
			<div class="wrap">
				<div class="header">
					<h1><?php echo esc_html($heading); ?></h1>
				</div>
				<div class="body">
					<?php foreach ($body_paragraphs as $para): ?>
						<p><?php echo esc_html($para); ?></p>
					<?php endforeach; ?>

					<div class="coupon-box">
						<div class="label"><?php echo esc_html__('YOUR EXCLUSIVE DISCOUNT', 'retainwoo'); ?></div>
						<div class="code"><?php echo esc_html($coupon_code); ?></div>
						<div class="expiry"><?php echo esc_html__('Valid for 14 days - One use only', 'retainwoo'); ?></div>
					</div>

					<p><?php /* translators: 1: coupon code, 2: discount label */ printf(esc_html__('Use code %1$s at checkout to get %2$s when you reactivate your subscription.', 'retainwoo'), '<strong>' . esc_html($coupon_code) . '</strong>', '<strong>' . esc_html($discount_label) . '</strong>'); ?>
					</p>

					<a href="<?php echo esc_url($shop_url); ?>" class="cta"><?php echo esc_html($btn_text); ?></a>

					<p><?php /* translators: %s: store name */ printf(esc_html__('Thank you for being a customer, %s', 'retainwoo'), '<br><strong>' . esc_html($store_name) . '</strong>'); ?>
					</p>
				</div>
				<div class="footer">
					<?php /* translators: %s: store name */ printf(esc_html__("You're receiving this because you recently cancelled a subscription at %s.", 'retainwoo'), esc_html($store_name)); ?>
				</div>
			</div>
		</body>

		</html>
		<?php
		return ob_get_clean();
	}
}
