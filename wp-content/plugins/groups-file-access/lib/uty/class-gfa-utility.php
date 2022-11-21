<?php
/**
 * class-gfa-utility.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-file-access
 * @since groups-file-access 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utiltiy functions.
 */
class GFA_Utility {

	/**
	* Filters mail header injection, html, ...
	*
	* @param string $unfiltered_value
	*
	* @return string
	*/
	public static function filter( $unfiltered_value ) {
		$mail_filtered_value = preg_replace( '/(%0A|%0D|content-type:|to:|cc:|bcc:)/i', '', $unfiltered_value );
		return stripslashes( wp_filter_nohtml_kses( self::filter_xss( trim( strip_tags( $mail_filtered_value ) ) ) ) );
	}

	/**
	 * Filter xss
	 *
	 * @link https://api.drupal.org/api/drupal/core!includes!common.inc/function/filter_xss/8
	 *
	 * @param string $string input
	 *
	 * @return string filtered string
	 */
	public static function filter_xss( $string ) {
		$string = str_replace( chr( 0 ), '', $string );
		$string = preg_replace( '%&\s*\{[^}]*(\}\s*;?|$)%', '', $string );
		$string = str_replace( '&', '&amp;', $string );
		$string = preg_replace( '/&amp;#([0-9]+;)/', '&#\1', $string );
		$string = preg_replace( '/&amp;#[Xx]0*((?:[0-9A-Fa-f]{2})+;)/', '&#x\1', $string );
		$string = preg_replace( '/&amp;([A-Za-z][A-Za-z0-9]*;)/', '&\1', $string );
		return preg_replace( '%( <(?=[^a-zA-Z!/]) | <!--.*?--> | <[^>]*(>|$) | > )%x', '', $string );
	}

	/**
	 * Tries to obtain the MIME type of the file at the given path.
	 * Returns null if the MIME type could not be obtained.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function get_mime_type( $path ) {

		$mime_type = null;

		if ( ( $mime_type === null ) && function_exists( 'finfo_file' ) ) {
			if ( $finfo = finfo_open( FILEINFO_MIME_TYPE ) ) {
				$mime_type = finfo_file( $finfo, $path );
				finfo_close( $finfo );
			}
		}

		if ( ( $mime_type === null || $mime_type === false ) && function_exists( 'mime_content_type' ) ) {
			$mime_type = mime_content_type( $path );
		}

		if ( $mime_type === null || $mime_type === false ) {
			$filename = sanitize_file_name( wp_basename( $path ) );
			$check = wp_check_filetype_and_ext( $path, $filename );
			if ( !empty( $check['type'] ) && $check['type'] !== false ) {
				$mime_type = $check['type'];
			}
		}

		if ( $mime_type === null || $mime_type === false ) {
			if ( ! class_exists( 'getID3' ) ) {
				include_once ABSPATH . WPINC . '/ID3/getid3.php';
			}
			if ( class_exists( 'getID3' ) ) {
				$getID3 = new getID3();
				$info = $getID3->analyze( $path );
				if ( !empty( $info['mime_type'] ) ) {
					$mime_type = $info['mime_type'];
				}
			}
		}

		return $mime_type;
	}

}
