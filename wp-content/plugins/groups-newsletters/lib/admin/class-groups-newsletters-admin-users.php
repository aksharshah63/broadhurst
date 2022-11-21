<?php
/**
 * class-groups-newsletters-admin-users.php
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
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Subscriber column in users screen.
 */
class Groups_Newsletters_Admin_Users {

	const SUBSCRIBER = 'groups_newsletters_subscriber';

	/**
	 * Adds admin_init filter.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Adds user column filters.
	 */
	public static function admin_init() {
		if ( current_user_can( 'manage_options' ) ) {
			// filters to display the user's groups
			add_filter( 'manage_users_columns', array( __CLASS__, 'manage_users_columns' ) );
			// args: unknown, string $column_name, int $user_id
			add_filter( 'manage_users_custom_column', array( __CLASS__, 'manage_users_custom_column' ), 10, 3 );
			// bulk actions not yet ... see https://core.trac.wordpress.org/ticket/16031
			//add_filter( 'bulk_actions-users', array( __CLASS__,'bulk_actions_users' ) );
			// adding via this instead
			add_action( 'admin_footer', array( __CLASS__, 'admin_footer' ) );
			add_action( 'load-users.php', array( __CLASS__, 'load_users' ) );
		}
	}

	/**
	 * Adds the subscribers column.
	 *
	 * @param array $column_headers
	 * @return array column headers
	 */
	public static function manage_users_columns( $column_headers ) {
		$column_headers[self::SUBSCRIBER] = sprintf( '<span title="%s">%s</span>', esc_attr__( 'Newsletter subscribers', 'groups-newsletters' ), esc_html__( 'Subscriber', 'groups-newsletters' ) );
		return $column_headers;
	}

	/**
	 * Renders the subscriber column content.
	 *
	 * @param string $output
	 * @param string $column_name
	 * @param int $user_id
	 * @return string custom column content
	 */
	public static function manage_users_custom_column( $output, $column_name, $user_id ) {
		switch ( $column_name ) {
			case self::SUBSCRIBER :
				$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );
				if ( $is_subscriber == 'yes' ) {
					$output .= esc_html__( 'Yes', 'groups-newsletters' );
				}
				break;
		}
		return $output;
	}

	/**
	 * Currently not used. Supposed to add bulk actions but
	 * this isn't possible yet - see https://core.trac.wordpress.org/ticket/16031
	 *
	 * @param array $actions
	 * @return array
	 */
	public static function bulk_actions_users( $actions ) {
		$actions['groups-newsletters-subscribe'] = __( 'Subscribe', 'groups-newsletters' );
		$actions['groups-newsletters-unsubscribe'] = __( 'Unsubscribe', 'groups-newsletters' );
		return $actions;
	}

	/**
	 * Adds our bulk actions.
	 */
	public static function admin_footer() {
		$screen = get_current_screen();
		if ( isset( $screen->id ) && $screen->id == 'users' ) {
			echo '<script type="text/javascript">';
			echo 'jQuery(function() {';
			printf( "jQuery('<option>').val('groups-newsletters-subscribe').text('%s').appendTo('select[name=\"action\"]');", esc_html__( 'Subscribe', 'groups-newsletters' ) );
			printf( "jQuery('<option>').val('groups-newsletters-subscribe').text('%s').appendTo('select[name=\"action2\"]');", esc_html__( 'Subscribe', 'groups-newsletters' ) );
			printf( "jQuery('<option>').val('groups-newsletters-unsubscribe').text('%s').appendTo('select[name=\"action\"]');", esc_html__( 'Unsubscribe', 'groups-newsletters' ) );
			printf( "jQuery('<option>').val('groups-newsletters-unsubscribe').text('%s').appendTo('select[name=\"action2\"]');", esc_html__( 'Unsubscribe', 'groups-newsletters' ) );
			echo '});';
			echo '</script>';
		}
	}

	/**
	 * Handle our bulk actions.
	 */
	public static function load_users() {
		if ( $wp_list_table = _get_list_table( 'WP_Users_List_Table' ) ) {
			$action = $wp_list_table->current_action();
			$user_ids = !empty( $_REQUEST['users'] ) ? $_REQUEST['users'] : array();
			switch ( $action ) {
				case 'groups-newsletters-subscribe' :
					foreach ( $user_ids as $user_id ) {
						$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );
						if ( $is_subscriber != 'yes' ) {
							if ( update_user_meta( $user_id, 'groups_newsletters_subscriber', 'yes' ) ) {
								$hash = md5( time() + rand( 0, time() ) );
								$datetime = date( 'Y-m-d H:i:s', time() );
								update_user_meta( $user_id, 'groups_newsletters_hash', $hash );
								update_user_meta( $user_id, 'groups_newsletters_datetime', $datetime );
								do_action( 'groups_newsletters_user_subscribed', $user_id );
							}
						}
					}
					break;
				case 'groups-newsletters-unsubscribe' :
					foreach ( $user_ids as $user_id ) {
						$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );
						foreach ( $user_ids as $user_id ) {
							if ( $is_subscriber == 'yes' ) {
								if ( delete_user_meta( $user_id, 'groups_newsletters_subscriber' ) ) {
									delete_user_meta( $user_id, 'groups_newsletters_hash' );
									delete_user_meta( $user_id, 'groups_newsletters_datetime' );
									do_action( 'groups_newsletters_user_unsubscribed', $user_id );
								}
							}
						}
					}
					break;
			}
		}
	}
}
Groups_Newsletters_Admin_Users::init();
