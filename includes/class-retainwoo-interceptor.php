<?php
if (!defined('ABSPATH'))
    exit;

class RetainWoo_Interceptor
{

    public static function init()
    {
        if (!RetainWoo_Settings::is_enabled())
            return;

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
        add_action('wp_ajax_retainwoo_accept_offer', [__CLASS__, 'handle_offer']);
        // expose eligibility so front–end can hide offers on cooldown
        add_action('wp_ajax_retainwoo_check_offers', [__CLASS__, 'check_offers']);
        add_action('wp_ajax_retainwoo_check_discount', [__CLASS__, 'check_discount_eligibility']);

        // Track actual cancellations
        RetainWoo_Compat::on_cancelled([__CLASS__, 'on_cancelled']);
    }

    public static function enqueue()
    {
        if (!is_account_page())
            return;

        wp_enqueue_style(
            'retainwoo',
            RETAINWOO_URL . 'assets/css/retainwoo.css',
            [],
            RETAINWOO_VERSION
        );

        // shared design tokens for consistent look between admin and frontend
        wp_enqueue_style(
            'retainwoo-common',
            RETAINWOO_URL . 'assets/css/retainwoo-common.css',
            [],
            RETAINWOO_VERSION
        );

        wp_enqueue_script(
            'retainwoo',
            RETAINWOO_URL . 'assets/js/retainwoo.js',
            ['jquery'],
            RETAINWOO_VERSION,
            true
        );

        $s = RetainWoo_Settings::get_all();

        wp_localize_script('retainwoo', 'RetainWoo', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('retainwoo'),
            'selectors' => RetainWoo_Compat::get_cancel_selectors(),
            'plugin' => RetainWoo_Compat::$active_plugin,
            'settings' => $s,
            'strings' => [
                'headline' => $s['headline'],
                'subheadline' => $s['subheadline'],
                'pause_1' => __('Pause my subscription for 1 month', 'retainwoo'),
                'pause_1_desc' => __('No payment for 1 month. We\'ll resume automatically.', 'retainwoo'),
                'pause_3' => __('Pause my subscription for 3 months', 'retainwoo'),
                'pause_3_desc' => __('No payment for 3 months. We\'ll resume automatically.', 'retainwoo'),
                'skip' => __('Skip my next payment', 'retainwoo'),
                'skip_desc' => __('Stay active — we\'ll move your next charge forward by one cycle (once every 3 months).', 'retainwoo'),
                'discount' => self::discount_label($s),
                'discount_desc' => __('Keep your subscription at a discount. Applies to renewals too.', 'retainwoo'),
                'cancel_anyway' => __('No thanks, cancel my subscription', 'retainwoo'),
                'processing' => __('Just a moment...', 'retainwoo'),
                'success' => __('Done! Your subscription has been saved.', 'retainwoo'),
            ],
        ]);
    }

    private static function discount_label($s)
    {
        $a = $s['discount_amount'];
        $t = $s['discount_type'];
        $label = "percent" === $t ? $a . "% off" : "$" . $a . " off";
        return sprintf(
            /* translators: %s: discount label e.g. "20% off" or "$20 off" */
            __("Get %s your next payment — and stay!", "retainwoo"),
            $label
        );
    }

    /**
     * AJAX: Accept a retention offer
     */
    public static function handle_offer()
    {
        check_ajax_referer('retainwoo', 'nonce');

        $offer = isset($_POST['offer']) ? sanitize_text_field(wp_unslash($_POST['offer'])) : '';
        $sub_id = isset($_POST['sub_id']) ? absint($_POST['sub_id']) : 0;

        if (!$offer || !$sub_id) {
            wp_send_json_error(['message' => __('Invalid request.', 'retainwoo')]);
        }

        $sub = RetainWoo_Compat::get_subscription($sub_id);

        if (!$sub) {
            wp_send_json_error(['message' => __('Subscription not found.', 'retainwoo')]);
        }

        if ((int) $sub->get_customer_id() !== get_current_user_id()) {
            wp_send_json_error(['message' => __('Unauthorized.', 'retainwoo')]);
        }

        $result = RetainWoo_Offers::apply($sub, $offer);

        if ($result['success']) {
            RetainWoo_Tracker::track($sub_id, $sub->get_customer_id(), 'offer_accepted', $offer, $sub->get_total());
            $data = ['message' => $result['message']];
            if (!empty($result['code'])) {
                $data['code'] = $result['code'];
            }
            wp_send_json_success($data);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * AJAX: Check whether any of the offers are currently eligible for the
     * given subscription. This allows the popup to hide buttons that are on
     * cooldown (skip/pause) or already used (discount).
     */
    public static function check_offers()
    {
        check_ajax_referer('retainwoo', 'nonce');

        $sub_id = isset($_POST['sub_id']) ? absint($_POST['sub_id']) : 0;
        if (!$sub_id) {
            wp_send_json_success(['eligibility' => []]);
        }

        $sub = RetainWoo_Compat::get_subscription( $sub_id );
        if ( ! $sub || (int) $sub->get_customer_id() !== get_current_user_id() ) {
            wp_send_json_success(['eligibility' => []]);
        }

        $elig = RetainWoo_Offers::get_eligibility($sub_id);
        wp_send_json_success(['eligibility' => $elig]);
    }

    /**
     * AJAX: Check if the discount offer is still available for a subscription
     */
    public static function check_discount_eligibility()
    {
        check_ajax_referer('retainwoo', 'nonce');

        $sub_id = isset($_POST['sub_id']) ? absint($_POST['sub_id']) : 0;

        if (!$sub_id) {
            wp_send_json_success(['eligible' => false]);
        }

        $sub = RetainWoo_Compat::get_subscription( $sub_id );
        if ( ! $sub || (int) $sub->get_customer_id() !== get_current_user_id() ) {
            wp_send_json_success(['eligible' => false]);
        }

        $already_used = get_post_meta($sub_id, '_retainwoo_discount_used', true);
        wp_send_json_success(['eligible' => empty($already_used)]);
    }

    /**
     * Fired when subscription is actually cancelled (user chose "cancel anyway")
     */
    public static function on_cancelled($sub)
    {
        $sub_id = is_object($sub) ? $sub->get_id() : $sub;
        $obj = is_object($sub) ? $sub : RetainWoo_Compat::get_subscription($sub_id);
        if (!$obj)
            return;

        RetainWoo_Tracker::track(
            $sub_id,
            $obj->get_customer_id(),
            'cancelled',
            null,
            $obj->get_total()
        );

        // Trigger win-back email if enabled
        if (RetainWoo_Settings::get('retainwoo_winback_enabled') === '1') {
            $delay = (int) RetainWoo_Settings::get('retainwoo_winback_delay', 1);
            wp_schedule_single_event(
                time() + ($delay * DAY_IN_SECONDS),
                'retainwoo_send_winback',
                [$sub_id, $obj->get_customer_email()]
            );
        }
    }
}
