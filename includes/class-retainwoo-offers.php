<?php
// ─── Offers ───────────────────────────────────────────────────────────────────
if (!defined('ABSPATH'))
    exit;

class RetainWoo_Offers
{

    /**
     * Cooldown helper: checks if an offer was used within $months months.
     * Returns the timestamp string if on cooldown, or false if eligible.
     */
    private static function on_cooldown($sub_id, $meta_key, $months)
    {
        $last = get_post_meta($sub_id, $meta_key, true);
        if ($last && strtotime($last) > strtotime("-{$months} months")) {
            return $last; // on cooldown — return when it was last used
        }
        return false;
    }

    public static function apply($sub, $offer)
    {
        switch ($offer) {

            // ── Pause 1 month ────────────────────────────────────────────────
            case 'pause_1':
                // Cooldown: once per 6 months (shared with pause_3)
                $cd = self::on_cooldown($sub->get_id(), '_retainwoo_pause_used', 6);
                if ($cd) {
                    return [
                        'success' => false,
                        'message' => __('You have already used a pause offer in the last 6 months.', 'retainwoo'),
                    ];
                }
                $ok = $sub->pause(1);
                if ($ok) {
                    update_post_meta($sub->get_id(), '_retainwoo_pause_used', current_time('mysql'));
                    return ['success' => true, 'message' => __('Your subscription has been paused for 1 month.', 'retainwoo')];
                }
                return ['success' => false, 'message' => __('Could not pause subscription. Please contact support.', 'retainwoo')];

            // ── Pause 3 months ───────────────────────────────────────────────
            case 'pause_3':
                // Cooldown: once per 6 months (shared with pause_1)
                $cd = self::on_cooldown($sub->get_id(), '_retainwoo_pause_used', 6);
                if ($cd) {
                    return [
                        'success' => false,
                        'message' => __('You have already used a pause offer in the last 6 months.', 'retainwoo'),
                    ];
                }
                $ok = $sub->pause(3);
                if ($ok) {
                    update_post_meta($sub->get_id(), '_retainwoo_pause_used', current_time('mysql'));
                    return ['success' => true, 'message' => __('Your subscription has been paused for 3 months.', 'retainwoo')];
                }
                return ['success' => false, 'message' => __('Could not pause subscription.', 'retainwoo')];

            // ── Skip next payment ────────────────────────────────────────────
            case 'skip':
                // Cooldown: once per configured number of months
                $months = (int) RetainWoo_Settings::get( 'retainwoo_skip_cooldown', 3 );
                $cd = self::on_cooldown($sub->get_id(), '_retainwoo_skip_used', $months);
                if ($cd) {
                    return [
                        'success' => false,
                        'message' => sprintf(
                            /* translators: %d: months */
                            __('You have already skipped a payment in the last %d months.', 'retainwoo'),
                            $months
                        ),
                    ];
                }
                $ok = $sub->skip_payment();
                if ($ok) {
                    update_post_meta($sub->get_id(), '_retainwoo_skip_used', current_time('mysql'));
                    return ['success' => true, 'message' => __('Your next payment has been skipped.', 'retainwoo')];
                }
                return ['success' => false, 'message' => __('Could not skip payment.', 'retainwoo')];

            // ── Discount ─────────────────────────────────────────────────────
            case 'discount':
                // Hard block: one discount per subscription, ever
                $already_used = get_post_meta($sub->get_id(), '_retainwoo_discount_used', true);
                if ($already_used) {
                    return [
                        'success' => false,
                        'message' => __('A discount has already been applied to this subscription. Please contact support if you need further help.', 'retainwoo'),
                    ];
                }
                $amount = RetainWoo_Settings::get('retainwoo_discount_amount', 20);
                $type = RetainWoo_Settings::get('retainwoo_discount_type', 'percent');
                $code = $sub->apply_discount($amount, $type);
                if ($code) {
                    update_post_meta($sub->get_id(), '_retainwoo_discount_used', current_time('mysql'));
                    $label = 'percent' === $type ? $amount . '%' : '$' . $amount;
                    $message = sprintf(
                        /* translators: 1: discount label e.g. "20%" 2: coupon code */
                        __('%1$s discount applied! Your coupon code: %2$s. Your subscription will renew at the discounted rate.', 'retainwoo'),
                        $label,
                        $code
                    );
                    return ['success' => true, 'message' => $message, 'code' => $code];
                }
                return ['success' => false, 'message' => __('Could not apply discount.', 'retainwoo')];

            default:
                return ['success' => false, 'message' => 'Unknown offer.'];
        }
    }

    /**
     * Public helper: check eligibility of all offers for a given subscription.
     * Returns an array of [ 'offer_key' => bool ] flags.
     */
    public static function get_eligibility($sub_id)
    {
        $skip_months = (int) RetainWoo_Settings::get( 'retainwoo_skip_cooldown', 3 );
        return [
            'discount' => !get_post_meta($sub_id, '_retainwoo_discount_used', true),
            'skip' => !self::on_cooldown($sub_id, '_retainwoo_skip_used', $skip_months),
            'pause' => !self::on_cooldown($sub_id, '_retainwoo_pause_used', 6),
        ];
    }
}
