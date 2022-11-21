<?php
/**
 * class-groups-newsletters-registration.php
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
 * Show opt-in on user registration form.
 */
class Groups_Newsletters_Registration {

	/**
	 * Adds user profile actions.
	 */
	public static function init() {
		add_action( 'register_form', array( __CLASS__, 'register_form' ) );
		add_action( 'user_register', array( __CLASS__, 'user_register' ) );
	}

	/**
	 * Updates the newsletter subscription.
	 *
	 * @param int $user_id
	 */
	public static function edit_user_profile_update( $user_id ) {
		if ( ( get_current_user_id() === $user_id ) || current_user_can( 'edit_users' ) ) {
			if ( isset( $_POST['set_groups_newsletters_subscriber'] ) ) {
				$subscribe = !empty( $_POST['groups_newsletters_subscriber'] );
				$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );
				if ( $subscribe ) {
					if ( $is_subscriber != 'yes' ) {
						if ( update_user_meta( $user_id, 'groups_newsletters_subscriber', 'yes' ) ) {
							$hash = md5( time() + rand( 0, time() ) );
							$datetime = date( 'Y-m-d H:i:s', time() );
							update_user_meta( $user_id, 'groups_newsletters_hash', $hash );
							update_user_meta( $user_id, 'groups_newsletters_datetime', $datetime );
							do_action( 'groups_newsletters_user_subscribed', $user_id );
						}
					}
				} else {
					if ( $is_subscriber == 'yes' ) {
						if ( delete_user_meta( $user_id, 'groups_newsletters_subscriber' ) ) {
							delete_user_meta( $user_id, 'groups_newsletters_hash' );
							delete_user_meta( $user_id, 'groups_newsletters_datetime' );
							do_action( 'groups_newsletters_user_unsubscribed', $user_id );
						}
					}
				}
			}
		}
	}

	/**
	 * Adds a newslettere opt-in checkbox to the registration form.
	 */
	public static function register_form() {
		$checked = '';
		if ( empty( $_POST['wp-submit'] ) ) {
			if ( Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_REGISTRATION_OPT_IN_CHECKED, true ) ) {
				$checked = ' checked="checked" ';
			}
		} else {
			$checked = isset( $_POST['groups_newsletters_subscriber'] ) ? ' checked="checked" ' : '';
		}
		echo apply_filters(
			'groups_newsletters_register_form_opt_in',
			'<p>' .
			'<label>' .
			sprintf( '<input type="checkbox" name="groups_newsletters_subscriber" id="groups_newsletters_subscriber" class="checkbox" %s />', $checked ) .
			' ' .
			self::get_subscribe_text() .
			'</label>' .
			'</p>' .
			'<br/>'
		);
	}

	/**
	 * Adds subscriber hash on new user registration when user has opted in.
	 *
	 * @param int $user_id
	 */
	public static function user_register( $user_id ) {
		if ( !empty( $_POST['groups_newsletters_subscriber'] ) ) {
			if ( update_user_meta( $user_id, 'groups_newsletters_subscriber', 'yes' ) ) {
				$hash = md5( time() + rand( 0, time() ) );
				$datetime = date( 'Y-m-d H:i:s', time() );
				update_user_meta( $user_id, 'groups_newsletters_hash', $hash );
				update_user_meta( $user_id, 'groups_newsletters_datetime', $datetime );
				do_action( 'groups_newsletters_user_subscribed', $user_id );
			}
		}
	}

	/**
	 * Returns the 'Subscribe to our newsletters' text or filtered value.
	 *
	 * @return string
	 *
	 * @see Groups_Newsletters_Admin_User_Profile::get_subscribe_text()
	 */
	public static function get_subscribe_text() {
		return apply_filters(
			'groups_newsletters_get_subscribe_text',
			__( 'Subscribe to our newsletters', 'groups-newsletters' )
		);
	}
}
Groups_Newsletters_Registration::init();
