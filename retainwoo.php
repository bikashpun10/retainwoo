<?php
/**
 * Plugin Name:       RetainWoo
 * Description:       Intercepts WooCommerce subscription cancellations with smart retention offers. Show pause, skip, or discount offers before a subscriber cancels. Built-in win-back email. Works with WooCommerce Subscriptions, WebToffee, YITH, and SUMO.
 * Plugin URI:        https://wordpress.org/plugins/retainwoo
 * Version:           1.0.0
 * Requires at least: 5.8
 * Tested up to:      6.9
 * Requires PHP:      7.4
 * Author:            Bikash Pun
 * Author URI:        https://envitetech.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       retainwoo
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RETAINWOO_VERSION', '1.0.0' );
define( 'RETAINWOO_PATH', plugin_dir_path( __FILE__ ) );
define( 'RETAINWOO_URL', plugin_dir_url( __FILE__ ) );

// Subscription wrappers — load base first, then implementations.
require_once RETAINWOO_PATH . 'includes/class-retainwoo-subscription-base.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-subscription-wcs.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-subscription-webtoffee.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-subscription-yith.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-subscription-sumo.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-subscription-generic.php';

// Core plugin files.
require_once RETAINWOO_PATH . 'includes/class-retainwoo-compat.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-settings.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-interceptor.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-offers.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-tracker.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-winback.php';
require_once RETAINWOO_PATH . 'includes/class-retainwoo-cron.php';
require_once RETAINWOO_PATH . 'admin/class-retainwoo-admin.php';

/**
 * Initialize the RetainWoo plugin.
 *
 * Detects the active subscription plugin and boots all modules.
 */
function retainwoo_init() {
	RetainWoo_Compat::detect();
	if ( ! RetainWoo_Compat::is_supported() ) {
		add_action( 'admin_notices', 'retainwoo_missing_plugin_notice' );
		return;
	}
	RetainWoo_Settings::init();
	RetainWoo_Interceptor::init();
	RetainWoo_Tracker::init();
	RetainWoo_WinBack::init();
	RetainWoo_Admin::init();
}
add_action( 'plugins_loaded', 'retainwoo_init', 20 );

/**
 * Display an admin notice when no supported subscription plugin is active.
 */
function retainwoo_missing_plugin_notice() {
	echo '<div class="notice notice-warning is-dismissible"><p>';
	echo '<strong>' . esc_html__( 'RetainWoo', 'retainwoo' ) . '</strong> ' . esc_html__( 'requires a supported subscription plugin:', 'retainwoo' ) . ' ';
	echo esc_html__( 'WooCommerce Subscriptions, WebToffee Subscriptions, YITH WooCommerce Subscriptions, or SUMO Subscriptions.', 'retainwoo' );
	echo '</p></div>';
}

/**
 * Add a Settings link to the plugin action links.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function retainwoo_plugin_action_links( $links ) {
	$settings = '<a href="' . admin_url( 'admin.php?page=retainwoo-settings' ) . '">' . esc_html__( 'Settings', 'retainwoo' ) . '</a>';
	array_unshift( $links, $settings );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'retainwoo_plugin_action_links' );

register_activation_hook( __FILE__, 'retainwoo_activate' );

/**
 * Run on plugin activation.
 *
 * Creates the events table and sets default options.
 */
function retainwoo_activate() {
	global $wpdb;
	$table   = $wpdb->prefix . 'retainwoo_events';
	$charset = $wpdb->get_charset_collate();
	$sql     = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sub_id      VARCHAR(100)    NOT NULL,
        customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        event       VARCHAR(50)     NOT NULL,
        offer       VARCHAR(50)     DEFAULT NULL,
        sub_value   DECIMAL(10,2)   DEFAULT 0,
        plugin      VARCHAR(50)     DEFAULT NULL,
        created_at  DATETIME        DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY sub_id (sub_id),
        KEY event (event),
        KEY created_at (created_at)
    ) {$charset};";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	$defaults = array(
		'retainwoo_enabled'         => '1',
		'retainwoo_offer_pause'     => '1',
		'retainwoo_offer_skip'      => '1',
		'retainwoo_offer_discount'  => '1',
		'retainwoo_discount_amount' => '20',
		'retainwoo_discount_type'   => 'percent',
		'retainwoo_headline'        => __( 'Wait - before you go!', 'retainwoo' ),
		'retainwoo_subheadline'     => __( "We'd hate to lose you. Pick an option and we'll make it work.", 'retainwoo' ),
		'retainwoo_winback_enabled' => '1',
		'retainwoo_winback_subject' => __( "We miss you - here's something special", 'retainwoo' ),
		'retainwoo_winback_delay'   => '1',
	);
	foreach ( $defaults as $key => $val ) {
		if ( false === get_option( $key ) ) {
			update_option( $key, $val );
		}
	}
}

register_deactivation_hook( __FILE__, 'retainwoo_deactivate' );

/**
 * Run on plugin deactivation.
 *
 * Clears all scheduled cron events.
 */
function retainwoo_deactivate() {
	wp_clear_scheduled_hook( 'retainwoo_resume_sub' );
	wp_clear_scheduled_hook( 'retainwoo_send_winback' );
}
