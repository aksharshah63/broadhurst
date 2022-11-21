<?php
/**
 * Plugin Name:       WPForms Surveys and Polls
 * Plugin URI:        https://wpforms.com
 * Description:       Create Surveys and Polls with WPForms.
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            WPForms
 * Author URI:        https://wpforms.com
 * Version:           1.10.0
 * Text Domain:       wpforms-surveys-polls
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
 * Plugin constants.
 *
 * @since 1.0.0
 */
// phpcs:disable WPForms.Comments.PHPDocDefine.MissPHPDoc
define( 'WPFORMS_SURVEYS_POLLS_VERSION', '1.10.0' );
define( 'WPFORMS_SURVEYS_POLLS_FILE', __FILE__ );
// phpcs:enable WPForms.Comments.PHPDocDefine.MissPHPDoc

/**
 * Check addon requirements.
 * We do it on `plugins_loaded` hook. If earlier - core constants still not defined.
 *
 * @since 1.6.1
 */
function wpforms_surveys_polls_required() {

	if ( PHP_VERSION_ID < 50600 ) {
		add_action( 'admin_init', 'wpforms_surveys_polls_deactivate' );
		add_action( 'admin_notices', 'wpforms_surveys_polls_deactivate_msg' );

	} elseif (
		! defined( 'WPFORMS_VERSION' ) ||
		version_compare( WPFORMS_VERSION, '1.7.7', '<' )
	) {
		add_action( 'admin_init', 'wpforms_surveys_polls_deactivate' );
		add_action( 'admin_notices', 'wpforms_surveys_polls_fail_wpforms_version' );

	} elseif (
		! function_exists( 'wpforms_get_license_type' ) ||
		! in_array( wpforms_get_license_type(), [ 'pro', 'elite', 'agency', 'ultimate' ], true )
	) {
		return;

	} else {
		// Actually, load the addon now.
		require_once __DIR__ . '/autoloader.php';
	}
}

add_action( 'wpforms_loaded', 'wpforms_surveys_polls_required' );

/**
 * Deactivate plugin.
 *
 * @since 1.0.0
 */
function wpforms_surveys_polls_deactivate() {

	deactivate_plugins( plugin_basename( __FILE__ ) );
}

/**
 * Display notice after deactivation.
 *
 * @since 1.0.0
 */
function wpforms_surveys_polls_deactivate_msg() {

	echo '<div class="notice notice-error"><p>';
	printf(
		wp_kses( /* translators: %s - WPForms.com documentation page URL. */
			__( 'The WPForms Surveys and Poll plugin has been deactivated. Your site is running an outdated version of PHP that is no longer supported and is not compatible with the Surveys and Polls addon. <a href="%s" target="_blank" rel="noopener noreferrer">Read more</a> for additional information.', 'wpforms-surveys-polls' ),
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

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}

/**
 * Admin notice for minimum WPForms version.
 *
 * @since 1.6.1
 */
function wpforms_surveys_polls_fail_wpforms_version() {

	echo '<div class="notice notice-error"><p>';
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html_e( 'The WPForms Surveys and Polls plugin has been deactivated, because it requires WPForms v1.7.7 or later to work.', 'wpforms-surveys-polls' );
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</p></div>';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}
