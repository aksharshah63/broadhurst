/**
 * admin.js
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package groups-file-access
 * @since 2.1.0
 * @link https://www.itthinx.com
 */
if ( typeof jQuery !== "undefined" ) {
	jQuery( document ).ready( function( $ ) {
		$( "#file-type-selector" ).on(
			"change",
			function() {
				switch ( this.value ) {
					case "amazon-s3":
						$("#file-field-container").hide();
						$("#amazon-s3-region-field-container").show();
						$("#amazon-s3-bucket-field-container").show();
						$("#amazon-s3-key-field-container").show();
						$("#file-field-container > input").prop( "required", false );
						$("#amazon-s3-region-field-container > select").prop( "required", true );
						$("#amazon-s3-bucket-field-container > input").prop( "required", true );
						$("#amazon-s3-key-field-container > input").prop( "required", true );
						break;
					default:
						$("#file-field-container").show();
						$("#amazon-s3-region-field-container").hide();
						$("#amazon-s3-bucket-field-container").hide();
						$("#amazon-s3-key-field-container").hide();
						$("#file-field-container > input").prop( "required", true );
						$("#amazon-s3-region-field-container > select").prop( "required", false );
						$("#amazon-s3-bucket-field-container > input").prop( "required", false );
						$("#amazon-s3-key-field-container > input").prop( "required", false );
				}
			}
		);
		$( "#file-type-selector" ).trigger( "change" );
	} );
}
