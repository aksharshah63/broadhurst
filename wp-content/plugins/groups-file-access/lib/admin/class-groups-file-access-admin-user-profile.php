<?php
/**
 * class-groups-file-access-admin-user-profile.php
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
 * @since groups-file-access 2.3.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Groups_File_Access_Admin_User_Profile {

	/**
	 * Hook into the user profile.
	 */
	public static function init() {
		add_action( 'edit_user_profile', array( __CLASS__, 'edit_user_profile' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'edit_user_profile_update' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'personal_options_update' ) );
		add_action( 'show_user_profile', array( __CLASS__, 'edit_user_profile' ) );
	}

	/**
	 * Display information when viewing a user profile.
	 *
	 * @param WP_User $user
	 */
	public static function edit_user_profile( $user ) {

		$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
		$user_profile_show_for_admins = isset( $options[Groups_File_Access::USER_PROFILE_SHOW_FOR_ADMINS] ) ? $options[Groups_File_Access::USER_PROFILE_SHOW_FOR_ADMINS] : false;

		$show = false;
		if ( current_user_can( GROUPS_ADMINISTER_GROUPS) ) {
			$show = $user_profile_show_for_admins;
			$user_profile_show_service_key = $show;
			$user_profile_show_files = $show;
		} else {
			$user_profile_show_service_key = isset( $options[Groups_File_Access::USER_PROFILE_SHOW_SERVICE_KEY] ) ? $options[Groups_File_Access::USER_PROFILE_SHOW_SERVICE_KEY] : false;
			$user_profile_show_files = isset( $options[Groups_File_Access::USER_PROFILE_SHOW_FILES] ) ? $options[Groups_File_Access::USER_PROFILE_SHOW_FILES] : false;
		}

		$show = $show || $user_profile_show_service_key || $user_profile_show_files;

		if ( !$show ) {
			return;
		}

		$output = '<h3>';
		$output .= esc_html_x( 'Groups File Access', 'user profile section heading', 'groups-file-access' );
		$output .= '</h3>';

		$output .= '<table id="groups-file-access-user-profile" class="form-table">';
		$output .= '<tbody>';

		//
		// Service Key
		//
		if ( $user_profile_show_service_key ) {
			$service_key = Groups_File_Access::get_service_key( $user->ID );
			if ( $service_key === null ) {
				$service_key = '';
			}
			$output .= '<tr>';
			$output .= '<th>';
			$output .= '<label>';
			$output .= esc_html__( 'Service Key', 'groups-file-access' );
			$output .= '</label>';
			$output .= '</th>';
			$output .= '<td>';
			$output .= sprintf( '<input name="service_key" class="regular-text code" type="text" readonly value="%s"/>', esc_attr( $service_key ) );
			$output .= '</td>';
			$output .= '</tr>';
		}

		//
		// Accessible Files
		//
		if ( $user_profile_show_files ) {
			$output .= '<tr>';
			$output .= '<th>';
			$output .= '<label>';
			$output .= esc_html__( 'Files', 'groups-file-access' );
			$output .= '</label>';
			$output .= '</th>';
			$output .= '<td>';
			$output .= GFA_Shortcodes::groups_file_link( array( 'user_id' => $user->ID, 'group' => '*' ) );
			$output .= '</td>';
			$output .= '</tr>';
		}

		$output .= '</tbody>';
		$output .= '</table>';

		echo $output;

	}

	/**
	 * Update when own profile is saved.
	 *
	 * @param int $user_id
	 */
	public static function personal_options_update( $user_id ) {
		if ( !current_user_can( GROUPS_ADMINISTER_GROUPS) ) {
			return;
		}
		self::edit_user_profile_update( $user_id );
	}

	/**
	 * Save user profile changes.
	 *
	 * @param int $user_id
	 */
	public static function edit_user_profile_update( $user_id ) {
		if ( !current_user_can( GROUPS_ADMINISTER_GROUPS) ) {
			return;
		}
	}
}

Groups_File_Access_Admin_User_Profile::init();
