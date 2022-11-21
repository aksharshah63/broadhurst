<?php
/**
 * class-groups-newsletters-import.php
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
 * @author Karim Rahimpur
 * @package groups-newsletters
 * @since groups-newsletters 1.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Show opt-in on user registration form.
 */
class Groups_Newsletters_Import {

	const MAX_LINE_LENGTH = 1024;

	private static $admin_messages = array();

	/**
	 * Init hook to catch import file generation request.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			echo '<div style="padding:1em;margin:1em;border:1px solid #aa0;border-radius:4px;background-color:#ffe;color:#333;">';
			foreach ( self::$admin_messages as $msg ) {
				echo '<p>';
				echo $msg;
				echo '</p>';
			}
			echo '</div>';
		}
	}

	/**
	 * Catch request to generate import file.
	 */
	public static function wp_init() {
		if ( isset( $_REQUEST['groups-newsletters-import'] ) ) {
			if ( wp_verify_nonce( $_REQUEST['groups-newsletters-import'], 'import' ) ) {
				self::import_subscribers();
			}
		}
	}

	/**
	 * Import from uploaded file.
	 *
	 * @return int number of records created
	 */
	public static function import_subscribers() {

		global $wpdb;

		$charset = get_bloginfo( 'charset' );
		$now     = date( 'Y-m-d H:i:s', time() );
		$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );

		$subscribe_users = isset( $_REQUEST['subscribe_users'] );

		if ( isset( $_FILES['file'] ) ) {
			if ( $_FILES['file']['error'] == UPLOAD_ERR_OK ) {
				$tmp_name = $_FILES['file']['tmp_name'];
				if ( file_exists( $tmp_name ) ) {
					if ( $h = @fopen( $tmp_name, 'r' ) ) {

						$imported           = 0;
						$invalid            = 0;
						$skipped_user       = 0;
						$skipped_subscriber = 0;
						$skipped_others     = 0;
						$subscribed_user    = 0;

						while ( $line = fgets( $h, self::MAX_LINE_LENGTH ) ) {
							$line = preg_replace( '/\r|\n/', '', $line );
							$data = explode( "\t", $line );
							$email = trim( $data[0] );
							if ( is_email( $email ) ) {
								$subscribed = date( 'Y-m-d H:i:s', time() );
								if ( !empty( $data[1] ) ) {
									$subscribed = trim( $data[1] );
									$subscribed = date( 'Y-m-d H:i:s', strtotime( $subscribed ) );
								}
								$status = 1;
								if ( isset( $data[2] ) ) { // 0
									$status = intval( $data[2] ) > 0 ? 1 : 0;
								}
								$hash = md5( time() + rand( 0, time() ) );
								if ( !($user = get_user_by( 'email', $email ) ) ) {
									if ( !$wpdb->get_var( $wpdb->prepare( "SELECT subscriber_id FROM $subscriber_table WHERE email = %s", $email ) ) ) {
										if ( $wpdb->query( $wpdb->prepare(
											"INSERT INTO $subscriber_table SET email = %s, status = %d, subscribed = %s, hash = %s",
											$email, $status, $subscribed, $hash
										) ) ) {
											$imported++;
										} else {
											$skipped_others++;
										}
									} else {
										$skipped_subscriber++;
									}
								} else {
									if ( $subscribe_users ) {
										$is_subscriber = get_user_meta( $user->ID, 'groups_newsletters_subscriber', true );
										if ( $is_subscriber != 'yes' ) {
											if ( $status ) {
												if ( update_user_meta( $user->ID, 'groups_newsletters_subscriber', 'yes' ) ) {
													update_user_meta( $user->ID, 'groups_newsletters_hash', $hash );
													update_user_meta( $user->ID, 'groups_newsletters_datetime', $subscribed );
													do_action( 'groups_newsletters_user_subscribed', $user->ID );
													$subscribed_user++;
												}
											}
										}
									} else {
										$skipped_user++;
									}
								}
							} else {
								$invalid++;
							}
						}
						@fclose( $h );

						self::$admin_messages[] = sprintf( _n( '1 subscriber has been imported.', '%d subscribers have been imported', $imported, 'groups-newsletters' ), $imported );
						if ( $subscribed_user > 0 ) {
							self::$admin_messages[] = sprintf( _n( '1 existing user was subscribed', '%d existing users were subscribed', $subscribed_user, 'groups-newsletters' ), $subscribed_user );
						}
						if ( $invalid > 0 ) {
							self::$admin_messages[] = sprintf( _n( '1 invalid line was skipped', '%d invalid lines were skipped', $invalid, 'groups-newsletters' ), $invalid );
						}
						if ( $skipped_user > 0 ) {
							self::$admin_messages[] = sprintf( _n( '1 existing user was skipped', '%d existing users were skipped', $skipped_user, 'groups-newsletters' ), $skipped_user );
						}
						if ( $skipped_subscriber > 0 ) {
							self::$admin_messages[] = sprintf( _n( '1 existing subscriber was skipped', '%d existing subscribers were skipped', $skipped_subscriber, 'groups-newsletters' ), $skipped_subscriber );
						}
						if ( $skipped_others > 0 ) {
							self::$admin_messages[] = sprintf( _n( '1 was skipped because creating the record failed', '%d were skipped because creating the records failed', $skipped_others, 'groups-newsletters' ), $skipped_others );
						}
					} else {
						self::$admin_messages[] = __( 'Import failed (error opening temporary file).', 'groups-newsletters' );
					}
				}
			}
		}

	}
}
Groups_Newsletters_Import::init();
