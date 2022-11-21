<?php
/**
 * Plugin Name:       WPForms Save and Resume
 * Plugin URI:        https://wpforms.com
 * Description:       Save partial entries and resume them later with WPForms.
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.3.0
 * Text Domain:       wpforms-save-resume
 * Domain Path:       /languages
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

use WPFormsSaveResume\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WPForms.Comments.PHPDocDefine.MissPHPDoc
define( 'WPFORMS_SAVE_RESUME_VERSION', '1.3.0' );
define( 'WPFORMS_SAVE_RESUME_FILE', __FILE__ );
define( 'WPFORMS_SAVE_RESUME_PATH', plugin_dir_path( WPFORMS_SAVE_RESUME_FILE ) );
define( 'WPFORMS_SAVE_RESUME_URL', plugin_dir_url( WPFORMS_SAVE_RESUME_FILE ) );
define( 'WPFORMS_SAVE_RESUME_MIN_WPFORMS_VERSION', '1.7.5.5' );
// phpcs:enable WPForms.Comments.PHPDocDefine.MissPHPDoc

/**
 * Load the provider class.
 *
 * @since 1.0.0
 */
function wpforms_save_resume_load() {

	// Check requirements.
	if ( ! wpforms_save_resume_required() ) {
		return;
	}

	// Load the plugin.
	wpforms_save_resume();
}

add_action( 'wpforms_loaded', 'wpforms_save_resume_load' );

/**
 * Check addon requirements.
 *
 * @since 1.0.0
 */
function wpforms_save_resume_required() {

	if ( PHP_VERSION_ID < 50600 ) {
		add_action( 'admin_init', 'wpforms_save_resume_deactivation' );
		add_action( 'admin_notices', 'wpforms_save_resume_fail_php_version' );

		return false;
	}

	if ( ! function_exists( 'wpforms' ) ) {
		return false;
	}

	if ( version_compare( wpforms()->version, WPFORMS_SAVE_RESUME_MIN_WPFORMS_VERSION, '<' ) ) {
		add_action( 'admin_init', 'wpforms_save_resume_deactivation' );
		add_action( 'admin_notices', 'wpforms_save_resume_fail_wpforms_version' );

		return false;
	}

	if (
		! function_exists( 'wpforms_get_license_type' ) ||
		! in_array( wpforms_get_license_type(), [ 'pro', 'agency', 'ultimate', 'elite' ], true )
	) {
		return false;
	}

	return true;
}

/**
 * Deactivate the plugin.
 *
 * @since 1.0.0
 */
function wpforms_save_resume_deactivation() {

	deactivate_plugins( plugin_basename( WPFORMS_SAVE_RESUME_FILE ) );
}

/**
 * Admin notice for a minimum PHP version.
 *
 * @since 1.0.0
 */
function wpforms_save_resume_fail_php_version() {

	echo '<div class="notice notice-error"><p>';
	printf(
		wp_kses( /* translators: %s - WPForms.com documentation page URI. */
			__( 'The WPForms Save and Resume plugin has been deactivated. Your site is running an outdated version of PHP that is no longer supported and is not compatible with the Save and Resume plugin. <a href="%s" target="_blank" rel="noopener noreferrer">Read more</a> for additional information.', 'wpforms-save-resume' ),
			[
				'a' => [
					'href'   => [],
					'rel'    => [],
					'target' => [],
				],
			]
		),
		'https://wpforms.com/docs/supported-php-version/'
	);
	echo '</p></div>';

	// phpcs:disable
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	// phpcs:enable
}

/**
 * Admin notice for minimum WPForms version.
 *
 * @since 1.0.0
 */
function wpforms_save_resume_fail_wpforms_version() {

	echo '<div class="notice notice-error"><p>';
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	printf(
	/* translators: Minimum required WPForms version. */
		esc_html__( 'The WPForms Save and Resume plugin has been deactivated because it requires WPForms v%s or later to work.', 'wpforms-save-resume' ),
		WPFORMS_SAVE_RESUME_MIN_WPFORMS_VERSION
	);
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</p></div>';

	// phpcs:disable
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	// phpcs:enable
}

/**
 * Get the instance of the `\WPFormsSaveResume\Plugin` class.
 * This function is useful for quickly grabbing data used throughout the plugin.
 *
 * @since 1.0.0
 *
 * @return Plugin
 */
function wpforms_save_resume() {

	require_once __DIR__ . '/vendor/autoload.php';

	return Plugin::get_instance();
}
