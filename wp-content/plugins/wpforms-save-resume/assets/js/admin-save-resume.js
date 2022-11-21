'use strict';

jQuery( function( $ ) {

	/**
	 * Copies resume link to the clipboard.
	 *
	 * @since 1.2.0
	 *
	 * @param {object} event Event obect.
	 */
	function copyToClipBoard( event ) {

		event.preventDefault();

		const link          = $( this ),
			linkText        = link.attr( 'href' ),
			linkSuccessText = link.data( 'success-copy' ),
			linkTitle       = link.text();

		if ( link.data( 'disabled' ) ) {
			return;
		}

		// Use Clipboard API for modern browsers and HTTPS connections, in other cases use old-fashioned way.
		if ( navigator.clipboard  ) {
			navigator.clipboard.writeText( linkText );
		} else {
			let $temp = $( '<input>' );
			$( 'body' ).append( $temp );
			$temp.val( linkText ).select();
			document.execCommand( 'copy' );
			$temp.remove();
		}

		$( this ).find( 'strong' ).text( linkSuccessText );
		link.data( 'disabled', true );

		window.setTimeout( function() {

			link.find( 'strong' ).text( linkTitle );
			link.data( 'disabled', false );
		}, 3000 );
	}

	$( document ).on( 'click', '.wpforms-entry-copy-link', copyToClipBoard );
	$( document ).ready( function() {

		if ( typeof jQuery.fn.tooltipster === 'undefined' ) {
			return;
		}


		jQuery( '.wpforms-save-resume-help-tooltip' ).tooltipster( {
			contentAsHTML: true,
			position: 'left',
			maxWidth: 300,
			multiple: true,
			interactive: true,
			debug: false,
			IEmin: 11,
		} );

	} );
} );
