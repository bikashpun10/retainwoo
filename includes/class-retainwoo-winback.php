<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RetainWoo_WinBack {

    public static function init() {
        add_action( 'retainwoo_send_winback', [ __CLASS__, 'send' ], 10, 2 );
    }

    public static function send( $sub_id, $email ) {
        if ( ! is_email( $email ) ) return;
        if ( RetainWoo_Settings::get( 'retainwoo_winback_enabled' ) !== '1' ) return;

        $subject  = RetainWoo_Settings::get( 'retainwoo_winback_subject', "We miss you — here's something special" );
        $blogname = get_bloginfo( 'name' );
        $shop_url = wc_get_page_permalink( 'shop' );

        // Build a one-time coupon for the win-back email
        $amount = RetainWoo_Settings::get( 'retainwoo_discount_amount', 20 );
        $type   = RetainWoo_Settings::get( 'retainwoo_discount_type', 'percent' );
        $code   = 'BACK-' . strtoupper( substr( md5( $sub_id . $email . time() ), 0, 8 ) );

        $coupon_id = wp_insert_post([
            'post_title'  => $code,
            'post_status' => 'publish',
            'post_type'   => 'shop_coupon',
            'post_author' => 1,
        ]);
        update_post_meta( $coupon_id, 'discount_type', $type === 'percent' ? 'percent' : 'fixed_cart' );
        update_post_meta( $coupon_id, 'coupon_amount', $amount );
        update_post_meta( $coupon_id, 'usage_limit', 1 );
        update_post_meta( $coupon_id, 'customer_email', [ $email ] );
        update_post_meta( $coupon_id, 'expiry_date', date( 'Y-m-d', strtotime( '+14 days' ) ) );

        $label   = $type === 'percent' ? "{$amount}% off" : "\${$amount} off";
        $message = self::build_email( $blogname, $label, $code, $shop_url );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$blogname} <" . get_option( 'admin_email' ) . '>',
        ];

        wp_mail( $email, $subject, $message, $headers );

        // Log it
        RetainWoo_Tracker::track( $sub_id, 0, 'winback_sent', 'email', 0 );
    }

    private static function build_email( $store_name, $discount_label, $coupon_code, $shop_url ) {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
  .wrap { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .header { background: linear-gradient(135deg, #667eea, #764ba2); padding: 40px 32px; text-align: center; color: #fff; }
  .header h1 { margin: 0; font-size: 26px; }
  .header p { margin: 8px 0 0; opacity: 0.85; font-size: 15px; }
  .body { padding: 36px 32px; color: #333; }
  .body p { font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
  .coupon-box { background: #f0f4ff; border: 2px dashed #667eea; border-radius: 10px; padding: 20px; text-align: center; margin: 24px 0; }
  .coupon-box .label { font-size: 13px; color: #888; margin-bottom: 8px; }
  .coupon-box .code { font-size: 28px; font-weight: 800; letter-spacing: 3px; color: #4f46e5; }
  .coupon-box .expiry { font-size: 12px; color: #aaa; margin-top: 8px; }
  .cta { display: block; text-align: center; background: #667eea; color: #fff !important; padding: 16px 32px; border-radius: 8px; text-decoration: none; font-size: 16px; font-weight: 700; margin: 24px 0; }
  .footer { padding: 20px 32px; font-size: 12px; color: #aaa; text-align: center; border-top: 1px solid #f0f0f0; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>We miss you 💙</h1>
    <p>It hasn't been the same since you left.</p>
  </div>
  <div class="body">
    <p>Hi there,</p>
    <p>We noticed you recently cancelled your subscription with <strong><?php echo esc_html( $store_name ); ?></strong>. We completely understand — life gets busy and priorities shift.</p>
    <p>But if there's any chance we could win you back, we'd love to offer you something special:</p>

    <div class="coupon-box">
      <div class="label">YOUR EXCLUSIVE DISCOUNT</div>
      <div class="code"><?php echo esc_html( $coupon_code ); ?></div>
      <div class="expiry">Valid for 14 days - One use only</div>
    </div>

    <p>Use code <strong><?php echo esc_html( $coupon_code ); ?></strong> at checkout to get <strong><?php echo esc_html( $discount_label ); ?></strong> when you reactivate your subscription.</p>

    <a href="<?php echo esc_url( $shop_url ); ?>" class="cta">Reactivate My Subscription</a>

    <p>If there's anything we could have done better, we'd genuinely love to hear from you. Just reply to this email.</p>
    <p>Thank you for being a customer,<br><strong><?php echo esc_html( $store_name ); ?></strong></p>
  </div>
  <div class="footer">
    You're receiving this because you recently cancelled a subscription at <?php echo esc_html( $store_name ); ?>.
  </div>
</div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}
