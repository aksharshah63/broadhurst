/* global SignaturePad */
'use strict';

/**
 * WPForms Signature function.
 *
 * @since 1.1.0
 */
var WPFormsSignatures = window.WPFormsSignatures || ( function( document, window, $ ) {

	/**
	 * Private functions and properties.
	 *
	 * @since 1.1.0
	 *
	 * @type {object}
	 */
	var __private = {

		/**
		 * Config contains all configuration properties.
		 *
		 * @since 1.1.0
		 *
		 * @type {object}
		 */
		config: {

			// Window width to help process resize events.
			windowWidth: false,

			// Display ratio (eg retina or standard display).
			pixelRatio: Math.max( window.devicePixelRatio || 1, 1 ),

			// This is a placeholder for setInterval in some use cases.
			watching: false,

			// Time in ms to watch for disabled signatures.
			watchingRate: 300,
		},

		/**
		 * Returns the canvas element from jQuery signature object.
		 *
		 * Also makes necessary adjustments for high-res displays.
		 *
		 * @since 1.1.0
		 *
		 * @param {jQuery} $signature Signature object.
		 *
		 * @returns {object} Return the canvas element.
		 */
		getCanvas: function( $signature ) {

			var canvas = $signature.get( 0 );

			// This fixes issues with high res/retina displays.
			canvas.width  = canvas.offsetWidth * __private.config.pixelRatio;
			canvas.height = canvas.offsetHeight * __private.config.pixelRatio;
			canvas.getContext( '2d' ).scale( __private.config.pixelRatio, __private.config.pixelRatio );

			return canvas;
		},

		/**
		 * Crops a canvas so that all white space is removed.
		 *
		 * @since 1.1.0
		 *
		 * @param {object} canvas Signature canvas.
		 *
		 * @returns {string} Return data URL of the new cropped canvas.
		 */
		cropCanvas: function( canvas ) {

			// First duplicate the canvas to not alter the original.
			var croppedCanvas = document.createElement( 'canvas' ),
				croppedCtx    = croppedCanvas.getContext( '2d' );

			croppedCanvas.width  = canvas.width;
			croppedCanvas.height = canvas.height;
			croppedCtx.drawImage( canvas, 0, 0 );

			// Perform the cropping.
			var w         = croppedCanvas.width,
				h         = croppedCanvas.height,
				pix       = { x:[], y:[] },
				imageData = croppedCtx.getImageData( 0, 0, croppedCanvas.width, croppedCanvas.height ),
				x,
				y,
				n,
				cut,
				index;

			for ( y = 0; y < h; y++ ) {
				for ( x = 0; x < w; x++ ) {
					index = ( y * w + x ) * 4;
					if ( imageData.data[ index + 3 ] > 0 ) {
						pix.x.push( x );
						pix.y.push( y );
					}
				}
			}

			pix.x.sort( function( a, b ) {
				return a - b;
			} );
			pix.y.sort( function( a, b ) {
				return a - b;
			} );

			n   = pix.x.length - 1;
			w   = pix.x[ n ] - pix.x[ 0 ];
			h   = pix.y[ n ] - pix.y[ 0 ];
			cut = croppedCtx.getImageData( pix.x[0], pix.y[0], w, h );

			croppedCanvas.width  = w;
			croppedCanvas.height = h;
			croppedCtx.putImageData( cut, 0, 0 );

			// Return data URL of the new cropped canvas.
			return croppedCanvas.toDataURL();
		},

		/**
		 * Watches signatures that are currently hidden, detects when they
		 * become visible.
		 *
		 * @since 1.1.0
		 */
		watchSignatures: function() {

			var $allLoaded = true;

			$( '.wpforms-signature-canvas' ).each( function() {

				var $signature = $( this );

				if ( ! $signature.hasClass( 'loaded' ) ) {

					$allLoaded = false;

					// Signature is now visible.
					if ( ! $signature.is( ':hidden' ) ) {

						// Since we can see it, lets load it!
						app.loadSignature( $signature ); // eslint-disable-line no-use-before-define
					}
				}
			} );

			// If hidden signatures have been displayed, cease watching.
			if ( $allLoaded ) {
				clearInterval( __private.config.watching );
				__private.config.watching = false;
			}
		},
	};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.1.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.1.0
		 */
		init: function() {

			$( document ).on( 'wpformsReady', app.ready );
		},

		/**
		 * Initialize once the DOM is fully loaded.
		 *
		 * @since 1.1.0
		 */
		ready: function() {

			// Set initial window width to filter for accurate resize events.
			__private.config.windowWidth = $( window ).width();

			// Initialize signature instances.
			app.loadSignatures();

			// Bind clear button to reset signature.
			$( document ).on( 'click', '.wpforms-signature-clear', function( event ) {

				event.preventDefault();

				app.resetSignature( $( this ).parent().find( '.wpforms-signature-canvas' ), true );
			} );

			// Bind window resize to reset signatures.
			$( window ).resize( app.resetSignatures );

			// Enable the visibility watching to check if we have hidden signatures.
			__private.config.watching = setInterval( __private.watchSignatures, __private.config.watchingRate );
		},

		/**
		 * Finds, creates and loads each signature instance.
		 *
		 * @since 1.1.0
		 */
		loadSignatures: function() {

			$( '.wpforms-signature-canvas' ).each( function() {
				app.loadSignature( $( this ) );
			} );
		},

		/**
		 * Creates and loads a single signature instance.
		 *
		 * @since 1.1.0
		 *
		 * @param {jQuery} $signature jQuery signature object.
		 */
		loadSignature: function( $signature ) {

			var $wrap  = $signature.closest( '.wpforms-field-signature' ),
				$input = $wrap.find( '.wpforms-signature-input' ),
				canvas = __private.getCanvas( $signature ),
				color  = $signature.data( 'color' );

			if ( ! $signature.is( ':hidden' ) ) {

				$signature.addClass( 'loaded' );

				// Creates the signature instance.
				var signaturePad = new SignaturePad( canvas, {
					penColor: color,
					onEnd: function() {

						$input.val( __private.cropCanvas( canvas ) ).trigger( 'input change' ).valid();
					},
				} );

				$signature.data( 'signaturePad', signaturePad );
			}
		},

		/**
		 * Reset signatures. This runs when the viewport size is changed.
		 *
		 * @since 1.1.0
		 */
		resetSignatures: function() {

			// If the viewport width has not changed we do not need to reset.
			if ( __private.config.windowWidth === $( window ).width() )  {
				return;
			}

			$( '.wpforms-signature-canvas' ).each( function() {

				app.resetSignature( $( this ), false );
			} );
		},

		/**
		 * Reset the canvas for a signature.
		 *
		 * @since 1.1.0
		 * @since 1.5.0 Added a `clear` parameter.
		 *
		 * @param {jQuery}  $signature jQuery signature object.
		 * @param {boolean} clear      True if you need to clear the canvas.
		 */
		resetSignature: function( $signature, clear ) {

			var $wrap = $signature.closest( '.wpforms-field-signature' ),
				$input = $wrap.find( '.wpforms-signature-input' ),
				signaturePad = $signature.data( 'signaturePad' ),
				value = signaturePad.toDataURL( 'image/svg+xml' );

			if ( clear ) {
				signaturePad.clear();
			} else {

				// Properly scale canvas.
				__private.getCanvas( $signature );
				signaturePad.fromDataURL( value	);
			}

			$input.trigger( 'input change' );

			// Check if signature is hidden.
			if ( $signature.is( ':hidden' ) ) {

				$signature.removeClass( 'loaded' );

				// Enable visibility watching if not already running.
				if ( ! __private.config.watching ) {
					__private.config.watching = setInterval( __private.watchSignatures, __private.config.watchingRate );
				}
			}
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsSignatures.init();
