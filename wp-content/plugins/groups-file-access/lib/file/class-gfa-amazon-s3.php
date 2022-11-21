<?php
/**
 * class-gfa-amazon-s3.php
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

/**
 * Amazon S3 File Access
 */
class GFA_Amazon_S3 {

	private $access_key = null;

	private $secret_key = null;

	private $bucket = null;

	private $key = null;

	private $object_url = null;

	private $expires = null;

	public function __construct( $args = array() ) {
		if ( is_array( $args ) ) {
			foreach ( $args as $key => $value ) {
				switch ( $key ) {
					case 'access_key':
						if ( is_string( $value ) ) {
							$this->access_key = $value;
						}
						break;
					case 'secret_key':
						if ( is_string( $value ) ) {
							$this->secret_key = $value;
						}
						break;
					case 'bucket':
						if ( is_string( $value ) ) {
							$this->bucket = $value;
						}
						break;
					case 'key':
						if ( is_string( $value ) ) {
							$this->key = $value;
						}
						break;
					case 'object_url':
						if ( is_string( $value ) ) {
							$this->object_url = $value;
						}
						break;
					case 'expires':
						if ( is_numeric( $value ) ) {
							$this->expires = intval( $value );
						}
						break;
				}
			}
		}
	}

	public function get_signed_url() {
		$bits = array(
			'GET',
			null,
			null,
			$this->expires,
			'/' . $this->bucket . '/' . $this->key
		);
		$sign = implode( "\n", $bits );
		$signature = $this->get_signature( $this->secret_key, $sign );
		$query_data = array(
			'AWSAccessKeyId' => $this->access_key,
			'Expires'        => $this->expires,
			'Signature'      => $signature
		);
		$query = http_build_query( $query_data );
		$signed_url = $this->object_url . '?' . $query;
		return $signed_url;
	}

	private function get_signature( $key, $data ) {
		return base64_encode( hash_hmac( 'sha1', $data, $key, true ) );
	}

	/**
	 * Returns available regions as Code-Name entries.
	 *
	 * @return array
	 */
	public static function get_regions() {
		$regions = array(
			'us-east-2'      => 'US East (Ohio)',
			'us-east-1'      => 'US East (N. Virginia)',
			'us-west-1'      => 'US West (N. California)',
			'us-west-2'      => 'US West (Oregon)',
			'af-south-1'     => 'Africa (Cape Town)',
			'ap-east-1'      =>'Asia Pacific (Hong Kong)',
			'ap-southeast-3' => 'Asia Pacific (Jakarta)',
			'ap-south-1'     => 'Asia Pacific (Mumbai)',
			'ap-northeast-3' => 'Asia Pacific (Osaka)',
			'ap-northeast-2' => 'Asia Pacific (Seoul)',
			'ap-southeast-1' => 'Asia Pacific (Singapore)',
			'ap-southeast-2' => 'Asia Pacific (Sydney)',
			'ap-northeast-1' => 'Asia Pacific (Tokyo)',
			'ca-central-1'   => 'Canada (Central)',
			'eu-central-1'   => 'Europe (Frankfurt)',
			'eu-west-1'      => 'Europe (Ireland)',
			'eu-west-2'      => 'Europe (London)',
			'eu-south-1'     => 'Europe (Milan)',
			'eu-west-3'      => 'Europe (Paris)',
			'eu-north-1'     => 'Europe (Stockholm)',
			'me-south-1'     => 'Middle East (Bahrain)',
			'sa-east-1'      => 'South America (São Paulo)'
		);
		$regions = apply_filters( 'groups_file_access_amazon_s3_regions', $regions );
		return $regions;
	}
}
