<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$options = [
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
];

function retainwoo_uninstall_blog() {
    global $wpdb;
    foreach ( $GLOBALS['retainwoo_uninstall_options'] as $option ) {
        delete_option( $option );
    }
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}retainwoo_events" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
}

$GLOBALS['retainwoo_uninstall_options'] = $options;

global $wpdb;
if ( is_multisite() ) {
    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    foreach ( $blog_ids as $blog_id ) {
        switch_to_blog( $blog_id );
        retainwoo_uninstall_blog();
        restore_current_blog();
    }
} else {
    retainwoo_uninstall_blog();
}