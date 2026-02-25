=== RetainWoo - Subscription Retention ===
Contributors:      retainwoo
Tags:              woocommerce, subscriptions, retention, cancel, churn
Requires at least: 5.8
Tested up to:      6.9
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI:        https://wordpress.org/plugins/retainwoo

Stop losing subscribers. Show smart retention offers the moment someone tries to cancel — pause, skip, or discount — right inside WooCommerce.

== Description ==

**RetainWoo intercepts WooCommerce subscription cancellations and shows a beautiful popup with retention offers before the subscriber leaves.**

Most stores lose subscribers silently — the customer clicks cancel, the subscription ends, and no one ever finds out why. RetainWoo stops that from happening.

When a subscriber clicks the cancel button, a polished popup appears instantly with three retention options:

* **Pause** — Let them pause for 1 or 3 months instead of cancelling
* **Skip** — Skip their next payment to give them breathing room (can only be used once every 3 months)
* **Discount** — Offer a percentage or fixed discount to stay

If they still cancel, RetainWoo automatically sends a **win-back email** with a unique coupon code — giving you a second chance to recover that subscriber.

Everything is tracked in a clean dashboard so you can see exactly how much revenue you're saving.

= Why RetainWoo? =

* **Offer first, not a survey** — Competitors show a survey before the offer. RetainWoo shows the offer immediately, which gets higher acceptance.
* **Works with 4 subscription plugins** — WooCommerce Subscriptions, WebToffee, YITH, and SUMO all auto-detected.
* **5-minute setup** — Install, activate, done. No complex configuration required.
* **Built-in win-back email** — Other plugins require Klaviyo or Mailchimp. RetainWoo sends beautiful win-back emails automatically.
* **Self-contained design** — The popup looks great on every theme without any CSS conflicts.

= Features =

* Intercepts subscription cancel clicks across all subscription pages
* Three retention offers: pause (1 or 3 months), skip next payment, percentage or fixed discount
* Automatic win-back email with unique coupon code
* Dashboard showing cancellations saved, revenue saved, save rate, and offer breakdown
* Customizable headline and subheadline text
* Toggle each offer type on or off
* Configure discount amount and type
* Compatible with WooCommerce Subscriptions, WebToffee Subscriptions, YITH WooCommerce Subscriptions, and SUMO Subscriptions

= Supported Subscription Plugins =

* WooCommerce Subscriptions (official)
* Subscriptions for WooCommerce by WebToffee
* YITH WooCommerce Subscriptions
* SUMO Subscriptions

= Privacy =

RetainWoo does not collect, share, or transmit any personal data to external servers. All data is stored in your own WordPress database. The win-back email is sent using your own WordPress mail (wp_mail).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/retainwoo/`, or install through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Make sure you have a supported subscription plugin active (WooCommerce Subscriptions, WebToffee, YITH, or SUMO).
4. The plugin is active immediately — go to **RetainWoo → Settings** to customize your offers.
5. Go to **RetainWoo → Dashboard** to track performance.

== Frequently Asked Questions ==

= Which subscription plugins are supported? =

RetainWoo works with WooCommerce Subscriptions (official), Subscriptions for WooCommerce by WebToffee, YITH WooCommerce Subscriptions, and SUMO Subscriptions. The correct plugin is detected automatically.

= Does the popup appear for all subscription cancellations? =

Yes — the popup appears whenever a logged-in customer clicks any cancel button on their subscription. It works on the My Account Subscriptions page and individual subscription pages.

= What happens when a customer accepts an offer? =

**Pause:** The subscription status is set to On Hold and a scheduled event automatically resumes it after the selected period.
**Skip:** The next payment date is pushed forward by one billing cycle.
**Discount:** A unique single-use coupon is created and applied to the subscription automatically.

= What happens when a customer cancels anyway? =

The cancellation proceeds normally. If win-back email is enabled, a scheduled email is sent after the configured delay with a unique discount coupon to encourage reactivation.

= Will this slow down my site? =

No. The popup CSS and JavaScript are only loaded on WooCommerce account pages. The total added weight is under 15KB.

= Does it work with page builders like Elementor or Divi? =

Yes. The popup is injected directly into the page body and is independent of your theme or page builder.

= Can I customize the popup text? =

Yes. Go to RetainWoo → Settings to change the headline, subheadline, discount amount, and win-back email subject.

= Is this GDPR compliant? =

Yes. RetainWoo does not send any data to external servers. Win-back emails are sent using your WordPress installation's own email system. No third-party services are involved.

== Screenshots ==

1. The retention popup shown when a subscriber clicks cancel
2. The RetainWoo dashboard showing saves, revenue, and save rate
3. The settings page with all configuration options

== Changelog ==

= 1.0.0 =
* Initial release
* Retention popup with pause, skip, and discount offers
* Win-back email system with automatic coupon generation
* Dashboard with 30-day performance stats
* Support for WooCommerce Subscriptions, WebToffee, YITH, and SUMO

== Upgrade Notice ==

= 1.0.0 =
Initial release.
