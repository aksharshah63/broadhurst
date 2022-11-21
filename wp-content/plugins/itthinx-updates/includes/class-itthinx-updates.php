<?php
class Itthinx_Updates {

	/**
	 * Plugin initialization.
	 */
	public static function init() {
		$proceed = true;
		if ( is_multisite() && !is_network_admin() ) {
			remove_action( 'admin_notices', 'itthinx_updates_install' );
			if ( !function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			if ( !is_plugin_active_for_network( plugin_basename( ITTHINX_UPDATES_FILE ) ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
				$proceed = false;
			}
		}
		load_plugin_textdomain( ITTHINX_UPDATES_PLUGIN_DOMAIN, null, 'itthinx-updates/languages' );
		if ( $proceed ) {
			if ( is_admin() ) {
				require_once 'class-itthinx-updates-update.php';
				require_once 'class-itthinx-updates-admin.php';
				require_once ITTHINX_UPDATES_PLUGIN_DIR . '/log/class-itthinx-updates-log.php';
				add_action( 'init', array( __CLASS__, 'wp_init' ) );
				$service_key = Itthinx_Updates::get_service_key( '' );
				if ( empty( $service_key ) ) {
					add_action( 'admin_notices', array( __CLASS__, 'admin_notices_service_key' ) );
					if ( is_multisite() ) {
						add_action( 'network_admin_notices', array( __CLASS__, 'admin_notices_service_key' ) );
					}
				}
			}
			// @see Itthinx_Updates_Bridge
// 			add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'pre_set_site_transient_update_plugins' ), 20, 1 );
// 			add_filter( 'site_transient_update_plugins', array( __CLASS__, 'site_transient_update_plugins' ), 20, 1 );
		}
	}

	/**
	 * Show the network activation required error message.
	 */
	public static function admin_notices() {
		echo '<div class="error">';
		echo __( 'The <strong>Itthinx Updates</strong> plugin must be network activated for this multisite.', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</div>';
	}

	/**
	 * Show the service key required error message.
	 */
	public static function admin_notices_service_key() {

		if ( is_multisite() && ( !is_super_admin() || !is_network_admin() ) ) {
			return;
		}

		if ( $screen = get_current_screen() ) {
			if ( ( $screen->id == 'plugins_page_itthinx-updates' ) || ( $screen->id == 'plugins_page_itthinx-updates-network' ) ) {
				return;
			}
		}

		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="error">';
			echo sprintf(
				__( 'Please enter a valid <a href="%s">service key</a> to enable automatic updates with the <strong>Itthinx Updates</strong> plugin.', ITTHINX_UPDATES_PLUGIN_DOMAIN ),
				is_multisite() ? esc_attr( admin_url( 'network/plugins.php?page=itthinx-updates' ) ) : esc_attr( admin_url( 'plugins.php?page=itthinx-updates' ) )
			);
			echo '</div>';
		}
	}

	/**
	 * Adds plugin update bridges for registered plugins.
	 */
	public static function wp_init() {
		global $itthinx_plugins;
		self::network_add_plugins();
		if ( !empty( $itthinx_plugins ) && is_array( $itthinx_plugins ) ) {
			require_once 'class-itthinx-updates-bridge.php';
			foreach( $itthinx_plugins as $itthinx_plugin ) {
				$u = new Itthinx_Updates_Bridge( $itthinx_plugin );
			}
		}
	}

	/**
	 * Check all plugins in multisite so that even inactive plugins produce
	 * an update notification. In case they are not network activated but
	 * active on some sites, the network admin should be aware of updates.
	 */
	public static function network_add_plugins() {
		global $itthinx_plugins;
		if ( is_multisite() && is_network_admin() ) {
			if ( !function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			$plugins = get_plugins();
			if ( is_array( $plugins ) ) {
				foreach( $plugins as $plugin_basename => $plugin ) {
					$plugin_file = WP_PLUGIN_DIR . '/' . $plugin_basename;
					if ( isset( $plugin['Author'] ) && $plugin['Author'] == 'itthinx' ) {
						if ( !isset( $itthinx_plugins ) ) {
							$itthinx_plugins = array();
						}
						if ( !in_array( $plugin_file, $itthinx_plugins ) ) {
							$itthinx_plugins[] = $plugin_file;
						}
					}
				}
			}
		}
	}

	/**
	 * Not used.
	 * @param array $transient
	 * @return array
	 */
	public static function pre_set_site_transient_update_plugins( $transient ) {
		return $transient;
	}

	/**
	 * Not used.
	 * @param array $transient update_plugins
	 * @return array update_plugins transient
	 */
	public static function site_transient_update_plugins( $transient ) {
		return $transient;
	}

	public static function get_service_key( $default = null ) {
		$service_key = null;
		if ( is_multisite() ) {
			$service_key = get_site_option( 'itthinx_service_key', $default );
		} else {
			$service_key = get_option( 'itthinx_service_key', $default );
		}
		return $service_key;
	}

	public static function set_service_key( $service_key ) {
		if ( is_multisite() ) {
			delete_site_option( 'itthinx_service_key' );
			if ( !empty( $service_key ) ) {
				add_site_option( 'itthinx_service_key', $service_key );
			}
		} else {
			delete_option( 'itthinx_service_key' );
			if ( !empty( $service_key ) ) {
				add_option( 'itthinx_service_key', $service_key, '', 'no' );
			}
		}
	}
}
Itthinx_Updates::init();
