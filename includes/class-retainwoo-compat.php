<?php
/**
 * RetainWoo Compatibility Layer
 *
 * Detects which subscription plugin is active and provides a
 * unified API so the rest of RetainWoo doesn't care which plugin
 * the store is using.
 *
 * Supported plugins:
 *  1. WooCommerce Subscriptions (official, premium)
 *  2. Subscriptions for WooCommerce by WebToffee (premium, ~$89/yr)
 *  3. YITH WooCommerce Subscriptions (freemium)
 *  4. SUMO Subscriptions (premium)
 *  5. Subscriptions & Recurring Payments by Plugins Hive (freemium)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class RetainWoo_Compat {

    /** Which plugin is active: 'wcs' | 'webtoffee' | 'yith' | 'sumo' | 'pluginshive' | null */
    public static $active_plugin = null;

    /** Detect the active subscription plugin */
    public static function detect() {
        if ( class_exists( 'WC_Subscriptions' ) ) {
            self::$active_plugin = 'wcs';
        } elseif ( class_exists( 'WC_Subscriptions_Core_Plugin' ) ) {
            // WooCommerce Subscriptions Core (free version of WCS)
            self::$active_plugin = 'wcs';
        } elseif ( defined( 'WC_WEBTOFFEE_SUBSCRIPTIONS_VERSION' ) || class_exists( 'WF_Subscriptions' ) ) {
            self::$active_plugin = 'webtoffee';
        } elseif ( defined( 'YITH_YWSBS_VERSION' ) || class_exists( 'YITH_WC_Subscription' ) ) {
            self::$active_plugin = 'yith';
        } elseif ( defined( 'SUMO_SUBSCRIPTIONS_VERSION' ) || class_exists( 'SUMOSubscriptions' ) ) {
            self::$active_plugin = 'sumo';
        } elseif ( defined( 'RP_SUB_VERSION' ) || class_exists( 'RP_Subscription' ) ) {
            self::$active_plugin = 'pluginshive';
        } else {
            self::$active_plugin = null;
        }
    }

    public static function is_supported() {
        return self::$active_plugin !== null;
    }

    public static function get_plugin_name() {
        $names = [
            'wcs'        => 'WooCommerce Subscriptions',
            'webtoffee'  => 'WebToffee Subscriptions',
            'yith'       => 'YITH WooCommerce Subscriptions',
            'sumo'       => 'SUMO Subscriptions',
            'pluginshive'=> 'Plugins Hive Subscriptions',
        ];
        return $names[ self::$active_plugin ] ?? 'Unknown';
    }

    // ─── Cancel Button Interception ──────────────────────────────────────────

    /**
     * Returns the CSS selectors used by each plugin for the cancel button.
     * The JS uses these to intercept clicks.
     */
    public static function get_cancel_selectors() {
        $selectors = [
            // WooCommerce Subscriptions
            'a[href*="cancel_subscription"]',
            'a[href*="change_subscription_to=cancelled"]',
            // WebToffee — uses same URL pattern as WCS
            'a.wt-subscription-cancel',
            // YITH
            'a.yith-wcss-cancel-subscription',
            'a[href*="ywsbs-action=cancel"]',
            // SUMO
            'a.sumo-cancel-subscription',
            'a[href*="sumo_sub_action=cancel"]',
            // Plugins Hive
            'a[href*="rp_sub_action=cancel"]',
            // Generic fallback — any link with "cancel" near subscription text
            '.subscription-actions a[href*="cancel"]',
            '.woocommerce-orders-table a[href*="cancel"]',
        ];
        return implode( ', ', $selectors );
    }

    // ─── Subscription Object Wrapper ─────────────────────────────────────────

    /**
     * Get a normalized subscription object by ID.
     * Returns a RetainWoo_Subscription wrapper or null.
     */
    public static function get_subscription( $id ) {
        switch ( self::$active_plugin ) {
            case 'wcs':
                if ( function_exists( 'wcs_get_subscription' ) ) {
                    $sub = wcs_get_subscription( $id );
                    if ( $sub ) return new RetainWoo_Subscription_WCS( $sub );
                }
                break;

            case 'webtoffee':
                // WebToffee stores subscriptions as custom WC orders
                $order = wc_get_order( $id );
                if ( $order && $order->get_type() === 'wt_subscription' ) {
                    return new RetainWoo_Subscription_WebToffee( $order );
                }
                // Fallback: try loading directly
                if ( $order ) return new RetainWoo_Subscription_WebToffee( $order );
                break;

            case 'yith':
                // YITH subscriptions are custom post type
                $post = get_post( $id );
                if ( $post && $post->post_type === 'yith_subscription' ) {
                    return new RetainWoo_Subscription_YITH( $id );
                }
                break;

            case 'sumo':
                return new RetainWoo_Subscription_SUMO( $id );

            case 'pluginshive':
                return new RetainWoo_Subscription_Generic( $id );
        }
        return null;
    }

    // ─── Status Change Hooks ─────────────────────────────────────────────────

    /**
     * Register a callback fired when a subscription is cancelled.
     * Normalizes across all supported plugins.
     */
    public static function on_cancelled( $callback ) {
        switch ( self::$active_plugin ) {
            case 'wcs':
                add_action( 'woocommerce_subscription_status_cancelled', $callback, 10, 1 );
                break;
            case 'webtoffee':
                add_action( 'wt_subscription_status_changed', function( $sub_id, $new_status ) use ( $callback ) {
                    if ( $new_status === 'cancelled' || $new_status === 'cancel' ) {
                        $sub = RetainWoo_Compat::get_subscription( $sub_id );
                        if ( $sub ) call_user_func( $callback, $sub );
                    }
                }, 10, 2 );
                // Also hook into WooCommerce order status change for WT subscriptions
                add_action( 'woocommerce_order_status_changed', function( $order_id, $from, $to ) use ( $callback ) {
                    if ( in_array( $to, [ 'cancelled', 'wc-cancelled' ] ) ) {
                        $sub = RetainWoo_Compat::get_subscription( $order_id );
                        if ( $sub ) call_user_func( $callback, $sub );
                    }
                }, 10, 3 );
                break;
            case 'yith':
                add_action( 'yith_wcss_subscription_status_changed', function( $sub_id, $new_status ) use ( $callback ) {
                    if ( in_array( $new_status, [ 'cancelled', 'cancel', 'expired' ] ) ) {
                        $sub = RetainWoo_Compat::get_subscription( $sub_id );
                        if ( $sub ) call_user_func( $callback, $sub );
                    }
                }, 10, 2 );
                break;
            case 'sumo':
                add_action( 'sumo_subscription_status_changed', function( $sub_id, $new_status ) use ( $callback ) {
                    if ( $new_status === 'Cancelled' ) {
                        $sub = RetainWoo_Compat::get_subscription( $sub_id );
                        if ( $sub ) call_user_func( $callback, $sub );
                    }
                }, 10, 2 );
                break;
            default:
                // Generic fallback via order status
                add_action( 'woocommerce_order_status_changed', function( $order_id, $from, $to ) use ( $callback ) {
                    if ( $to === 'cancelled' ) {
                        $sub = RetainWoo_Compat::get_subscription( $order_id );
                        if ( $sub ) call_user_func( $callback, $sub );
                    }
                }, 10, 3 );
        }
    }
}

