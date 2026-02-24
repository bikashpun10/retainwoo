<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RetainWoo_Tracker {

    public static function init() {
        add_action( 'wp_ajax_retainwoo_track', [ __CLASS__, 'ajax_track' ] );
    }

    public static function track( $sub_id, $customer_id, $event, $offer = null, $value = 0 ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'retainwoo_events',
            [
                'sub_id'      => $sub_id,
                'customer_id' => $customer_id,
                'event'       => $event,
                'offer'       => $offer,
                'sub_value'   => $value,
                'plugin'      => RetainWoo_Compat::$active_plugin,
                'created_at'  => current_time( 'mysql' ),
            ],
            [ '%s', '%d', '%s', '%s', '%f', '%s', '%s' ]
        );
    }

    public static function ajax_track() {
        check_ajax_referer( 'retainwoo', 'nonce' );
        $sub_id = sanitize_text_field( $_POST['sub_id'] ?? '' );
        $event  = sanitize_text_field( $_POST['event'] ?? 'popup_shown' );
        if ( $sub_id ) {
            self::track( $sub_id, get_current_user_id(), $event );
        }
        wp_send_json_success();
    }

    public static function get_stats( $days = 30 ) {
        global $wpdb;
        $t     = $wpdb->prefix . 'retainwoo_events';
        $since = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $shown    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE event='popup_shown' AND created_at>=%s", $since ) );
        $saved    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE event='offer_accepted' AND created_at>=%s", $since ) );
        $lost     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE event='cancelled' AND created_at>=%s", $since ) );
        $avg_val  = (float) $wpdb->get_var( $wpdb->prepare( "SELECT AVG(sub_value) FROM $t WHERE event='cancelled' AND sub_value>0 AND created_at>=%s", $since ) );
        $rev_saved= $saved * $avg_val;
        $save_rate= $shown > 0 ? round( $saved / $shown * 100, 1 ) : 0;

        $breakdown = $wpdb->get_results( $wpdb->prepare(
            "SELECT offer, COUNT(*) as cnt FROM $t WHERE event='offer_accepted' AND created_at>=%s GROUP BY offer",
            $since
        ) );

        return compact( 'shown', 'saved', 'lost', 'rev_saved', 'save_rate', 'breakdown' );
    }
}
