'use strict';

( function( $ ) {

	var WPFormsPostSubmissions = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			WPFormsPostSubmissions.bindUIActions();

			$( WPFormsPostSubmissions.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {

			WPFormsPostSubmissions.conditionals();
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function() {

			// When a featured image field is configured, configure that file
			// upload field to only accept images.
			$( document ).on( 'change', '#wpforms-panel-field-settings-post_submissions_featured', function() {

				var fieldID = $( this ).find( 'option:selected' ).val();

				if ( fieldID !== '' ) {
					$( '#wpforms-field-option-' + fieldID + '-extensions' ).val( 'jpg,jpeg,png,gif' );
					$( '#wpforms-field-option-' + fieldID + '-max_file_number' ).val( 1 );
				}
			} );
		},

		/**
		 * Show settings only if they are enabled.
		 *
		 * @since 1.4.0
		 */
		conditionals: function() {

			if ( typeof $.fn.conditions === 'undefined' ) {
				return;
			}

			$( '#wpforms-panel-field-settings-post_submissions' ).conditions( {
				conditions: {
					element: '#wpforms-panel-field-settings-post_submissions',
					type: 'checked',
					operator: 'is',
				},
				actions: {
					if: {
						element: '#wpforms-post-submissions-content-block',
						action: 'show',
					},
					else: {
						element: '#wpforms-post-submissions-content-block',
						action: 'hide',
					},
				},
				effect: 'appear',
			} );
		},
	};

	WPFormsPostSubmissions.init();
}( jQuery ) );
