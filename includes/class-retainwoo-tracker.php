<?php
/**
 * RetainWoo Tracker
 *
 * Handles tracking of subscription retention events.
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RetainWoo Tracker class.
 */
class RetainWoo_Tracker {

	/**
	 * Initialize the tracker.
	 */
	public static function init() {
		add_action( 'wp_ajax_retainwoo_track', array( __CLASS__, 'ajax_track' ) );
	}

	/**
	 * Track a subscription event.
	 *
	 * @param string $sub_id      Subscription ID.
	 * @param int    $customer_id Customer ID.
	 * @param string $event       Event name.
	 * @param string $offer       Offer type.
	 * @param float  $value       Subscription value.
	 */
	public static function track( $sub_id, $customer_id, $event, $offer = null, $value = 0 ) {
		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'retainwoo_events',
			array(
				'sub_id'      => $sub_id,
				'customer_id' => $customer_id,
				'event'       => $event,
				'offer'       => $offer,
				'sub_value'   => $value,
				'plugin'      => RetainWoo_Compat::$active_plugin,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s', '%f', '%s', '%s' )
		);
	}

	/**
	 * Handle AJAX tracking request.
	 */
	public static function ajax_track() {
		check_ajax_referer( 'retainwoo', 'nonce' );
		$sub_id = sanitize_text_field( wp_unslash( isset( $_POST['sub_id'] ) ? $_POST['sub_id'] : '' ) );
		$event  = sanitize_text_field( wp_unslash( isset( $_POST['event'] ) ? $_POST['event'] : 'popup_shown' ) );
		if ( $sub_id ) {
			self::track( $sub_id, get_current_user_id(), $event );
		}
		wp_send_json_success();
	}

	/**
	 * Get retention stats for the given number of days.
	 *
	 * @param int $days Number of days to look back.
	 * @return array Stats array.
	 */
	public static function get_stats( $days = 30 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'retainwoo_events';
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$shown = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE event=%s AND created_at>=%s',
				$table,
				'popup_shown',
				$since
			)
		);

		$saved = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE event=%s AND created_at>=%s',
				$table,
				'offer_accepted',
				$since
			)
		);

		$lost = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE event=%s AND created_at>=%s',
				$table,
				'cancelled',
				$since
			)
		);

		$avg_val = (float) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT AVG(sub_value) FROM %i WHERE event=%s AND sub_value>0 AND created_at>=%s',
				$table,
				'cancelled',
				$since
			)
		);

		$breakdown = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'SELECT offer, COUNT(*) as cnt FROM %i WHERE event=%s AND created_at>=%s GROUP BY offer',
				$table,
				'offer_accepted',
				$since
			)
		);

		$rev_saved = $saved * $avg_val;
		$save_rate = $shown > 0 ? round( $saved / $shown * 100, 1 ) : 0;

		return compact( 'shown', 'saved', 'lost', 'rev_saved', 'save_rate', 'breakdown' );
	}
}
