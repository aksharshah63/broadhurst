<?php
class Itthinx_Updates_Bridge {

	const UPDATE_SERVICE_URL = 'https://service.itthinx.com/update-service.php';

	const PRIORITY_UPDATE_PLUGINS = 999;
	const PRIORITY_PLUGINS_API    = 999;

	private $version     = null;
	private $file        = null;
	private $plugin      = null;
	private $basename    = null;
	private $service_key = null;

	public function __construct( $file ) {
		$this->file = $file;
		$this->plugin = plugin_basename( $file );
		$this->basename = basename( $file, '.php' );
		if ( !function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data( $this->file );
		if ( isset( $plugin_data['Version'] ) ) {
			$this->version = $plugin_data['Version'];
		}
		if ( $this->version ) {
			$this->service_key = Itthinx_Updates::get_service_key();
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_site_transient_update_plugins' ), self::PRIORITY_UPDATE_PLUGINS );
			add_filter( 'plugins_api', array( $this, 'plugins_api'), self::PRIORITY_PLUGINS_API, 3 );
		}
	}

	/**
	 * Adds the plugin info to the update_plugins transient if a new version is available.
	 * @param array $value update_plugins transient
	 * @return (possibly modified) update_plugins transient
	 */
	public function pre_set_site_transient_update_plugins( $value ) {
		$info = $this->get_info();
		if ( $info ) {
			if ( isset( $info->new_version ) && ( version_compare( $this->version , $info->new_version ) < 0 ) ) {
				$value->response[$this->plugin] = $info;
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
	public function plugins_api( $result, $action, $args ) {
		if ( $action == 'plugin_information' ) {
			if ( $args->slug === dirname( $this->plugin ) ) {
				$result = false;
				$info = $this->get_info();
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
	public function get_info() {
		$result = null;
		$args = array(
			'body' => array(
				'action'      => 'info',
				'plugin'      => $this->basename,
				'service_key' => $this->service_key
			)
		);
		$request = wp_remote_post( self::UPDATE_SERVICE_URL, $args );
		if ( !is_wp_error( $request ) && wp_remote_retrieve_response_code( $request ) === 200) {
			$result = unserialize( $request['body'] );
			if ( isset( $result->download_link ) ) {
				if ( !empty( $this->service_key ) ) {
					$sep = '?';
					if ( parse_url( $result->download_link, PHP_URL_QUERY ) ) {
						$sep = '&';
					}
					$result->download_link .= $sep . 'service_key=' . $this->service_key;
					if ( !isset( $result->package ) ) {
						$result->package = $result->download_link;
					} else {
						$result->package .= $sep . 'service_key=' . $this->service_key;
					}
				}
			}
			Itthinx_Updates_Log::log(
				sprintf(
					'Successfully retrieved info, URL = %s, Parameters = %s, Response = %s',
					self::UPDATE_SERVICE_URL,
					var_export( $args, true ),
					var_export( $request, true )
				),
				Itthinx_Updates_Log::INFO
			);
		} else {
			Itthinx_Updates_Log::log(
				sprintf(
					'Failed to retrieve info, URL = %s, Parameters = %s, Response = %s',
					self::UPDATE_SERVICE_URL,
					var_export( $args, true ),
					var_export( $request, true )
				),
				Itthinx_Updates_Log::ERROR
			);
		}
		return $result;
	}

	/**
	 * Retrieves plugin list.
	 * @return array or null
	 */
	public static function get_plugins() {
		$result = null;
		$args = array(
			'body' => array(
				'action' => 'plugins'
			)
		);
		$request = wp_remote_post( self::UPDATE_SERVICE_URL, $args );
		if ( !is_wp_error( $request ) && wp_remote_retrieve_response_code( $request ) === 200) {
			$result = unserialize( $request['body'] );
			Itthinx_Updates_Log::log(
				sprintf(
					'Successfully retrieved plugins, URL = %s, Parameters = %s, Response = %s',
					self::UPDATE_SERVICE_URL,
					var_export( $args, true ),
					var_export( $request, true )
				),
				Itthinx_Updates_Log::INFO
			);
		} else {
			Itthinx_Updates_Log::log(
				sprintf(
					'Failed to retrieve plugins, URL = %s, Parameters = %s, Response = %s',
					self::UPDATE_SERVICE_URL,
					var_export( $args, true ),
					var_export( $request, true )
				),
				Itthinx_Updates_Log::ERROR
			);
		}
		return $result;
	}

	/**
	 * Plugin info request.
	 * @param string $plugin
	 * @return object plugin information when successfully retrieved, null otherwise
	 */
	public static function get_plugin_info( $plugin ) {
		$result = null;
		$service_key = Itthinx_Updates::get_service_key();
		$args = array(
			'body' => array(
				'action'      => 'info',
				'plugin'      => $plugin,
				'service_key' => $service_key
			)
		);
		$request = wp_remote_post( self::UPDATE_SERVICE_URL, $args );
		if ( !is_wp_error( $request ) && wp_remote_retrieve_response_code( $request ) === 200) {
			$result = unserialize( $request['body'] );
			if ( isset( $result->download_link ) ) {
				if ( !empty( $service_key ) ) {
					$sep = '?';
					if ( parse_url( $result->download_link, PHP_URL_QUERY ) ) {
						$sep = '&';
					}
					$result->download_link .= $sep . 'service_key=' . $service_key;
					if ( !isset( $result->package ) ) {
						$result->package = $result->download_link;
					} else {
						$result->package .= $sep . 'service_key=' . $service_key;
					}
				}
			}
			Itthinx_Updates_Log::log(
				sprintf(
					'Successfully retrieved plugin info, URL = %s, Parameters = %s, Response = %s',
					self::UPDATE_SERVICE_URL,
					var_export( $args, true ),
					var_export( $request, true )
				),
				Itthinx_Updates_Log::INFO
			);
		} else {
			Itthinx_Updates_Log::log(
				sprintf(
					'Failed to retrieve plugin info, URL = %s, Parameters = %s, Response = %s',
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
// Itthinx_Updates_Bridge::init();