// ─── Normalized Subscription Wrappers ────────────────────────────────────────

/**
 * Abstract base — all wrappers implement this interface
 */
abstract class RetainWoo_Subscription_Base {
    abstract public function get_id();
    abstract public function get_customer_id();
    abstract public function get_customer_email();
    abstract public function get_total();
    abstract public function get_billing_period(); // day|week|month|year
    abstract public function get_billing_interval();
    abstract public function get_next_payment_date();
    abstract public function pause( $months );
    abstract public function skip_payment();
    abstract public function apply_discount( $amount, $type );
    abstract public function cancel();
}

/** WooCommerce Subscriptions wrapper */
class RetainWoo_Subscription_WCS extends RetainWoo_Subscription_Base {
    private $sub;
    public function __construct( $sub ) { $this->sub = $sub; }
    public function get_id()             { return $this->sub->get_id(); }
    public function get_customer_id()    { return $this->sub->get_customer_id(); }
    public function get_customer_email() { return $this->sub->get_billing_email(); }
    public function get_total()          { return (float) $this->sub->get_total(); }
    public function get_billing_period() { return $this->sub->get_billing_period(); }
    public function get_billing_interval(){ return (int) $this->sub->get_billing_interval(); }
    public function get_next_payment_date() {
        return $this->sub->get_date( 'next_payment' );
    }
    public function pause( $months ) {
        $this->sub->update_status( 'on-hold' );
        $resume = date( 'Y-m-d H:i:s', strtotime( "+{$months} months" ) );
        $this->sub->update_meta_data( '_retainwoo_resume_date', $resume );
        $this->sub->save();
        wp_schedule_single_event( strtotime( "+{$months} months" ), 'retainwoo_resume_sub', [ $this->get_id() ] );
        return true;
    }
    public function skip_payment() {
        $next  = $this->get_next_payment_date();
        $new   = date( 'Y-m-d H:i:s', wcs_add_time( $this->get_billing_interval(), $this->get_billing_period(), strtotime( $next ) ) );
        $this->sub->update_dates( [ 'next_payment' => $new ] );
        $this->sub->save();
        return true;
    }
    public function apply_discount( $amount, $type ) {
        $code = $this->_create_coupon( $amount, $type );
        $this->sub->apply_coupon( $code );
        if ( method_exists( $this->sub, 'calculate_totals' ) ) {
            $this->sub->calculate_totals();
        }
        $this->sub->save();
        return $code;
    }
    public function cancel() {
        $this->sub->update_status( 'cancelled' );
        $this->sub->save();
    }
    private function _create_coupon( $amount, $type ) {
        $code     = 'CS-' . strtoupper( substr( md5( $this->get_id() . time() ), 0, 8 ) );
        $coupon_id = wp_insert_post([
            'post_title' => $code, 'post_status' => 'publish',
            'post_type'  => 'shop_coupon', 'post_author' => 1,
        ]);
        update_post_meta( $coupon_id, 'discount_type', 'percent' === $type ? 'recurring_percent' : 'recurring_fee' );
        update_post_meta( $coupon_id, 'coupon_amount', $amount );
        update_post_meta( $coupon_id, 'usage_limit', 0 );
        update_post_meta( $coupon_id, 'customer_email', [ $this->get_customer_email() ] );
        update_post_meta( $coupon_id, 'expiry_date', date( 'Y-m-d', strtotime( '+1 year' ) ) );
        return $code;
    }
}

