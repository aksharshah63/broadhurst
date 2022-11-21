<?php
/**
 * class-gfa-file-renderer.php
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
 * @since groups-file-access 2.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Amazon S3 file renderer.
 */
class GFA_File_Renderer_S3 extends GFA_File_Renderer {

	/**
	 * Probe file, send 200 header on success or 404 header on failure.
	 *
	 * @param string $file
	 * @param string $base_path
	 *
	 * @return boolean successful?
	 */
	public static function probe( $file, $base_path ) {

		global $wpdb;

		$result = false;

		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ) {
			$protocol = 'HTTP/1.0';
		}

		$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
		$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
		$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
		$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';
		if ( $amazon_s3 && !empty( $amazon_s3_access_key ) && !empty( $amazon_s3_secret_key ) ) {
			$type   = '';
			$region = '';
			$bucket = '';
			$key    = '';
			$file_meta_table = _groups_get_tablename( 'file_meta' );
			$file_metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_meta_table WHERE file_id = %d", intval( $file->file_id ) ) );
			foreach ( $file_metas as $file_meta ) {
				$meta_key = $file_meta->meta_key;
				$meta_value = $file_meta->meta_value;
				switch ( $meta_key ) {
					case 'type':
						$type = $meta_value;
						break;
					case 'region':
						$region = $meta_value;
						break;
					case 'bucket':
						$bucket = $meta_value;
						break;
					case 'key':
						$key = $meta_value;
						break;
				}
			}

			if ( !empty( $region ) && !empty( $bucket ) && !empty( $key ) ) {
				require_once GFA_AWS_LIB . '/aws-autoloader.php';
				try {
					$s3client = new S3Client( array(
						'version' => 'latest',
						'region'  => $region,
						'credentials' => array(
							'key' => $amazon_s3_access_key,
							'secret' => $amazon_s3_secret_key,
						)
					) );
					$object_url = $s3client->getObjectUrl( $bucket, $key );
					$result = true;
				} catch ( Exception $ex ) {
					header( "$protocol 503 Service Unavailable" );
					error_log( sprintf(
						'Groups File Access has encountered an issue while probing the file #%d via Amazon S3: %s',
						$file->file_id,
						wp_kses_post( $ex->getMessage() )
					) );
				}
			} else {
				header( "$protocol 503 Service Unavailable" );
				error_log( sprintf(
					'Groups File Access has insufficient information to probe the file #%d via Amazon S3',
					$file->file_id
				) );
			}
		} else {
			header( "$protocol 503 Service Unavailable" );
			error_log( sprintf(
				'Groups File Access is not appropriately configured to probe the file #%d via Amazon S3',
				$file->file_id
			) );
		}
		return $result;
	}

	/**
	 * Serve file.
	 *
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
	 *
	 * @param string $file
	 * @param string $base_path
	 *
	 * @return int|false bytes read or false on failure
	 */
	public static function serve( $file, $base_path ) {

		global $wpdb;

		$result = false;

		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ) {
			$protocol = 'HTTP/1.0';
		}

		$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
		$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
		$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
		$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';
		$amazon_s3_expires    = isset( $options[Groups_File_Access::AMAZON_S3_EXPIRES] ) ? $options[Groups_File_Access::AMAZON_S3_EXPIRES] : Groups_File_Access::AMAZON_S3_EXPIRES_DEFAULT;
		$amazon_s3_expires_alt = apply_filters( 'groups_file_access_amazon_s3_expires', $amazon_s3_expires, intval( $file->file_id ) );
		if ( is_numeric( $amazon_s3_expires_alt ) ) {
			$amazon_s3_expires_alt = intval( $amazon_s3_expires_alt );
			if ( $amazon_s3_expires_alt < Groups_File_Access::AMAZON_S3_EXPIRES_MIN ) {
				$amazon_s3_expires_alt = Groups_File_Access::AMAZON_S3_EXPIRES_MIN;
			}
			$amazon_s3_expires = $amazon_s3_expires_alt;
		}
		$amazon_s3_redirect = isset( $options[Groups_File_Access::AMAZON_S3_REDIRECT] ) ? $options[Groups_File_Access::AMAZON_S3_REDIRECT] : Groups_File_Access::AMAZON_S3_REDIRECT_DEFAULT;

		if ( $amazon_s3 && !empty( $amazon_s3_access_key ) && !empty( $amazon_s3_secret_key ) ) {

			$type   = '';
			$region = '';
			$bucket = '';
			$key    = '';
			$file_meta_table = _groups_get_tablename( 'file_meta' );
			$file_metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_meta_table WHERE file_id = %d", intval( $file->file_id ) ) );
			foreach ( $file_metas as $file_meta ) {
				$meta_key = $file_meta->meta_key;
				$meta_value = $file_meta->meta_value;
				switch ( $meta_key ) {
					case 'type':
						$type = $meta_value;
						break;
					case 'region':
						$region = $meta_value;
						break;
					case 'bucket':
						$bucket = $meta_value;
						break;
					case 'key':
						$key = $meta_value;
						break;
				}
			}

			if ( $type === 'amazon-s3' && !empty( $region ) && !empty( $bucket ) && !empty( $key ) ) {
				$object_url = null;
				$size       = null;
				$mime_type  = null;
				require_once GFA_AWS_LIB . '/aws-autoloader.php';
				try {
					$s3client = new S3Client( array(
						'version' => 'latest',
						'region'  => $region,
						'credentials' => array(
							'key' => $amazon_s3_access_key,
							'secret' => $amazon_s3_secret_key,
						)
					) );
					$object_url = $s3client->getObjectUrl( $bucket, $key );
					$headobject = $s3client->headObject( array( 'Bucket' => $bucket, 'Key' => $key ) );
					/**
					 * @var Aws\Api\DateTimeResult $last_modified
					 */
					// $last_modified = $headobject->get( 'LastModified' );
					// $date = $last_modified->format( 'Y-m-d H:i:s e' );
					$size = $headobject->get( 'ContentLength' );
					$mime_type = $headobject->get( 'ContentType' );

					$cmd = $s3client->getCommand(
						'GetObject',
						array(
							'Bucket' => $bucket,
							'Key' => $key
						)
					);
					$request = $s3client->createPresignedRequest( $cmd, sprintf( '+%d minutes', intval( $amazon_s3_expires ) ) );
					$presigned_url = (string) $request->getUri();

					if ( $object_url !== null && $size !== null && $size > 0 && !empty( $presigned_url ) ) {

						if ( $amazon_s3_redirect ) {
							//
							// Serve from Amazon S3 ...
							//
							// Note that ranges are not supported with this redirect.
							//
							// header( "$protocol 307 Temporary Redirect" ); is explicit in ... :
							header( sprintf( "Location: %s", $presigned_url ), true, 307 );
							// we're done here, assume size will be sent
							$result = $size;
						} else {
							//
							// I am a proxy ...
							//
							require_once GFA_FILE_LIB . '/class-gfa-file-upload.php';
							require_once GFA_UTY_LIB . '/class-gfa-utility.php';

							@ini_set( 'zlib.output_compression', 'Off' );
							set_time_limit( 0 );
			
							$filesize = $size;
							$start    = 0;
							$end      = $filesize - 1;

							if ( isset( $_SERVER['HTTP_RANGE'] ) ) {
								$http_range = explode( '=', $_SERVER['HTTP_RANGE'] );
								if ( count( $http_range ) > 1 ) {
									list( $uom, $range_specification ) = $http_range;
									if ( $uom == 'bytes' ) {
										$tmp = explode( ',', $range_specification, 2 );
										$range = array_shift( $tmp );
										$r = explode( '-', $range, 2 );
										$start = isset( $r[0] ) ? $r[0] : null;
										$end   = isset( $r[1] ) ? $r[1] : null;
										$start = !empty( $start ) ? max( 0, intval( $start ) ) : 0;
										$end   = !empty( $end ) ? min( intval( $end ), $filesize - 1 ) : $filesize - 1;
									}
								}
							}

							$is_range = false;
							if ( ( $start > 0 ) || ( $end < ( $filesize - 1 ) ) ) {
								header( "$protocol 206 Partial Content" );
								header( 'Content-Length: ' . ( $end - $start + 1 ) );
								header( sprintf( 'Content-Range: bytes %d-%d/%d', $start, $end, $filesize ) );
								$is_range = true;
							} else {
								header( "$protocol 200 OK" );
								if ( $filesize ) {
									header( 'Content-Length: ' . $filesize );
								}
							}

							header( 'Accept-Ranges: bytes' );
							header( 'Pragma: no-cache' );
							header( 'Cache-Control: no-cache, no-store' );
							header( 'Expires: 0' );
							header( 'X-Robots-Tag: noindex, nofollow' );
							require_once GFA_CORE_LIB . '/i-groups-file-access.php';
							$options = get_option( I_Groups_File_Access::PLUGIN_OPTIONS , array() );
							$apply_mime_types  = isset( $options[I_Groups_File_Access::APPLY_MIME_TYPES] ) ? $options[I_Groups_File_Access::APPLY_MIME_TYPES] : I_Groups_File_Access::APPLY_MIME_TYPES_DEFAULT;
							if ( $apply_mime_types && ( $mime_type !== null ) ) {
								header( sprintf( 'Content-Type: %s', $mime_type ) );
							} else {
								header( 'Content-Type: application/octet-stream' );
							}

							// indicate that no encoding is applied @see https://tools.ietf.org/html/rfc2045
							header( 'Content-Transfer-Encoding: binary' );

							$filename = $key;
							$content_disposition = isset( $options[I_Groups_File_Access::CONTENT_DISPOSITION] ) ? $options[I_Groups_File_Access::CONTENT_DISPOSITION] : I_Groups_File_Access::CONTENT_DISPOSITION_DEFAULT;

							header( sprintf( 'Content-Disposition: %s; filename="%s"', $content_disposition, $filename ) );

							if ( $is_range ) {
								$result = self::buffered_read_serve( $presigned_url, $start, $end );
							} else {
								$result = self::buffered_read_serve_all( $presigned_url );
							}
						}
					}

				} catch ( Exception $ex ) {
					header( "$protocol 503 Service Unavailable" );
					error_log( sprintf(
						'Groups File Access has encountered an issue while trying to serve the file #%d via Amazon S3: %s',
						$file->file_id,
						wp_kses_post( $ex->getMessage() )
					) );
				}
			} else {
				header( "$protocol 404 Not Found" );
			}

		} else {
			header( "$protocol 503 Service Unavailable" );
			error_log( sprintf(
				'Groups File Access is not appropriately configured to serve the file #%d via Amazon S3',
				$file->file_id
			) );
		}

		return $result;
	}

	/**
	 * File URL renderer.
	 *
	 * Session Access : Unless session_access is explicitly enabled by
	 * indicating it in $options (true, 'yes' or 'true'), no gfsid will be
	 * generated.
	 *
	 * @param string $file
	 * @param string $base_url
	 * @param array $options
	 *
	 * @return string file URL
	 */
	public static function render_url( $file, $base_url, $options = array() ) {

		global $wpdb;

		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ) {
			$protocol = 'HTTP/1.0';
		}

		$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
		$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
		$amazon_s3_access_key = isset( $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_ACCESS_KEY] : '';
		$amazon_s3_secret_key = isset( $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] ) ? $options[Groups_File_Access::AMAZON_S3_SECRET_KEY] : '';
		$amazon_s3_expires    = isset( $options[Groups_File_Access::AMAZON_S3_EXPIRES] ) ? $options[Groups_File_Access::AMAZON_S3_EXPIRES] : Groups_File_Access::AMAZON_S3_EXPIRES_DEFAULT;
		$amazon_s3_expires_alt = apply_filters( 'groups_file_access_amazon_s3_expires', $amazon_s3_expires, intval( $file->file_id ) );
		if ( is_numeric( $amazon_s3_expires_alt ) ) {
			$amazon_s3_expires_alt = intval( $amazon_s3_expires_alt );
			if ( $amazon_s3_expires_alt < Groups_File_Access::AMAZON_S3_EXPIRES_MIN ) {
				$amazon_s3_expires_alt = Groups_File_Access::AMAZON_S3_EXPIRES_MIN;
			}
			$amazon_s3_expires = $amazon_s3_expires_alt;
		}

		$presigned_url = '';

		if ( $amazon_s3 && !empty( $amazon_s3_access_key ) && !empty( $amazon_s3_secret_key ) ) {

			$type   = '';
			$region = '';
			$bucket = '';
			$key    = '';
			$file_meta_table = _groups_get_tablename( 'file_meta' );
			$file_metas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_meta_table WHERE file_id = %d", intval( $file->file_id ) ) );
			foreach ( $file_metas as $file_meta ) {
				$meta_key = $file_meta->meta_key;
				$meta_value = $file_meta->meta_value;
				switch ( $meta_key ) {
					case 'type':
						$type = $meta_value;
						break;
					case 'region':
						$region = $meta_value;
						break;
					case 'bucket':
						$bucket = $meta_value;
						break;
					case 'key':
						$key = $meta_value;
						break;
				}
			}

			if ( !empty( $region ) && !empty( $bucket ) && !empty( $key ) ) {
				require_once GFA_AWS_LIB . '/aws-autoloader.php';
				try {
					$s3client = new S3Client( array(
						'version' => 'latest',
						'region'  => $region,
						'credentials' => array(
							'key' => $amazon_s3_access_key,
							'secret' => $amazon_s3_secret_key,
						)
					) );
					$cmd = $s3client->getCommand(
						'GetObject',
						array(
							'Bucket' => $bucket,
							'Key' => $key
						)
					);
					$request = $s3client->createPresignedRequest( $cmd, sprintf( '+%d minutes', intval( $amazon_s3_expires ) ) );
					$presigned_url = (string) $request->getUri();
				} catch ( Exception $ex ) {
					error_log( sprintf(
						'Groups File Access has encountered an issue while trying to render the URL for the file #%d via Amazon S3: %s',
						$file->file_id,
						wp_kses_post( $ex->getMessage() )
					) );
				}
			}
		} else {
			error_log( sprintf(
				'Groups File Access is not appropriately configured to render the URL for the file #%d via Amazon S3',
				$file->file_id
			) );
		}

		return $presigned_url;
	}

}
