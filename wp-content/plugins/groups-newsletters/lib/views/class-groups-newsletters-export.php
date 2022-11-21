<?php
/**
 * class-groups-newsletters-export.php
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
class Groups_Newsletters_Export {

	/**
	 * Init hook to catch export file generation request.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
	}

	/**
	 * Catch request to generate export file.
	 */
	public static function wp_init() {
		if ( isset( $_REQUEST['groups-newsletters-export'] ) ) {
			if ( wp_verify_nonce( $_REQUEST['groups-newsletters-export'], 'export' ) ) {
				self::export_subscribers();
			}
		}
	}

	public static function export_subscribers() {
		global $wpdb;
		if ( !headers_sent() ) {
			$charset = get_bloginfo( 'charset' );
			$now     = date( 'Y-m-d-H-i-s', time() );
			header( 'Content-Description: File Transfer' );
			if ( !empty( $charset ) ) {
				header( 'Content-Type: text/plain; charset=' . $charset );
			} else {
				header( 'Content-Type: text/plain' );
			}
			header( "Content-Disposition: attachment; filename=\"groups-newsletters-subscribers-$now.txt\"" );
			$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );

			if ( isset( $_POST['status'] ) ) {

				$active   = is_array( $_POST['status'] ) && in_array( '1', $_POST['status'] );
				$inactive = is_array( $_POST['status'] ) && in_array( '0', $_POST['status'] );
				$status = '';
				if ( $active && $inactive ) {
					$status = '0, 1';
				} else if ( $active ) {
					$status = '1';
				} else if ( $inactive ) {
					$status = '0';
				}

				$separator = "\t";
				if ( isset( $_POST['format'] ) ) {
					switch( $_POST['format'] ) {
						case "csv" :
							$separator = ",";
							break;
						default :
							$separator = "\t";
					}
				}

				$subscribers = !empty( $_POST['subscribers'] );
				$users       = !empty( $_POST['users'] );

				$count = 0;
				if ( $subscribers ) {
					if ( $results = $wpdb->get_results( "SELECT * FROM $subscriber_table WHERE status IN ($status)" ) ) {
						$count += count( $results );
						foreach( $results as $result ) {
							echo sprintf( "$result->email%s$result->subscribed%s$result->status\n", $separator, $separator );
						}
					}
					unset( $results );
				}

				// subscribed users
				if ( $users ) {
					$user_ids = get_users(
						array(
							'fields' => 'ID',
							'meta_query' => array(
								array(
									'key' => 'groups_newsletters_subscriber',
									'value' => 'yes',
									'compare' => '='
								)
							)
						)
					);
					foreach( $user_ids as $user_id ) {
						if ( $user = get_user_by( 'id', $user_id ) ) {
							$email = $user->user_email;
							$status = $user->get( 'groups_newsletters_subscriber' ) == 'yes' ? '1' : '0';
							$subscribed = $user->get( 'groups_newsletters_datetime' );
							echo sprintf( "$email%s$subscribed%s$status\n", $separator, $separator );
							unset( $user );
						}
					}
					unset( $user_ids );
				}

				if ( $count > 0 ) {
					echo "\n";
				}
			}
			die;
		} else {
			wp_die( 'ERROR: headers already sent' );
		}
	}
}
Groups_Newsletters_Export::init();
