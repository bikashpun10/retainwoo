<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class RetainWoo_Settings {

    public static function init() {}

    public static function get( $key, $default = '' ) {
        return get_option( $key, $default );
    }

    public static function is_enabled() {
        return self::get( 'retainwoo_enabled', '1' ) === '1';
    }

    public static function get_all() {
        return [
            'enabled'         => self::is_enabled(),
            'offer_pause'     => self::get( 'retainwoo_offer_pause', '1' ) === '1',
            'offer_skip'      => self::get( 'retainwoo_offer_skip', '1' ) === '1',
            'skip_cooldown'   => (int) self::get( 'retainwoo_skip_cooldown', 3 ),
            'offer_discount'  => self::get( 'retainwoo_offer_discount', '1' ) === '1',
            'discount_amount' => (float) self::get( 'retainwoo_discount_amount', 20 ),
            'discount_type'   => self::get( 'retainwoo_discount_type', 'percent' ),
            'headline'        => self::get( 'retainwoo_headline', 'Wait — before you go!' ),
            'subheadline'     => self::get( 'retainwoo_subheadline', "We'd hate to lose you." ),
        ];
    }
}
