<?php
/**
 * RetainWoo Settings
 *
 * Provides a unified API for retrieving plugin settings.
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RetainWoo Settings class.
 */
class RetainWoo_Settings {

	/**
	 * Initialize settings.
	 */
	public static function init() {}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key          Option key.
	 * @param mixed  $fallback     Fallback value if option is not set.
	 * @return mixed
	 */
	public static function get( $key, $fallback = '' ) {
		return get_option( $key, $fallback );
	}

	/**
	 * Check if RetainWoo is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return '1' === self::get( 'retainwoo_enabled', '1' );
	}

	/**
	 * Get all plugin settings as an array.
	 *
	 * @return array
	 */
	public static function get_all() {
		return array(
			'enabled'         => self::is_enabled(),
			'offer_pause'     => '1' === self::get( 'retainwoo_offer_pause', '1' ),
			'offer_skip'      => '1' === self::get( 'retainwoo_offer_skip', '1' ),
			'skip_cooldown'   => (int) self::get( 'retainwoo_skip_cooldown', 3 ),
			'offer_discount'  => '1' === self::get( 'retainwoo_offer_discount', '1' ),
			'discount_amount' => (float) self::get( 'retainwoo_discount_amount', 20 ),
			'discount_type'   => self::get( 'retainwoo_discount_type', 'percent' ),
			'headline'        => self::get( 'retainwoo_headline', 'Wait — before you go!' ),
			'subheadline'     => self::get( 'retainwoo_subheadline', "We'd hate to lose you." ),
		);
	}
}
