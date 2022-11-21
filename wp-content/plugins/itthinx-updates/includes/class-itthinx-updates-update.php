<?php
class Itthinx_Updates_Update {

	const UPDATE_SERVICE_URL = 'https://service.itthinx.com/update-service.php';

	private static $version = null;
	private static $file = null;
	private static $plugin = null;

	/**
	 * Update hooks.
	 */
	public static function init() {
		self::$file = ITTHINX_UPDATES_FILE;
		self::$version = ITTHINX_UPDATES_VERSION;
		self::$plugin = 'itthinx-updates';
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'pre_set_site_transient_update_plugins' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugins_api'), 10, 3 );
	}

	/**
	 * Adds the plugin info to the update_plugins transient if a new version is available.
	 * @param array $value update_plugins transient
	 * @return (possibly modified) update_plugins transient
	 */
	public static function pre_set_site_transient_update_plugins( $value ) {
		$info = self::get_info();
		if ( $info ) {
			if ( isset( $info->new_version ) && ( version_compare( self::$version , $info->new_version ) < 0 ) ) {
				$value->response[plugin_basename( self::$file )] = $info;
			}
		}
		return $value;
	}

	/**
	 * Returns plugin info when requested for this plugin, $result otherwise.
	 * @param object|boolean $result
	 * @param string $action
	 * @param array $args
	 * @return object|boolean plugin info for this plugin if requested, $result otherwise
	 */
	public static function plugins_api( $result, $action, $args ) {
		if ( $action == 'plugin_information' ) {
			if ( $args->slug === dirname( plugin_basename( self::$file ) ) ) {
				$result = false;
				$info = self::get_info();
				if ( $info ) {
					$result = $info;
				}
			}
		}
		return $result;
	}

	/**
	 * Retrieves plugin information from update server.
	 * @return object plugin information when successfully retrieved, null otherwise
	 */
	public static function get_info() {
		$result = null;
		$args = array(
			'body' => array(
				'action' => 'info',
				'plugin' => self::$plugin
			)
		);
		$request = wp_remote_post( self::UPDATE_SERVICE_URL, $args );
		if ( !is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200) {
			$result = unserialize( $request['body'] );
			Itthinx_Updates_Log::log(
				sprintf(
					'Successfully retrieved itthinx-updates info, URL = %s, Parameters = %s, Response = %s',
					self::UPDATE_SERVICE_URL,
					var_export( $args, true ),
					var_export( $request, true )
				),
				Itthinx_Updates_Log::INFO
			);
		} else {
			Itthinx_Updates_Log::log(
				sprintf(
					'Failed to retrieve itthinx-updates info, URL = %s, Parameters = %s, Response = %s',
					self::UPDATE_SERVICE_URL,
					var_export( $args, true ),
					var_export( $request, true )
				),
				Itthinx_Updates_Log::ERROR
			);
		}
		return $result;
	}

}
Itthinx_Updates_Update::init();
