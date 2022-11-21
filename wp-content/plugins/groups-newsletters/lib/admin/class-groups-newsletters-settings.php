<?php
/**
 * class-groups-newsletters-settings.php
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
 * Admin section.
 */
class Groups_Newsletters_Settings {

	/**
	 * Admin options setup.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( GROUPS_NEWSLETTERS_PLUGIN_FILE ), array( __CLASS__, 'admin_settings_link' ) );
	}

	/**
	 * Admin options admin setup.
	 */
	public static function admin_init() {
		wp_register_style( 'groups_newsletters_admin', GROUPS_NEWSLETTERS_PLUGIN_URL . 'css/admin.css', array(), GROUPS_NEWSLETTERS_PLUGIN_VERSION );
	}

	/**
	 * Loads styles for the admin section.
	 */
	public static function admin_print_styles() {
		wp_enqueue_style( 'groups_newsletters_admin' );
	}

	/**
	 * Does nothing at the moment
	 */
	public static function admin_print_scripts() {
	}

	/**
	 * Add a menu item to the Appearance menu.
	 */
	public static function admin_menu() {
		$page = add_submenu_page(
			'edit.php?post_type=story',
			__( 'Groups Newsletters Settings', 'groups-newsletters' ),
			__( 'Settings', 'groups-newsletters' ),
			'manage_options',
			'groups-newsletters-settings',
			array( __CLASS__, 'settings' )
		);
		add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'admin_print_styles' ) );
		add_action( 'admin_print_scripts-' . $page, array( __CLASS__, 'admin_print_scripts' ) );
	}

	/**
	 * Settings screen.
	 */
	public static function settings() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-newsletters' ) );
		}
		echo
			'<h2>' .
			esc_html__( 'Settings', 'groups-newsletters' ) .
			'</h2>';
		echo '<div class="groups-newsletters-settings">';
		include_once GROUPS_NEWSLETTERS_ADMIN_LIB . '/settings.php';
		echo '</div>';
	}

	/**
	 * Adds plugin links.
	 *
	 * @param array $links with additional links
	 */
	public static function admin_settings_link( $links ) {
		if ( current_user_can( 'manage_options' ) ) {
			$links[] = '<a href="' . get_admin_url( null, 'admin.php?page=groups-newsletters-settings' ) . '">' . esc_html__( 'Settings', 'groups-newsletters' ) . '</a>';
		}
		return $links;
	}

}
add_action( 'init', array( 'Groups_Newsletters_Settings', 'init' ) );