/** WebToffee Subscriptions wrapper */
class RetainWoo_Subscription_WebToffee extends RetainWoo_Subscription_Base {
    private $order;
    public function __construct( $order ) { $this->order = $order; }
    public function get_id()             { return $this->order->get_id(); }
    public function get_customer_id()    { return $this->order->get_customer_id(); }
    public function get_customer_email() { return $this->order->get_billing_email(); }
    public function get_total()          { return (float) $this->order->get_total(); }
    public function get_billing_period() {
        return get_post_meta( $this->get_id(), '_wt_sub_period', true ) ?: 'month';
    }
    public function get_billing_interval() {
        return (int) ( get_post_meta( $this->get_id(), '_wt_sub_period_interval', true ) ?: 1 );
    }
    public function get_next_payment_date() {
        return get_post_meta( $this->get_id(), '_wt_sub_next_payment_date', true );
    }
    public function pause( $months ) {
        // WebToffee uses 'on-hold' status same as WCS
        $this->order->update_status( 'wc-on-hold' );
        update_post_meta( $this->get_id(), '_retainwoo_resume_date', date( 'Y-m-d H:i:s', strtotime( "+{$months} months" ) ) );
        wp_schedule_single_event( strtotime( "+{$months} months" ), 'retainwoo_resume_sub', [ $this->get_id() ] );
        return true;
    }
    public function skip_payment() {
        $next    = $this->get_next_payment_date();
        $period  = $this->get_billing_period();
        $interval= $this->get_billing_interval();
        $new     = date( 'Y-m-d H:i:s', strtotime( "+{$interval} {$period}", strtotime( $next ) ) );
        update_post_meta( $this->get_id(), '_wt_sub_next_payment_date', $new );
        return true;
    }
    public function apply_discount( $amount, $type ) {
        // WebToffee supports coupons on renewals via WooCommerce coupon system
        $code = 'CS-' . strtoupper( substr( md5( $this->get_id() . time() ), 0, 8 ) );
        $coupon_id = wp_insert_post([
            'post_title' => $code, 'post_status' => 'publish',
            'post_type'  => 'shop_coupon', 'post_author' => 1,
        ]);
        update_post_meta( $coupon_id, 'discount_type', 'percent' === $type ? 'recurring_percent' : 'recurring_fee' );
        update_post_meta( $coupon_id, 'coupon_amount', $amount );
        update_post_meta( $coupon_id, 'usage_limit', 0 );
        update_post_meta( $coupon_id, 'customer_email', [ $this->get_customer_email() ] );
        update_post_meta( $coupon_id, 'expiry_date', date( 'Y-m-d', strtotime( '+1 year' ) ) );
        // Store coupon against subscription for application on next renewal
        update_post_meta( $this->get_id(), '_retainwoo_coupon', $code );
        return $code;
    }
    public function cancel() {
        $this->order->update_status( 'wc-cancelled' );
    }
}

