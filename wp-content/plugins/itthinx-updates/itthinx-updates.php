<?php
/**
 * Plugin Name: Itthinx Updates
 * Plugin URI: https://www.itthinx.com/plugins/itthinx-updates/
 * Description: Automatic updates for plugins by <a href="https://www.itthinx.com">itthinx</a>.
 * Version: 1.4.0
 * Author: itthinx
 * Author URI: https://www.itthinx.com
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ITTHINX_UPDATES_VERSION', '1.4.0' );
define( 'ITTHINX_UPDATES_FILE', __FILE__ );
define( 'ITTHINX_UPDATES_PLUGIN_DOMAIN', 'itthinx-updates' );

define( 'ITTHINX_UPDATES_PLUGIN_DIR', WP_PLUGIN_DIR . '/itthinx-updates' );
define( 'ITTHINX_UPDATES_LOG_DIR', ITTHINX_UPDATES_PLUGIN_DIR . '/log' );

if ( is_admin() ) {
	require_once 'includes/class-itthinx-updates.php';
}
