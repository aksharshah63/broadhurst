/* global wpforms_save_resume, wpforms_settings */

'use strict';

/**
 * WPForms Save and Resume function.
 *
 * @since 1.0.0
 */
var WPFormsSaveResume = window.WPFormsSaveResume || ( function( document, window, $ ) {

	var $form,
		$formContainer,
		formData,
		submitState,
		currentFormID,
		pageScroll = 75;

	/**
	 * Public functions and properties.
	 *
	 * @since 1.0.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			$( document ).on( 'click', '.wpforms-save-resume-button', app.showSaveResume );
			$( document ).on( 'click', '.wpforms-save-resume-disclaimer-back', app.goBack );
			$( document ).on( 'click', '.wpforms-save-resume-disclaimer-continue', app.showConfirmation );
			$( document ).on( 'click', '.wpforms-save-resume-shortcode-copy', app.copyShortcodeToClipboard );
			$( document ).on( 'change', '.wpforms-field-email input[type=email]', app.validateEmail );
		},

		/**
		 * Show save and resume.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} event Event obj.
		 *
		 * @returns {void}
		 */
		showSaveResume: function( event ) {

			$form          = $( event.target ).closest( 'form.wpforms-form' );
			$formContainer = $form.parent().parent();

			submitState = $form.find( '.wpforms-submit-container' ).is( ':visible' );

			// Scroll to top before showing the screen.
			app.scrollToTop( $form.offset().top - pageScroll, 750 );

			if ( $formContainer.find( '.wpforms-save-resume-disclaimer' ).length >= 1 ) {
				app.showDisclaimer( event );
			} else {
				app.showConfirmation( event );
			}
		},

		/**
		 * Save entry on click.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} $form Form.
		 */
		prepareData: function( $form ) {

			formData      = new FormData( $form.get( 0 ) );
			currentFormID = $form.data( 'formid' );

			formData.append( 'action', 'wpforms_save_resume' );
			formData.append( 'page_url', window.location.href );

			// Include form token if exists.
			if ( $form.data( 'token' ) && $( '.wpforms-token', $form ).length === 0 ) {
				formData.append( 'wpforms[token]', $form.data( 'token' ) );
			}
		},

		/**
		 * Display Disclaimer.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} event Event object.
		 *
		 * @returns {void}
		 */
		showDisclaimer: function( event ) {

			event.preventDefault();

			app.prepareData( $form );
			$form.find( '.wpforms-field-container, .wpforms-submit-container, .wpforms-recaptcha-container, .wpforms-page-indicator, .wpforms-save-resume-expired-message ' ).hide();

			app.hideExpiredMessage();

			$formContainer.find( '.wpforms-save-resume-disclaimer' ).detach().insertAfter( $form ).show();
		},

		/**
		 * Display Confirmation.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} event Event object.
		 */
		showConfirmation: function( event ) {

			event.preventDefault();

			$formContainer.find( '.wpforms-save-resume-disclaimer' ).hide();
			app.prepareData( $form );
			$form.find( '.wpforms-field-container, .wpforms-submit-container, .wpforms-recaptcha-container, .wpforms-page-indicator ' ).hide();

			app.hideExpiredMessage();

			$formContainer.find( '.wpforms-save-resume-confirmation' ).detach().insertAfter( $form ).show();
			app.sendData( event );
		},

		/**
		 * Send the data.
		 *
		 * @since 1.0.0
		 */
		sendData: function( ) {

			if ( ! formData || ! currentFormID ) {
				return;
			}

			app.debug( 'Sending' );
			var args = {
				type       : 'post',
				dataType   : 'json',
				url        : wpforms_save_resume.ajaxurl,
				data       : formData,
				cache      : false,
				contentType: false,
				processData: false,
			};

			// Send the form(s) data via ajax.
			$.ajax( args ).done( function( data ) {

				if ( data.success === false ) {
					return;
				}

				if ( data.data.hash.length > 0 ) {
					app.debug( 'Sent' );
					$formContainer.find( '.wpforms-save-resume-entry-id' ).val( data.data.entry_id );
					$formContainer.find( '.wpforms-save-resume-shortcode' ).val( data.data.hash );
				}
			} );

			formData = new FormData();
		},

		/**
		 * Return the form.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} event Event obj.
		 */
		goBack: function( event ) {

			event.preventDefault();

			var $submitContainer  = $form.find( '.wpforms-submit-container' );

			$formContainer.find( '.wpforms-save-resume-disclaimer' ).hide();

			app.hideExpiredMessage();

			if ( submitState !== false ) {
				$submitContainer.show();
			}

			$form.find( '.wpforms-field-container, .wpforms-recaptcha-container, .wpforms-page-indicator' ).show();
		},

		/**
		 * Copies the shortcode embed code to the clipboard.
		 *
		 * @since 1.0.0
		 */
		copyShortcodeToClipboard: function() {

			var $shortcodeInput = $formContainer.find( '.wpforms-save-resume-shortcode' ),
				$shortcodeCopy = $formContainer.find( '.wpforms-save-resume-shortcode-copy' );

			// Remove disabled attribute, select the text, and re-add disabled attribute.
			$shortcodeInput
				.prop( 'disabled', false )
				.select()
				.prop( 'disabled', true );

			// Copy it.
			document.execCommand( 'copy' );

			var $icon = $shortcodeCopy.find( 'i' );

			// Add visual feedback to copy command.
			$icon.removeClass( 'fa-files-o' ).addClass( 'fa-check' );

			// Reset visual confirmation back to default state after 2.5 sec.
			window.setTimeout( function() {
				$icon.removeClass( 'fa-check' ).addClass( 'fa-files-o' );
			}, 2500 );

			app.debug( 'Copied' );
		},

		/**
		 * Validate email.
		 *
		 * @since 1.0.0
		 *
		 * @param {object} event Event obj.
		 */
		validateEmail: function( event ) {

			event.preventDefault();

			$( $form ).validate( {
				errorClass: 'wpforms-error',
				validClass: 'wpforms-valid',
				rules: {
					email: {
						required: true,
						email: true,
					},
				},
				messages: {
					email: wpforms_settings.val_email,
				},
				submitHandler: function( form ) {
					form.submit();
				},
			} );
		},

		/**
		 * Hide expired entry message.
		 *
		 * @since 1.2.0
		 */
		hideExpiredMessage: function() {

			const $expiredMsg =  $formContainer.find( '.wpforms-save-resume-expired-message' );

			if ( $expiredMsg.length > 0 ) {
				$expiredMsg.hide();
			}
		},

		/**
		 * Scroll to the top of a form.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} position Position (in pixels) to scroll to,
		 * @param {number} duration Animation duration.
		 */
		scrollToTop: function( position, duration ) {

			duration = duration || 1000;
			$( 'html, body' ).animate( { scrollTop: parseInt( position, 10 ) }, { duration: duration } );
		},

		/**
		 * Optional debug messages.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} msg Debug message.
		 */
		debug: function( msg ) {

			if ( window.location.hash && window.location.hash === '#wpformsfadebug' ) {
				console.log( 'WPForms S&R: ' + msg );
			}
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );

// Initialize.
WPFormsSaveResume.init();
