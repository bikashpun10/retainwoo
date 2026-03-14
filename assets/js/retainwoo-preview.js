/**
 * RetainWoo — Admin Popup Preview
 * Renders a live popup preview from saved settings.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.RetainWooPreview || {};
	var s   = cfg.settings || {};
	var str = cfg.strings  || {};

	/**
	 * Build discount label e.g. "Get 20% off — stay!"
	 */
	function discountLabel() {
		var amount = parseInt( s.discount_amount, 10 ) || 20;
		var suffix = ( s.discount_type === 'fixed' ) ? ( '$' + amount + ' off' ) : ( amount + '% off' );
		return 'Get ' + suffix + ' — stay!';
	}

	/**
	 * Build the popup HTML identical to the frontend popup.
	 */
	function buildPopup() {
		var offers = '';

		if ( s.offer_pause === '1' || s.offer_pause === 1 ) {
			offers +=
				'<button class="cs-btn" type="button">' +
					'<span class="cs-btn-icon">⏸</span>' +
					'<span class="cs-btn-content">' +
						'<span class="cs-btn-text">' + str.pause_1 + '</span>' +
						'<span class="cs-btn-desc">' + str.pause_1_desc + '</span>' +
					'</span>' +
				'</button>' +
				'<button class="cs-btn" type="button">' +
					'<span class="cs-btn-icon">⏸</span>' +
					'<span class="cs-btn-content">' +
						'<span class="cs-btn-text">' + str.pause_3 + '</span>' +
						'<span class="cs-btn-desc">' + str.pause_3_desc + '</span>' +
					'</span>' +
				'</button>';
		}

		if ( s.offer_skip === '1' || s.offer_skip === 1 ) {
			offers +=
				'<button class="cs-btn" type="button">' +
					'<span class="cs-btn-icon">⏭</span>' +
					'<span class="cs-btn-content">' +
						'<span class="cs-btn-text">' + str.skip + '</span>' +
						'<span class="cs-btn-desc">' + str.skip_desc + '</span>' +
					'</span>' +
				'</button>';
		}

		if ( s.offer_discount === '1' || s.offer_discount === 1 ) {
			offers +=
				'<button class="cs-btn cs-btn--hl" type="button">' +
					'<span class="cs-btn-icon">🎁</span>' +
					'<span class="cs-btn-content">' +
						'<span class="cs-btn-text">' + discountLabel() + '</span>' +
					'</span>' +
					'<span class="cs-btn-tag">POPULAR</span>' +
				'</button>';
		}

		if ( ! offers ) {
			offers = '<p style="color:#888;text-align:center;padding:12px 0;">No offers enabled. Turn on at least one offer in settings.</p>';
		}

		return (
			'<div id="cs-overlay" class="cs-visible" style="position:relative;inset:auto;background:none;backdrop-filter:none;padding:0;">' +
				'<div id="cs-popup">' +
					'<div class="cs-popup-header">' +
						'<div class="cs-popup-emoji">🛡️</div>' +
						'<h2>' + str.headline + '</h2>' +
						'<p>' + str.subheadline + '</p>' +
					'</div>' +
					'<div class="cs-popup-body">' +
						'<div class="cs-offers">' + offers + '</div>' +
						'<div class="cs-divider">or</div>' +
						'<button id="cs-cancel-anyway" type="button">' + str.cancel_anyway + '</button>' +
					'</div>' +
					'<div class="cs-trust">' +
						'<span class="cs-trust-dot"></span>' +
						'WooCommerce · WebToffee · YITH · SUMO' +
					'</div>' +
				'</div>' +
			'</div>'
		);
	}

	/**
	 * Open the preview panel.
	 */
	function openPreview() {
		$( '#cs-preview-popup-wrap' ).html( buildPopup() );
		$( '#cs-preview-overlay' ).addClass( 'cs-preview-visible' );
		$( '#cs-preview-btn' ).addClass( 'cs-preview-active' );
	}

	/**
	 * Close the preview panel.
	 */
	function closePreview() {
		$( '#cs-preview-overlay' ).removeClass( 'cs-preview-visible' );
		$( '#cs-preview-btn' ).removeClass( 'cs-preview-active' );
	}

	/**
	 * Toggle the preview panel.
	 */
	function togglePreview() {
		if ( $( '#cs-preview-overlay' ).hasClass( 'cs-preview-visible' ) ) {
			closePreview();
		} else {
			openPreview();
		}
	}

	$( document ).ready( function () {
		$( document ).on( 'click', '#cs-preview-btn', togglePreview );
		$( document ).on( 'click', '#cs-preview-close', closePreview );
		// Close when clicking the overlay backdrop (outside the modal).
		$( document ).on( 'click', '#cs-preview-overlay', function ( e ) {
			if ( e.target === this ) { closePreview(); }
		} );
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) { closePreview(); }
		} );
		// Prevent popup button clicks from doing anything in preview.
		$( document ).on( 'click', '#cs-preview-popup-wrap .cs-btn, #cs-preview-popup-wrap #cs-cancel-anyway', function ( e ) {
			e.preventDefault();
		} );
	} );

}( jQuery ) );
