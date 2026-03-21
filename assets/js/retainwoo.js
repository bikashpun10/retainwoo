/**
 * RetainWoo Frontend Script
 *
 * Intercepts subscription cancellation clicks and shows retention offers.
 *
 * @package RetainWoo
 */

(function ($) {
	"use strict";

	var CS           = window.RetainWoo || {};
	var S            = CS.settings || {};
	var T            = CS.strings || {};
	var currentSubId = null;
	var cancelHref   = null;

	// Build popup HTML.
	function buildPopup() {
		var offers = "";

		if (S.offer_pause) {
			offers += makeBtn( "pause_1", "⏸", T.pause_1, false, null, T.pause_1_desc );
			offers += makeBtn( "pause_3", "⏸", T.pause_3, false, null, T.pause_3_desc );
		}
		if (S.offer_skip) {
			offers += makeBtn( "skip", "⏭", T.skip, false, null, T.skip_desc );
		}
		if (S.offer_discount) {
			offers += makeBtn( "discount", "🎁", T.discount, true, "MOST POPULAR", T.discount_desc );
		}

		return (
		'<div id="cs-overlay">' +
		'<div id="cs-popup">' +

			// Header.
			'<div class="cs-popup-header">' +
			'<button id="cs-close" aria-label="Close">&times;</button>' +
			'<span class="cs-popup-emoji"><svg width="44" height="44" viewBox="0 0 128 128" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100.142 77.8253C96.5439 81.5453 92.5865 84.5831 88.4846 87.4369C87.4646 88.1465 86.7226 88.2019 85.5982 87.4794C79.3141 83.442 73.9166 78.3708 69.1926 72.467C67.6219 70.504 67.6158 70.5216 68.9421 68.4246C70.0363 66.6947 71.1093 64.9501 72.3479 62.9612C73.5609 64.771 74.4945 66.4115 75.6266 67.9018C78.5512 71.7519 82.0964 74.87 85.852 77.7567C86.4454 78.2128 87.051 78.5285 87.7681 77.9795C92.5477 74.3194 97.102 70.4214 99.8794 64.7527C101.325 61.8022 101.515 58.7771 99.8774 55.8183C97.9279 52.2955 93.8689 51.465 90.6547 53.8329C89.2671 54.8552 88.3861 56.3807 87.2318 57.5559C86.4284 57.5026 86.1988 56.8473 85.8282 56.3962C84.837 55.1898 83.832 54 82.4528 53.2662C79.66 51.7803 76.5777 52.5401 74.4911 55.2676C71.7788 58.8132 69.7663 62.8509 67.3417 66.6005C64.8567 70.4437 62.5058 74.379 59.5294 77.8529C54.8302 83.3376 49.0866 86.6444 42.0133 86.8542C30.5086 87.1955 22.0208 79.0082 19.7136 69.3028C16.4485 55.5681 24.7048 42.5148 38.0032 40.2975C43.5819 39.3673 48.8102 40.638 53.6172 43.8371C56.0909 45.4834 55.4876 45.6419 57.6058 43.5267C57.9079 43.225 58.277 42.9973 58.9878 42.4465C59.9472 47.8858 60.8535 53.0243 61.8439 58.6396C56.6516 57.2667 51.8991 56.0102 47.0509 54.7283C47.597 53.069 49.1059 52.5621 49.6947 51.0886C47.8981 49.4546 45.7444 48.6919 43.4492 48.3002C33.7689 46.6485 24.9256 56.4403 27.0147 66.4813C29.2227 77.094 40.8356 81.9912 49.631 76.0024C52.9579 73.7372 55.3388 70.5216 57.5858 67.1656C61.1559 61.8335 64.1543 56.1099 67.8657 50.8699C72.1797 44.779 79.6494 42.3449 86.073 45.9874C87.0157 46.5219 87.7127 46.0576 88.4942 45.6939C94.7048 42.8036 101.929 44.5089 105.741 50.2291C109.749 56.2444 109.726 62.7563 106.613 69.2238C105.042 72.4887 102.626 75.1259 100.142 77.8253Z" fill="white"/></svg></span>' +
			'<h2>' + esc( T.headline ) + '</h2>' +
			'<p>' + esc( T.subheadline ) + '</p>' +
			'</div>' +

			// Body.
			'<div class="cs-popup-body">' +
			'<div class="cs-offers">' + offers + '</div>' +
			'<div class="cs-msg"></div>' +
			'<div class="cs-divider">or</div>' +
			'<button id="cs-cancel-anyway">' + esc( T.cancel_anyway ) + '</button>' +
			'</div>' +

			// Trust footer.
			'<div class="cs-trust">' +
			'<span class="cs-trust-dot"></span>' +
			'Your subscription is secure — changes take effect immediately' +
			'</div>' +

		'</div>' +
		'</div>'
		);
	}

	function makeBtn(offer, icon, label, highlight, tag, desc) {
		var cls      = "cs-btn" + (highlight ? " cs-btn--hl" : "");
		var tagHtml  = tag
		? '<span class="cs-btn-tag">' + tag + '</span>'
		: '';
		var descHtml = (desc && desc.length)
		? '<span class="cs-btn-desc">' + esc( desc ) + '</span>'
		: '';
		return (
		'<button class="' + cls + '" data-offer="' + offer + '">' +
		'<span class="cs-btn-icon">' + icon + '</span>' +
		'<span class="cs-btn-content">' +
			'<span class="cs-btn-text">' + esc( label ) + '</span>' +
			descHtml +
		'</span>' +
		tagHtml +
		'</button>'
		);
	}

	function esc(str) {
		return $( "<div>" ).text( str || "" ).html();
	}

	// Intercept cancel buttons.
	function intercept() {
		var selectors = CS.selectors || 'a[href*="cancel"]';

		$( document ).on(
			"click",
			selectors,
			function (e) {
				var $link = $( this );
				var href  = $link.attr( "href" ) || "";
				var id    = extractSubId( href, $link );

				if ( ! id) {
					return; // Can't find sub ID — let it proceed.
				}

				e.preventDefault();
				e.stopImmediatePropagation();

				currentSubId = id;
				cancelHref   = href;

				showPopup();
				trackEvent( "popup_shown" );
			}
		);
	}

	function extractSubId(href, $link) {
		var patterns              = [
			/[?&]subscription_id=(\d+)/,  // WCS.
			/ [ ? & ]sub_id       = (\d + ) / ,           // WebToffee.
			/ [ ? & ]subscription = (\d + ) / ,     // YITH.
			/ [ ? & ]sumo_sub_id  = (\d + ) / ,      // SUMO.
		];

		var patternsLen = patterns.length;
		for (var i = 0; i < patternsLen; i++) {
			var m = href.match( patterns[i] );
			if (m) {
				return m[1];
			}
		}

		// Try data attribute on parent row.
		var rowId = $link.closest( "tr, .subscription-actions, [data-subscription-id]" )
					.data( "subscription-id" );
		if (rowId) {
			return rowId;
		}

		return null;
	}

	// Show popup.
	function showPopup() {
		if ( ! $( "#cs-overlay" ).length) {
			$( "body" ).append( buildPopup() );
			bindEvents();
		}
		resetState();
		$( "#cs-overlay" ).addClass( "cs-visible" );
		$( "body" ).addClass( "cs-lock" );
		// Focus trap.
		setTimeout(
			function () {
				$( "#cs-close" ).focus(); },
			100
		);

		// Ask server which offers are still eligible (discount/check cooldowns).
		if (currentSubId) {
			$.post(
				CS.ajaxurl,
				{
					action: "retainwoo_check_offers",
					nonce:  CS.nonce,
					sub_id: currentSubId,
				},
				function (res) {
					if (res.success && res.data && res.data.eligibility) {
						var elig = res.data.eligibility;
						if (elig.discount === false) {
							$( ".cs-btn[data-offer='discount']" ).closest( ".cs-btn" ).hide();
						}
						if (elig.skip === false) {
							$( ".cs-btn[data-offer='skip']" ).closest( ".cs-btn" ).hide();
						}
						if (elig.pause === false) {
							$( ".cs-btn[data-offer^='pause']" ).closest( ".cs-btn" ).hide();
						}
					}
				}
			);
		}
	}

	// Hide popup.
	function hidePopup() {
		$( "#cs-overlay" ).removeClass( "cs-visible" );
		$( "body" ).removeClass( "cs-lock" );
	}

	function resetState() {
		$( ".cs-msg" ).removeClass( "cs-ok cs-err" ).text( "" ).hide();
		$( ".cs-btn" ).prop( "disabled", false ).removeClass( "cs-loading" );
		$( ".cs-offers, #cs-cancel-anyway, .cs-divider" ).show();
	}

	// Bind events.
	function bindEvents() {
		// Close on X or overlay click.
		$( document ).on( "click", "#cs-close", hidePopup );
		$( document ).on(
			"click",
			"#cs-overlay",
			function (e) {
				if (e.target.id === "cs-overlay") {
					hidePopup();
				}
			}
		);

		// Escape key.
		$( document ).on(
			"keydown.retainwoo",
			function (e) {
				if (e.key === "Escape") {
					hidePopup();
				}
			}
		);

		// Offer click.
		$( document ).on(
			"click",
			".cs-btn",
			function () {
				var $btn     = $( this );
				var offer    = $btn.data( "offer" );
				var $text    = $btn.find( ".cs-btn-text" );
				var origText = $text.text();

				$text.text( T.processing || "Just a moment..." );
				$btn.addClass( "cs-loading" ).prop( "disabled", true );

				$.ajax(
					{
						url:  CS.ajaxurl,
						type: "POST",
						data: {
							action: "retainwoo_accept_offer",
							nonce:  CS.nonce,
							offer:  offer,
							sub_id: currentSubId,
						},
						success: function (res) {
							if (res.success) {
								showMsg( "✅ " + (res.data.message || T.success), true );
								$( ".cs-offers, #cs-cancel-anyway, .cs-divider" ).hide();
								setTimeout(
									function () {
										hidePopup();
										location.reload();
									},
									2800
								);
							} else {
								showMsg( "❌ " + (res.data.message || "Something went wrong."), false );
								$text.text( origText );
								$btn.prop( "disabled", false ).removeClass( "cs-loading" );
							}
						},
						error: function () {
							showMsg( "❌ Network error. Please try again.", false );
							$text.text( origText );
							$btn.prop( "disabled", false ).removeClass( "cs-loading" );
						},
					}
				);
			}
		);

		// Cancel anyway.
		$( document ).on(
			"click",
			"#cs-cancel-anyway",
			function () {
				if (cancelHref) {
					window.location.href = cancelHref;
				}
			}
		);
	}

	function showMsg(msg, ok) {
		$( ".cs-msg" )
		.removeClass( "cs-ok cs-err" )
		.addClass( ok ? "cs-ok" : "cs-err" )
		.text( msg )
		.show();
	}

	// Track events.
	function trackEvent(event) {
		if ( ! currentSubId) {
			return;
		}
		$.post(
			CS.ajaxurl,
			{
				action: "retainwoo_track",
				nonce:  CS.nonce,
				sub_id: currentSubId,
				event:  event,
			}
		);
	}

	// Init.
	$( document ).ready(
		function () {
			intercept();
		}
	);

})( jQuery );
