<?php
/**
 * Plugin Name:       WPForms Signatures
 * Plugin URI:        https://wpforms.com
 * Description:       Signatures with WPForms.
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.6.0
 * Text Domain:       wpforms-signatures
 * Domain Path:       languages
 *
 * WPForms is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WPForms is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WPForms. If not, see <https://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
define( 'WPFORMS_SIGNATURES_VERSION', '1.6.0' );

/**
 * Load the main class.
 *
 * @since 1.0.0
 */
function wpforms_signatures() {

	// WPForms Pro is required.
	if (
		! function_exists( 'wpforms' ) ||
		! function_exists( 'wpforms_get_license_type' ) ||
		! in_array( wpforms_get_license_type(), [ 'pro', 'elite', 'agency', 'ultimate' ], true )
	) {
		return;
	}

	if ( ! defined( 'WPFORMS_VERSION' ) || version_compare( WPFORMS_VERSION, '1.7.4.2', '<' ) ) {
		add_action( 'admin_init', 'wpforms_signatures_deactivate' );
		add_action( 'admin_notices', 'wpforms_signatures_fail_wpforms_version' );
	}

	require_once plugin_dir_path( __FILE__ ) . 'class-signatures.php';
}

add_action( 'wpforms_loaded', 'wpforms_signatures' );

/**
 * Deactivate plugin.
 *
 * @since 1.5.0
 */
function wpforms_signatures_deactivate() {

	deactivate_plugins( plugin_basename( __FILE__ ) );
}

/**
 * Display notice after deactivation.
 *
 * @since 1.5.0
 */
function wpforms_signatures_fail_wpforms_version() {

	echo '<div class="notice notice-error"><p>';
	esc_html_e( 'The WPForms Signatures plugin has been deactivated, because it requires WPForms v1.7.4.2 or later to work.', 'wpforms-signatures' );
	echo '</p></div>';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}

/**
 * Load the plugin updater.
 *
 * @since 1.0.0
 *
 * @param string $key WPForms license key.
 */
function wpforms_signatures_updater( $key ) {

	new WPForms_Updater(
		[
			'plugin_name' => 'WPForms Signatures',
			'plugin_slug' => 'wpforms-signatures',
			'plugin_path' => plugin_basename( __FILE__ ),
			'plugin_url'  => trailingslashit( plugin_dir_url( __FILE__ ) ),
			'remote_url'  => WPFORMS_UPDATER_API,
			'version'     => WPFORMS_SIGNATURES_VERSION,
			'key'         => $key,
		]
	);
}

add_action( 'wpforms_updater', 'wpforms_signatures_updater' );
