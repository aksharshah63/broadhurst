<?php
/**
* boot.php
*
* Copyright (c) "kento" Karim Rahimpur www.itthinx.com
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
* @author Karim Rahimpur
* @package groups-file-access
* @since groups-file-access 1.0.0
*/

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GFA_ADMIN_LIB', GFA_DIR . '/lib/admin' );
define( 'GFA_FILE_LIB', GFA_DIR . '/lib/file' );
define( 'GFA_UTY_LIB', GFA_DIR . '/lib/uty' );
define( 'GFA_VIEWS_LIB', GFA_DIR . '/lib/views' );

/**
 * basename() alternative - PHP's basename() will remove multibyte
 * characters that prefix the $path.
 *
 * @param string $path
 *
 * @return string
 */
function gfa_basename( $path ) {
	// unify
	$path = str_replace( "\\", "/", $path );
	// last /
	if ( function_exists( 'mb_strrpos' ) ) {
		$k = mb_strrpos( $path, "/" );
		if ( $k !== false ) {
			$path = mb_substr( $path, $k + 1 );
		}
	} else {
		$k = strrpos( $path, "/" );
		if ( $k !== false ) {
			$path = substr( $path, $k + 1 );
		}
	}
	return $path;
}

/**
 * Returns the path to the plugin's dedicated uploads directory.
 *
 * @uses wp_upload_dir()
 *
 * @link https://developer.wordpress.org/reference/functions/wp_upload_dir/
 *
 * @since 2.3.0
 *
 * @return string|null
 */
function gfa_uploads_dir() {
	$gfa_uploads_dir = null;
	$upload_dir = wp_upload_dir();
	if (
		is_array( $upload_dir ) &&
		isset( $upload_dir['basedir'] )
	) {
		if (
			!isset( $upload_dir['error'] ) ||
			$upload_dir['error'] === false
		) {
			$basedir = trailingslashit( $upload_dir['basedir'] );
			$gfa_uploads_dir = $basedir . 'groups-file-access';
		} else {
			/* translators: %1$s fixed name, %2$s an error message */
			error_log(
				sprintf(
					esc_html__( '%1$s encountered an error while obtaining the canonical uploads directory: %2$s', 'groups-file-access' ),
					'Groups File Access',
					esc_html( $upload_dir['error'] )
				)
			);
		}
	} else {
		/* translators: %s fixed name */
		error_log(
			sprintf(
				esc_html__( '%s could not obtain the canonical uploads directory', 'groups-file-access' ),
				'Groups File Access'
			)
		);
	}
	if ( $gfa_uploads_dir === null ) {
		$gfa_uploads_dir = untrailingslashit( WP_CONTENT_DIR ) . '/uploads/groups-file-access';
		if ( is_multisite() ) {
			$current_blog_id = get_current_blog_id();
			if ( $current_blog_id > 1 ) {
				$gfa_uploads_dir = untrailingslashit( WP_CONTENT_DIR ) . "/uploads/sites/{$current_blog_id}/groups-file-access";
			}
		}
	}
	$gfa_uploads_dir = apply_filters( 'groups_file_access_uploads_dir', $gfa_uploads_dir );
	return $gfa_uploads_dir;
}

define( 'GFA_UPLOADS_DIR', gfa_uploads_dir() );

require_once GFA_CORE_LIB . '/class-groups-file-access.php';
