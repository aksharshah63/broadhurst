<?php
/**
 * class-gfa-file-upload.php
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

/**
 * GFA file upload utilities
 */
class GFA_File_Upload {

	/**
	 * Returns an acceptable filename after replacing special characters and sanitizing the string.
	 *
	 * @param string $filename
	 * @param string $in_charset
	 *
	 * @return string
	 */
	public static function filename_filter( $filename, $in_charset = "UTF-8" ) {
		// @since 1.9.0 Using sanitize_file_name() instead as filenames with international characters could produce ...
		// PHP Notice:  iconv(): Detected an illegal character in input string
		// ... and the $filename ending up as false instead of a sensible string.
		// Also using "ASCII//TRANSLIT//IGNORE" is not useful as it would strip off the unrecognized characters leaving
		// us with a almost empty string if all characters are unreqcognized, as would happen for example with filenames in Greek.
		//
		// if ( function_exists( 'iconv' ) ) {
		// 	$filename = iconv( $in_charset, "ASCII//TRANSLIT", $filename );
		// }
		// $filename = preg_replace( "/[^a-zA-Z0-9_+ -.]/", "", $filename );
		// $filename = preg_replace( "/[_+ -]+/", "-", $filename );
		$filename = sanitize_file_name( $filename );
		return $filename;
	}

	/**
	 * Filter the path.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function path_filter( $path ) {
		return str_replace( "\\", "/", $path );
	}

	/**
	 * Check uploads folder
	 *
	 * @param string $gfa_uploads_dir
	 *
	 * @return boolean
	 */
	public static function check_uploads( $gfa_uploads_dir ) {
		if ( !file_exists( $gfa_uploads_dir ) ) {
			mkdir( $gfa_uploads_dir, 0755, true );
		}
		return file_exists( $gfa_uploads_dir );
	}

	/**
	 * Check .htaccess
	 *
	 * @param string $gfa_uploads_dir
	 *
	 * @return boolean
	 */
	public static function check_htaccess( $gfa_uploads_dir ) {
		if ( !file_exists( $gfa_uploads_dir . '/.htaccess' ) ) {
			file_put_contents( $gfa_uploads_dir . '/.htaccess', 'deny from all' );
		}
		return file_exists( $gfa_uploads_dir . '/.htaccess' );
	}

	/**
	 * Check index.html
	 *
	 * @param string $gfa_uploads_dir
	 *
	 * @return boolean
	 */
	public static function check_index( $gfa_uploads_dir ) {
		if ( !file_exists( $gfa_uploads_dir . '/index.html' ) ) {
			file_put_contents( $gfa_uploads_dir . '/index.html', '' );
		}
		return file_exists( $gfa_uploads_dir . '/index.html' );
	}

	/**
	 * Get the upload limit.
	 *
	 * @return int bytes
	 */
	public static function get_upload_limit() {
		$n = 0;
		if ( $file_uploads = ini_get( 'file_uploads' ) ) {
			$upload_max_filesize = self::machine_bytes( ini_get( 'upload_max_filesize' ) );
			$post_max_size =  self::machine_bytes( ini_get( 'post_max_size' ) );
			$n = min( array( $upload_max_filesize, $post_max_size ) );
		}
		return $n;
	}

	/**
	 * Bytes for humans.
	 *
	 * @param int $bytes
	 *
	 * @return string
	 */
	public static function human_bytes( $bytes ) {
		$bytes = intval( $bytes );
		$p = 100.0;
		$kb = intval( $bytes * $p / 1024 ) / $p;
		$mb = intval( $kb * $p / 1024 ) / $p;
		$gb = intval( $mb * $p / 1024 ) / $p;
		$tb = intval( $gb * $p / 1024 ) / $p;
		if ( $tb >= 1 ) {
			$bytes = $tb . " TB";
		} else if ( $gb >= 1 ) {
			$bytes = $gb . " GB";
		} else if ( $mb >= 1 ) {
			$bytes = $mb . " MB";
		} else if ( $kb >= 1 ) {
			$bytes = $kb . " KB";
		} else {
			$bytes .= " bytes";
		}
		return $bytes;
	}

	/**
	 * Bytes for machines.
	 *
	 * @param string $bytes
	 *
	 * @return int
	 */
	public static function machine_bytes( $bytes ) {
		$bytes = (string) $bytes;
		$f = array( '', 'K', 'M', 'G', 'T', 'P' );
		$s = count( $f );
		$result = false;
		for ( $i = 1; $i < $s; $i++ ) {
			if ( stripos( $bytes, $f[$i] ) !== false ) {
				$result = intval( str_ireplace( $f[$i], '', $bytes ) ) * pow( 1024, $i );
				break;
			}
		}
		if ( $result === false ) {
			$result = intval( $bytes );
		}
		return $result;
	}
}