/** YITH WooCommerce Subscriptions wrapper */
class RetainWoo_Subscription_YITH extends RetainWoo_Subscription_Base {
    private $id;
    public function __construct( $id ) { $this->id = $id; }
    public function get_id()             { return $this->id; }
    public function get_customer_id()    { return (int) get_post_field( 'post_author', $this->id ); }
    public function get_customer_email() {
        $user = get_user_by( 'id', $this->get_customer_id() );
        return $user ? $user->user_email : '';
    }
    public function get_total()          { return (float) get_post_meta( $this->id, '_ywsbs_price', true ); }
    public function get_billing_period() { return get_post_meta( $this->id, '_ywsbs_subscription_period', true ) ?: 'month'; }
    public function get_billing_interval(){ return (int) ( get_post_meta( $this->id, '_ywsbs_subscription_interval', true ) ?: 1 ); }
    public function get_next_payment_date() { return get_post_meta( $this->id, '_ywsbs_payment_due_date', true ); }
    public function pause( $months ) {
        update_post_meta( $this->id, '_ywsbs_status', 'paused' );
        update_post_meta( $this->id, '_retainwoo_resume_date', date( 'Y-m-d H:i:s', strtotime( "+{$months} months" ) ) );
        wp_schedule_single_event( strtotime( "+{$months} months" ), 'retainwoo_resume_sub', [ $this->id ] );
        return true;
    }
    public function skip_payment() {
        $next     = $this->get_next_payment_date();
        $period   = $this->get_billing_period();
        $interval = $this->get_billing_interval();
        $new      = date( 'Y-m-d H:i:s', strtotime( "+{$interval} {$period}", strtotime( $next ) ) );
        update_post_meta( $this->id, '_ywsbs_payment_due_date', $new );
        return true;
    }
    public function apply_discount( $amount, $type ) {
        $code = 'CS-' . strtoupper( substr( md5( $this->id . time() ), 0, 8 ) );
        $coupon_id = wp_insert_post([
            'post_title' => $code, 'post_status' => 'publish',
            'post_type'  => 'shop_coupon', 'post_author' => 1,
        ]);
        update_post_meta( $coupon_id, 'discount_type', 'percent' === $type ? 'recurring_percent' : 'recurring_fee' );
        update_post_meta( $coupon_id, 'coupon_amount', $amount );
        update_post_meta( $coupon_id, 'usage_limit', 0 );
        update_post_meta( $coupon_id, 'customer_email', [ $this->get_customer_email() ] );
        update_post_meta( $coupon_id, 'expiry_date', date( 'Y-m-d', strtotime( '+1 year' ) ) );
        update_post_meta( $this->id, '_retainwoo_coupon', $code );
        return $code;
    }
    public function cancel() {
        update_post_meta( $this->id, '_ywsbs_status', 'cancelled' );
    }
}

