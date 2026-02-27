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
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RetainWoo Compat class.
 */
class RetainWoo_Compat {

	/**
	 * Which plugin is active: 'wcs' | 'webtoffee' | 'yith' | 'sumo' | 'pluginshive' | null
	 *
	 * @var string|null
	 */
	public static $active_plugin = null;

	/**
	 * Detect the active subscription plugin.
	 */
	public static function detect() {
		if ( class_exists( 'WC_Subscriptions' ) ) {
			self::$active_plugin = 'wcs';
		} elseif ( class_exists( 'WC_Subscriptions_Core_Plugin' ) ) {
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

	/**
	 * Check if a supported plugin is active.
	 *
	 * @return bool
	 */
	public static function is_supported() {
		return null !== self::$active_plugin;
	}

	/**
	 * Get the active plugin name.
	 *
	 * @return string
	 */
	public static function get_plugin_name() {
		$names = array(
			'wcs'         => 'WooCommerce Subscriptions',
			'webtoffee'   => 'WebToffee Subscriptions',
			'yith'        => 'YITH WooCommerce Subscriptions',
			'sumo'        => 'SUMO Subscriptions',
			'pluginshive' => 'Plugins Hive Subscriptions',
		);
		return isset( $names[ self::$active_plugin ] ) ? $names[ self::$active_plugin ] : 'Unknown';
	}

	/**
	 * Returns the CSS selectors used by each plugin for the cancel button.
	 *
	 * @return string
	 */
	public static function get_cancel_selectors() {
		$selectors = array(
			'a[href*="cancel_subscription"]',
			'a[href*="change_subscription_to=cancelled"]',
			'a.wt-subscription-cancel',
			'a.yith-wcss-cancel-subscription',
			'a[href*="ywsbs-action=cancel"]',
			'a.sumo-cancel-subscription',
			'a[href*="sumo_sub_action=cancel"]',
			'a[href*="rp_sub_action=cancel"]',
			'.subscription-actions a[href*="cancel"]',
			'.woocommerce-orders-table a[href*="cancel"]',
		);
		return implode( ', ', $selectors );
	}

	/**
	 * Get a normalized subscription object by ID.
	 *
	 * @param int $id Subscription ID.
	 * @return RetainWoo_Subscription_Base|null
	 */
	public static function get_subscription( $id ) {
		switch ( self::$active_plugin ) {
			case 'wcs':
				if ( function_exists( 'wcs_get_subscription' ) ) {
					$sub = wcs_get_subscription( $id );
					if ( $sub ) {
						return new RetainWoo_Subscription_WCS( $sub );
					}
				}
				break;
			case 'webtoffee':
				$order = wc_get_order( $id );
				if ( $order && 'wt_subscription' === $order->get_type() ) {
					return new RetainWoo_Subscription_WebToffee( $order );
				}
				if ( $order ) {
					return new RetainWoo_Subscription_WebToffee( $order );
				}
				break;
			case 'yith':
				$post = get_post( $id );
				if ( $post && 'yith_subscription' === $post->post_type ) {
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

	/**
	 * Register a callback fired when a subscription is cancelled.
	 *
	 * @param callable $callback Callback function.
	 */
	public static function on_cancelled( $callback ) {
		switch ( self::$active_plugin ) {
			case 'wcs':
				add_action( 'woocommerce_subscription_status_cancelled', $callback, 10, 1 );
				break;
			case 'webtoffee':
				add_action(
					'wt_subscription_status_changed',
					function ( $sub_id, $new_status ) use ( $callback ) {
						if ( 'cancelled' === $new_status || 'cancel' === $new_status ) {
							$sub = RetainWoo_Compat::get_subscription( $sub_id );
							if ( $sub ) {
								call_user_func( $callback, $sub );
							}
						}
					},
					10,
					2
				);
				add_action(
					'woocommerce_order_status_changed',
					function ( $order_id, $from, $to ) use ( $callback ) {
						if ( in_array( $to, array( 'cancelled', 'wc-cancelled' ), true ) ) {
							$sub = RetainWoo_Compat::get_subscription( $order_id );
							if ( $sub ) {
								call_user_func( $callback, $sub );
							}
						}
					},
					10,
					3
				);
				break;
			case 'yith':
				add_action(
					'yith_wcss_subscription_status_changed',
					function ( $sub_id, $new_status ) use ( $callback ) {
						if ( in_array( $new_status, array( 'cancelled', 'cancel', 'expired' ), true ) ) {
							$sub = RetainWoo_Compat::get_subscription( $sub_id );
							if ( $sub ) {
								call_user_func( $callback, $sub );
							}
						}
					},
					10,
					2
				);
				break;
			case 'sumo':
				add_action(
					'sumo_subscription_status_changed',
					function ( $sub_id, $new_status ) use ( $callback ) {
						if ( 'Cancelled' === $new_status ) {
							$sub = RetainWoo_Compat::get_subscription( $sub_id );
							if ( $sub ) {
								call_user_func( $callback, $sub );
							}
						}
					},
					10,
					2
				);
				break;
			default:
				add_action(
					'woocommerce_order_status_changed',
					function ( $order_id, $from, $to ) use ( $callback ) {
						if ( 'cancelled' === $to ) {
							$sub = RetainWoo_Compat::get_subscription( $order_id );
							if ( $sub ) {
								call_user_func( $callback, $sub );
							}
						}
					},
					10,
					3
				);
		}
	}
}
