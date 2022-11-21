<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Itthinx_Updates_Admin {

	public static function init() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_filter( 'network_admin_plugin_action_links_'. plugin_basename( ITTHINX_UPDATES_FILE ), array( __CLASS__, 'admin_settings_link' ) );
		} else {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_filter( 'plugin_action_links_'. plugin_basename( ITTHINX_UPDATES_FILE ), array( __CLASS__, 'admin_settings_link' ) );
		}

		if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'ix-force-update-check' ) && wp_verify_nonce( $_POST['itthinx-updates-force-check'], 'admin' ) ) {
			if ( current_user_can( 'update_plugins' ) ) {
				set_site_transient( 'update_plugins', new \stdClass() );
				wp_safe_redirect( admin_url( 'plugins.php?page=itthinx-updates' ) );
				exit;
			}
		}
	}

	public static function admin_settings_link( $links ) {
		if ( current_user_can( 'manage_options' ) ) {
			if ( is_multisite() ) {
				$links = array( '<a href="' . get_admin_url( null, 'network/plugins.php?page=itthinx-updates' ) . '">' . __( 'Settings', ITTHINX_UPDATES_PLUGIN_DOMAIN ) . '</a>' ) + $links;
			} else {
				$links = array( '<a href="' . get_admin_url( null, 'plugins.php?page=itthinx-updates' ) . '">' . __( 'Settings', ITTHINX_UPDATES_PLUGIN_DOMAIN ) . '</a>' ) + $links;
			}
		}
		return $links;
	}

	public static function admin_menu() {
		add_plugins_page(
			__( 'Itthinx Updates', ITTHINX_UPDATES_PLUGIN_DOMAIN ),
			__( 'Itthinx Updates', ITTHINX_UPDATES_PLUGIN_DOMAIN ),
			'manage_options',
			'itthinx-updates',
			array( __CLASS__, 'settings' )
		);
	}

	public static function settings() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Access denied.', ITTHINX_UPDATES_PLUGIN_DOMAIN ) );
		}

		if ( isset( $_POST['action'] ) && wp_verify_nonce( $_POST['itthinx-updates-settings'], 'admin' ) ) {
			switch( $_POST['action'] ) {
				case 'set' :
					$service_key = !empty( $_POST['service_key'] ) ? trim( $_POST['service_key'] ) : '';
					Itthinx_Updates::set_service_key( $service_key );
					break;
				case 'log' :
					$enable = !empty( $_POST['log_enable'] );
					Itthinx_Updates_Log::enable( $enable );
					break;
			}
		}

		echo '<h1>';
		echo __( 'Itthinx Updates', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</h1>';

		echo '<h2>';
		echo __( 'Service Key', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</h2>';

		$service_key = Itthinx_Updates::get_service_key( '' );
		echo '<div>';
		echo '<form name="settings" method="post" action="">';
		echo '<div class="itthinx-updates">';
		echo '<p>';
		echo '<label>';
		echo __( 'Service Key', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo ' ';
		echo sprintf( '<input style="display:block;width:80%%;" type="text" name="service_key" value="%s" placeholder="%s" />', esc_attr( $service_key ), esc_attr( __( 'Input your service key here &hellip;', ITTHINX_UPDATES_PLUGIN_DOMAIN ) ) );
		echo '</label>';
		echo '</p>';
		echo '<p>';
		echo __( 'Automatic updates require a valid user account on <a href="https://www.itthinx.com/">itthinx.com</a> and a service key.', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</p>';
		echo '<p>';
		echo __( 'To obtain your service key, visit the <a href="https://www.itthinx.com/service-key/">Service Key</a> page, log in and then copy the service key that appears on that page and paste it in the field above.', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</p>';
		wp_nonce_field( 'admin', 'itthinx-updates-settings', true, true );
		echo '<div class="buttons">';
		echo sprintf( '<input class="save button button-primary" type="submit" name="submit" value="%s" />', esc_attr( __( 'Save', ITTHINX_UPDATES_PLUGIN_DOMAIN ) ) );
		echo '<input type="hidden" name="action" value="set" />';
		echo '</div>'; // .buttons
		echo '</div>'; // .itthinx-updates
		echo '</form>';
		echo '</div>';

		if ( current_user_can( 'update_plugins' ) ) {

			echo '<h2>';
			echo __( 'Updates', ITTHINX_UPDATES_PLUGIN_DOMAIN );
			echo '</h2>';

			$update_data = wp_get_update_data();
			if ( !empty( $update_data['title'] ) ) {
				echo '<div class="itthinx-updates">';
				echo '<p>';
				echo esc_html( $update_data['title'] );
				echo '</p>';
				echo '</div>'; // .itthinx-updates
			}

			echo '<div>';
			echo '<form name="settings" method="post" action="">';
			echo '<div class="itthinx-updates">';
			echo '<p>';
			echo __( 'You can click the button to force WordPress to check for updates.', ITTHINX_UPDATES_PLUGIN_DOMAIN );
			echo '</p>';
			wp_nonce_field( 'admin', 'itthinx-updates-force-check', true, true );
			echo '<div class="buttons">';
			echo sprintf( '<input class="update button" type="submit" name="submit" value="%s" />', esc_attr( __( 'Check', ITTHINX_UPDATES_PLUGIN_DOMAIN ) ) );
			echo '<input type="hidden" name="action" value="ix-force-update-check" />';
			echo '</div>'; // .buttons
			echo '</div>'; // .itthinx-updates
			echo '</form>';
			echo '</div>';
		}

		echo '<h2>';
		echo __( 'Log', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</h2>';

		$enabled = Itthinx_Updates_Log::enabled();
		echo '<div>';
		echo '<form name="settings" method="post" action="">';
		echo '<div class="itthinx-updates">';
		echo '<p>';
		echo '<label>';
		printf( '<input type="checkbox" name="log_enable" value="1" %s />', $enabled ? ' checked="checked" ' : '' );
		echo ' ';
		echo __( 'Enable logging', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</label>';
		echo '</p>';
		echo '<p>';
		echo sprintf( __( 'If this option is enabled, certain update events are logged to:<br/><code>%s</code>', ITTHINX_UPDATES_PLUGIN_DOMAIN ), Itthinx_Updates_Log::get_logfile() );
		echo '</p>';
		echo '<p>';
		echo __( 'This option should only be enabled when necessary and the logfile should be removed once done, especially on a production site.', ITTHINX_UPDATES_PLUGIN_DOMAIN );
		echo '</p>';
		wp_nonce_field( 'admin', 'itthinx-updates-settings', true, true );
		echo '<div class="buttons">';
		echo sprintf( '<input class="save button button-primary" type="submit" name="submit" value="%s" />', esc_attr( __( 'Save', ITTHINX_UPDATES_PLUGIN_DOMAIN ) ) );
		echo '<input type="hidden" name="action" value="log" />';
		echo '</div>'; // .buttons
		echo '</div>'; // .itthinx-updates
		echo '</form>';
		echo '</div>';
	}
}
add_action( 'init', array( 'Itthinx_Updates_Admin', 'init' ) );