/** SUMO Subscriptions wrapper */
class RetainWoo_Subscription_SUMO extends RetainWoo_Subscription_Base {
    private $id;
    public function __construct( $id ) { $this->id = $id; }
    public function get_id()             { return $this->id; }
    public function get_customer_id()    { return (int) get_post_meta( $this->id, '_customer_user', true ); }
    public function get_customer_email() {
        $user = get_user_by( 'id', $this->get_customer_id() );
        return $user ? $user->user_email : '';
    }
    public function get_total()          { return (float) get_post_meta( $this->id, '_order_total', true ); }
    public function get_billing_period() { return get_post_meta( $this->id, 'sumo_subscription_period', true ) ?: 'month'; }
    public function get_billing_interval(){ return (int) ( get_post_meta( $this->id, 'sumo_subscription_period_interval', true ) ?: 1 ); }
    public function get_next_payment_date() { return get_post_meta( $this->id, 'sumo_next_payment_date', true ); }
    public function pause( $months ) {
        update_post_meta( $this->id, 'sumo_subscription_status', 'Pause' );
        update_post_meta( $this->id, '_retainwoo_resume_date', date( 'Y-m-d H:i:s', strtotime( "+{$months} months" ) ) );
        wp_schedule_single_event( strtotime( "+{$months} months" ), 'retainwoo_resume_sub', [ $this->id ] );
        return true;
    }
    public function skip_payment() {
        $next     = $this->get_next_payment_date();
        $period   = $this->get_billing_period();
        $interval = $this->get_billing_interval();
        $new      = date( 'Y-m-d H:i:s', strtotime( "+{$interval} {$period}", strtotime( $next ) ) );
        update_post_meta( $this->id, 'sumo_next_payment_date', $new );
        return true;
    }
    public function apply_discount( $amount, $type ) {
        $code = 'CS-' . strtoupper( substr( md5( $this->id . time() ), 0, 8 ) );
        $coupon_id = wp_insert_post([
            'post_title' => $code, 'post_status' => 'publish',
            'post_type'  => 'shop_coupon', 'post_author' => 1,
        ]);
        update_post_meta( $coupon_id, 'discount_type', 'percent' === $type ? 'recurring_percent' : 'recurring_fee' );
        update_post_meta( $coupon_id, 'coupon_amount', $amount );
        update_post_meta( $coupon_id, 'usage_limit', 0 );
        update_post_meta( $coupon_id, 'customer_email', [ $this->get_customer_email() ] );
        update_post_meta( $coupon_id, 'expiry_date', date( 'Y-m-d', strtotime( '+1 year' ) ) );
        update_post_meta( $this->id, '_retainwoo_coupon', $code );
        return $code;
    }
    public function cancel() {
        update_post_meta( $this->id, 'sumo_subscription_status', 'Cancelled' );
    }
}

/** Generic fallback wrapper for unknown/future plugins */
class RetainWoo_Subscription_Generic extends RetainWoo_Subscription_Base {
    private $id;
    public function __construct( $id ) { $this->id = $id; }
    public function get_id()             { return $this->id; }
    public function get_customer_id()    { return (int) get_post_meta( $this->id, '_customer_user', true ); }
    public function get_customer_email() {
        $user = get_user_by( 'id', $this->get_customer_id() );
        return $user ? $user->user_email : '';
    }
    public function get_total()          { return (float) get_post_meta( $this->id, '_order_total', true ); }
    public function get_billing_period() { return 'month'; }
    public function get_billing_interval(){ return 1; }
    public function get_next_payment_date() { return date( 'Y-m-d H:i:s', strtotime( '+1 month' ) ); }
    public function pause( $months )        { return false; }
    public function skip_payment()          { return false; }
    public function apply_discount( $amount, $type ) { return false; }
    public function cancel()                {}
}

// ─── Cron: Resume paused subscriptions ───────────────────────────────────────
add_action( 'retainwoo_resume_sub', 'retainwoo_do_resume_sub' );
function retainwoo_do_resume_sub( $sub_id ) {
    $sub = RetainWoo_Compat::get_subscription( $sub_id );
    if ( ! $sub ) return;

    switch ( RetainWoo_Compat::$active_plugin ) {
        case 'wcs':
            $raw = wcs_get_subscription( $sub_id );
            if ( $raw && $raw->has_status( 'on-hold' ) ) {
                $raw->update_status( 'active' );
                $raw->delete_meta_data( '_retainwoo_resume_date' );
                $raw->save();
            }
            break;
        case 'webtoffee':
            $order = wc_get_order( $sub_id );
            if ( $order ) {
                $order->update_status( 'wc-active' );
                delete_post_meta( $sub_id, '_retainwoo_resume_date' );
            }
            break;
        case 'yith':
            update_post_meta( $sub_id, '_ywsbs_status', 'active' );
            delete_post_meta( $sub_id, '_retainwoo_resume_date' );
            break;
        case 'sumo':
            update_post_meta( $sub_id, 'sumo_subscription_status', 'Active' );
            delete_post_meta( $sub_id, '_retainwoo_resume_date' );
            break;
    }
}
