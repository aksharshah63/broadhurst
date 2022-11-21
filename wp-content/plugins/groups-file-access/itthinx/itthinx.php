<?php
if ( !function_exists( 'itthinx_plugins' ) ) {
	function itthinx_plugins( $plugin ) {
		global $itthinx_plugins;
		if ( !isset( $itthinx_plugins ) ) {
			$itthinx_plugins = array();
		}
		$itthinx_plugins[] = $plugin;
	}
}

if ( !class_exists( 'Itthinx_Updates' ) && !function_exists( 'itthinx_updates' ) ) {
	function itthinx_updates( $api, $action, $args ) {
		if ( $action != 'plugin_information' || $api !== false || $args->slug != 'itthinx-updates' ) {
			return $api;
		}
		$api = new stdClass();
		$api->name = 'Itthinx Updates';
		$api->version = '1.0.0';
		$api->download_link = esc_url( 'https://service.itthinx.com/itthinx-updates.zip' );
		return $api;
	}
	add_filter( 'plugins_api', 'itthinx_updates', 10, 3 );
}

if ( !class_exists( 'Itthinx_Updates' ) && !function_exists( 'itthinx_updates_install' ) ) {
	function itthinx_updates_install() {
		$active_plugins = apply_filters( 'active_plugins', get_option('active_plugins' ) );
		if ( in_array( 'itthinx-updates/itthinx-updates.php', $active_plugins ) ) {
			return;
		}

		$slug = 'itthinx-updates';
		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug );
		$activate_url = 'plugins.php?action=activate&plugin=' . urlencode( 'itthinx-updates/itthinx-updates.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode( wp_create_nonce( 'activate-plugin_itthinx-updates/itthinx-updates.php' ) );

		$message = '<a href="' . esc_url( $install_url ) . '">Please install the <strong>itthinx updates</strong></a> plugin to enable automatic updates for your plugins by itthinx.';
		$is_downloaded = false;
		$plugins = array_keys( get_plugins() );
		foreach ( $plugins as $plugin ) {
			if ( strpos( $plugin, 'itthinx-updates.php' ) !== false ) {
				$is_downloaded = true;
				$message = '<a href="' . esc_url( admin_url( $activate_url ) ) . '">Please activate the <strong>itthinx updates</strong></a> plugin to enable automatic updates for your plugins by itthinx.';
			}
		}
		echo '<div class="updated fade"><p>' . $message . '</p></div>' . "\n";
	}
	add_action( 'admin_notices', 'itthinx_updates_install' );
}
