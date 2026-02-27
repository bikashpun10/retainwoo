<?php
/**
 * RetainWoo Cron Functions
 *
 * Handles scheduled cron events for resuming paused subscriptions.
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'retainwoo_resume_sub', 'retainwoo_do_resume_sub' );

/**
 * Resume a paused subscription.
 *
 * @param int $sub_id Subscription ID.
 */
function retainwoo_do_resume_sub( $sub_id ) {
	$sub = RetainWoo_Compat::get_subscription( $sub_id );
	if ( ! $sub ) {
		return;
	}

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
