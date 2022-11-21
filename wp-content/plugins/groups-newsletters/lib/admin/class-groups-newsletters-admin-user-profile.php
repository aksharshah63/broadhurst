<?php
/**
 * class-groups-newsletters-admin-user-profile.php
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
 * Show opt-in/out on user profile.
 */
class Groups_Newsletters_Admin_User_Profile {

	/**
	 * Adds user profile actions.
	 */
	public static function init() {
		add_action( 'show_user_profile', array( __CLASS__, 'show_user_profile' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'edit_user_profile' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'personal_options_update' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'edit_user_profile_update' ) );
	}

	/**
	 * Editing own user profile.
	 *
	 * @param WP_User $user
	 */
	public static function show_user_profile( $user ) {
		$is_subscriber = get_user_meta( $user->ID, 'groups_newsletters_subscriber', true );
		$output = '<h3>' . esc_html__( 'Newsletters', 'groups-newsletters' ) . '</h3>';
		$output .= '<p>';
		$output .= '<label>';
		$output .= sprintf( '<input type="checkbox" name="groups_newsletters_subscriber" %s />', $is_subscriber == 'yes' ? ' checked="checked" ' : '' );
		$output .= '<input type="hidden" name="set_groups_newsletters_subscriber" value="1" />';
		$output .= ' ';
		$output .= self::get_subscribe_text();
		$output .= '</label>';
		$output .= '</p>';
		// @codingStandardsIgnoreStart
		echo $output;
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Editing a user profile.
	 *
	 * @param WP_User $user
	 */
	public static function edit_user_profile( $user ) {
		$is_subscriber = get_user_meta( $user->ID, 'groups_newsletters_subscriber', true );
		$disabled = current_user_can( 'edit_users' ) ? '' : ' disabled="disabled" ';
		$output = '<h3>' . esc_html__( 'Newsletters', 'groups-newsletters' ) . '</h3>';
		$output .= '<p>';
		$output .= '<label>';
		$output .= sprintf( '<input type="checkbox" name="groups_newsletters_subscriber" %s %s />', $is_subscriber == 'yes' ? ' checked="checked" ' : '', $disabled );
		if ( !$disabled ) {
			$output .= '<input type="hidden" name="set_groups_newsletters_subscriber" value="1" />';
		}
		$output .= ' ';
		$output .= self::get_subscribe_text();
		$output .= '</label>';
		$output .= '</p>';
		// @codingStandardsIgnoreStart
		echo $output;
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Updates the newsletter subscription.
	 *
	 * @param int $user_id
	 */
	public static function personal_options_update( $user_id ) {
		self::edit_user_profile_update( $user_id );
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
	 * Returns the 'Subscribe to our newsletters' text or filtered value.
	 *
	 * @see Groups_Newsletters::get_subscribe_text()
	 * @return string
	 */
	public static function get_subscribe_text() {
		return apply_filters(
			'groups_newsletters_get_subscribe_text',
			__( 'Subscribe to our newsletters', 'groups-newsletters' )
		);
	}
}
Groups_Newsletters_Admin_User_Profile::init();
