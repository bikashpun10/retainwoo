<?php
/**
 * RetainWoo Uninstall
 *
 * Runs when the plugin is deleted. Removes all plugin data.
 *
 * @package RetainWoo
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$retainwoo_options = array(
	'retainwoo_enabled',
	'retainwoo_offer_pause',
	'retainwoo_offer_skip',
	'retainwoo_skip_cooldown',
	'retainwoo_offer_discount',
	'retainwoo_discount_amount',
	'retainwoo_discount_type',
	'retainwoo_headline',
	'retainwoo_subheadline',
	'retainwoo_winback_enabled',
	'retainwoo_winback_subject',
	'retainwoo_winback_delay',
);

/**
 * Delete all plugin options and drop the events table for a single site.
 *
 * @param array $retainwoo_opts List of option keys to delete.
 */
function retainwoo_uninstall_blog( $retainwoo_opts ) {
	global $wpdb;
	foreach ( $retainwoo_opts as $retainwoo_option ) {
		delete_option( $retainwoo_option );
	}
	$retainwoo_table = esc_sql( $wpdb->prefix . 'retainwoo_events' );
	$wpdb->query( 'DROP TABLE IF EXISTS `' . $retainwoo_table . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
}

global $wpdb;
if ( is_multisite() ) {
	$retainwoo_site_list = $wpdb->get_col( 'SELECT blog_id FROM `' . $wpdb->blogs . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $retainwoo_site_list as $retainwoo_site_id ) {
		switch_to_blog( $retainwoo_site_id );
		retainwoo_uninstall_blog( $retainwoo_options );
		restore_current_blog();
	}
} else {
	retainwoo_uninstall_blog( $retainwoo_options );
}
