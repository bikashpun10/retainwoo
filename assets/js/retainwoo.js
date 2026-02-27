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
			'<span class="cs-popup-emoji"><svg width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 4L6 10V22C6 31.4 13.1 40.1 22 42C30.9 40.1 38 31.4 38 22V10L22 4Z" fill="white" fill-opacity="0.2" stroke="white" stroke-width="2" stroke-linejoin="round"/><path d="M16 22L20 26L28 18" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>' +
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
