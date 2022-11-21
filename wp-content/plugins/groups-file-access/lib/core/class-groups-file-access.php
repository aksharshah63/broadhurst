<?php
/**
 * class-groups-file-access.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-file-access
 * @since groups-file-access 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

require_once GFA_CORE_LIB . '/i-groups-file-access.php';
/**
 * Core plugin controller.
 */
class Groups_File_Access implements I_Groups_File_Access {

	// info/warnings/errors
	public static $admin_messages = array();

	// default URL parameter
	private static $parameter = 'gfid';

	/**
	 * Cache-safe switching.
	 *
	 * @param int $blog_id
	 */
	public static function switch_to_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		// Clear cache after switching blog id to avoid using another blog's values.
		// See wp-includes/cache.php
		// See also https://core.trac.wordpress.org/ticket/14941
		if ( function_exists( 'wp_cache_switch_to_blog' ) ) {
			wp_cache_switch_to_blog( $blog_id ); // introduced in WP 3.5.0
		} else if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		} else if ( function_exists( 'wp_cache_reset' ) ) {
			wp_cache_reset(); // deprecated in WP 3.5.0
		}
	}

	/**
	 * Switch back. If anything is needed to be done in addition, do it here.
	 */
	public static function restore_current_blog() {
		restore_current_blog();
	}

	/**
	 * Tasks performed upon plugin activation.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$blog_ids = self::get_blogs();
			foreach ( $blog_ids as $blog_id ) {
				self::switch_to_blog( $blog_id );
				self::setup();
				self::restore_current_blog();
			}
		} else {
			self::setup();
		}
	}

	/**
	 * Run activation for new blog.
	 *
	 * @param int $blog_id
	 * @param int $user_id
	 */
	public static function wpmu_new_blog( $blog_id, $user_id ) {
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			if ( key_exists( 'groups/groups.php', $active_sitewide_plugins ) &&
				key_exists( 'groups-file-access/groups-file-access.php', $active_sitewide_plugins )
			) {
				self::switch_to_blog( $blog_id );
				self::setup();
				self::restore_current_blog();
			}
		}
	}

	/**
	 * Delete plugin data for a blog about to be deleted (multisite).
	 *
	 * @param int $blog_id
	 * @param boolean $drop
	 */
	public static function delete_blog( $blog_id, $drop = false ) {
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			if ( key_exists( 'groups/groups.php', $active_sitewide_plugins ) &&
				key_exists( 'groups-file-access/groups-file-access.php', $active_sitewide_plugins )
			) {
				self::switch_to_blog( $blog_id );
				self::delete_data();
				self::restore_current_blog();
			}
		}
	}

	/**
	 * Setup data, folders and database.
	 */
	public static function setup() {
		$options = get_option( self::PLUGIN_OPTIONS , null );
		if ( $options === null ) {
			$options = array();
			$options[self::KEY] = md5( rand() );
			$options[self::DELETE_DATA] = false;
			$options[self::DELETE_DATA_ON_DEACTIVATE] = false;
			// add the options and there's no need to autoload these
			add_option( self::PLUGIN_OPTIONS, $options, null, 'no' );
		}
		self::schema_update();
		self::folders_update();
	}

	/**
	 * Handles uploads folder, .htaccess and index.html creation.
	 */
	public static function folders_update() {
		require_once GFA_FILE_LIB . '/class-gfa-file-upload.php';
		// We can't use GFA_UPLOADS_DIR if we did switch_to_blog because it will
		// point to the main blog's upload dir.
		// See also https://core.trac.wordpress.org/ticket/14992
		$gfa_uploads_dir = gfa_uploads_dir();
		$options = get_option( self::PLUGIN_OPTIONS , null );
		if ( GFA_File_Upload::check_uploads( $gfa_uploads_dir ) ) {
			if ( !isset( $options[self::FOLDERS] ) ) {
				$options[self::FOLDERS] = array(
					$gfa_uploads_dir
				);
			} else {
				if ( !in_array( $gfa_uploads_dir, $options[self::FOLDERS] ) ) {
					$options[self::FOLDERS][] = $gfa_uploads_dir;
				}
			}
		} else {
			self::$admin_messages[] = "<div class='error'>" . sprintf( __( 'The <strong>Groups File Access</strong> plugin could not create the %s directory. Your server must have write permissions on its parent directory.', 'groups-file-access' ), GFA_UPLOADS_DIR ) . "</div>";
		}
		update_option( self::PLUGIN_OPTIONS, $options );
		if ( !GFA_File_Upload::check_htaccess( $gfa_uploads_dir ) ) {
			self::$admin_messages[] = "<div class='error'>" . sprintf( __( 'The <strong>Groups File Access</strong> plugin could not create the .htaccess file in the %s directory. This file is required to assure that unauthorized access to files is avoided.', 'groups-file-access' ), GFA_UPLOADS_DIR ) . "</div>";
		}
		if ( !GFA_File_Upload::check_index( $gfa_uploads_dir ) ) {
			self::$admin_messages[] = "<div class='error'>" . sprintf( __( 'The <strong>Groups File Access</strong> plugin could not create the index.html file in the %s directory. This file is used to hide the directory contents from prying eyes.', 'groups-file-access' ), GFA_UPLOADS_DIR ) . "</div>";
		}
	}

	/**
	 * Update schema with file access table.
	 */
	public static function schema_update() {
		global $wpdb;
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
		$errors = 0;
		if ( isset( $wpdb ) && function_exists( '_groups_get_tablename' ) ) {
			require_once GFA_FILE_LIB . '/class-gfa-schema.php';
			$schema = GFA_Schema::get_schema();
			foreach ( $schema as $tablename => $tabledef ) {
				$tablename = _groups_get_tablename( $tablename );
				if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $tablename . "'" ) != $tablename ) {
					$query = "CREATE TABLE $tablename ( $tabledef ) $charset_collate;";
					if ( !$wpdb->query( $query ) ) {
						self::$admin_messages[] = "<div class='error'>" . sprintf( __( 'The <strong>Groups File Access</strong> plugin could not create the <code>%s</code> table.', 'groups-file-access' ), $tablename ) . "</div>";
						$errors++;
					}
				}
			}
			if ( $errors == 0 ) {
				$options = get_option( self::PLUGIN_OPTIONS , array() );
				$options[self::SCHEMA_UPDATED] = true;
				$options[self::SCHEMA_VERSION] = GFA_Schema::get_version();
				update_option( self::PLUGIN_OPTIONS, $options );
			}
		}
	}

	/**
	 * Extension update procedure, trigger schema update if needed.
	 */
	public static function update() {
		$options = get_option( self::PLUGIN_OPTIONS , null );
		if ( $options !== null ) {
			$schema_version = isset( $options[self::SCHEMA_VERSION] ) ? $options[self::SCHEMA_VERSION] : null;
			if ( $schema_version !== null  ) {
				require_once GFA_FILE_LIB . '/class-gfa-schema.php';
				$new_version = GFA_Schema::get_version();
				if ( version_compare( $schema_version, $new_version ) !== 0 ) {
					$success = GFA_Schema::update( $schema_version );
					if ( $success ) {
						$options[self::SCHEMA_VERSION] = $new_version;
						update_option( self::PLUGIN_OPTIONS, $options );
						error_log( sprintf( 'Groups File Access has successfully updated its schema from %d to %d.', $schema_version, $new_version ) );
					}
				}
			}
		}
	}

	/**
	 * On deactivation hook.
	 *
	 * Depends on Groups so don't go here unless it's activated.
	 */
	public static function deactivate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$options = get_option( self::PLUGIN_OPTIONS , array() );
			if ( isset( $options[self::NETWORK_DELETE_DATA_ON_DEACTIVATE] ) && $options[self::NETWORK_DELETE_DATA_ON_DEACTIVATE] ) {
				$blog_ids = self::get_blogs();
				foreach ( $blog_ids as $blog_id ) {
					self::switch_to_blog( $blog_id );
					self::delete_data();
					self::restore_current_blog();
				}
			}
		} else {
			$options = get_option( self::PLUGIN_OPTIONS , array() );
			if ( isset( $options[self::DELETE_DATA_ON_DEACTIVATE] ) && $options[self::DELETE_DATA_ON_DEACTIVATE] ) {
				self::delete_data();
			}
		}
	}

	/**
	 * On uninstall hook.
	 *
	 * Deletes options and data.
	 * Anything used here depending on Groups, should make sure it is activated before using its functions.
	 */
	public static function uninstall() {
		if ( is_multisite() ) {
			$blog_ids = self::get_blogs();
			foreach ( $blog_ids as $blog_id ) {
				self::switch_to_blog( $blog_id );
				$options = get_option( self::PLUGIN_OPTIONS , array() );
				if ( isset( $options[self::DELETE_DATA] ) && $options[self::DELETE_DATA] ) {
					self::delete_data();
				}
				self::restore_current_blog();
			}
		} else {
			$options = get_option( self::PLUGIN_OPTIONS , array() );
			if ( isset( $options[self::DELETE_DATA] ) && $options[self::DELETE_DATA] ) {
				self::delete_data();
			}
		}
	}

	/**
	 * Deletes all plugin data and tables.
	 * Requires the Groups plugin to be activated to take effect.
	 */
	public static function delete_data() {
		global $wpdb;
		if ( function_exists( '_groups_get_tablename' ) ) {
			$tables = array(
				_groups_get_tablename( 'file' ),
				_groups_get_tablename( 'file_access' ),
				_groups_get_tablename( 'file_group' )
			);
			foreach ( $tables as $table ) {
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $table );
			}
			delete_option( self::PLUGIN_OPTIONS );
		}
	}

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			foreach ( self::$admin_messages as $msg ) {
				echo wp_kses_post( $msg );
			}
		}
	}

	/**
	 * Loads translations, hooked on init.
	 */
	public static function wp_init() {
		load_plugin_textdomain( 'groups-file-access', null, 'groups-file-access/languages' );
	}

	/**
	 * Admin init hook. Registers styles.
	 */
	public static function admin_init() {
		wp_register_style( 'groups_file_access_admin', GFA_PLUGIN_URL . 'css/gfa_admin.css', array(), GFA_PLUGIN_VERSION );
		wp_register_script(
			'gfa-admin',
			// trailingslashit( GFA_PLUGIN_URL ) . ( SCRIPT_DEBUG ? 'js/admin.js' : 'js/admin.min.js' ),
			trailingslashit( GFA_PLUGIN_URL ) . ( SCRIPT_DEBUG ? 'js/admin.js' : 'js/admin.js' ),
			array( 'jquery' ),
			GFA_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Initialize the plugin & file handler.
	 */
	public static function init() {
		global $groups_file_access_update;
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'network_admin_notices', array( __CLASS__, 'admin_notices' ) );
		if ( self::check_dependencies() ) {
			register_activation_hook( GFA_FILE, array( __CLASS__, 'activate' ) );
			register_deactivation_hook( GFA_FILE, array( __CLASS__, 'deactivate' ) );
			add_action( 'init', array( __CLASS__, 'wp_init' ) );
			add_action( 'wpmu_new_blog', array( __CLASS__, 'wpmu_new_blog' ), 10, 2 );
			add_action( 'delete_blog', array( __CLASS__, 'delete_blog' ), 10, 2 );
			require_once GFA_CORE_LIB . '/class-groups-file-access-session.php';
			require_once GFA_FILE_LIB . '/class-gfa-file-renderer.php';
			require_once GFA_VIEWS_LIB . '/class-gfa-shortcodes.php';
			add_action( 'groups_file_served', array( __CLASS__, 'groups_file_served' ), 10, 2 );
			add_action( 'plugins_loaded', array( __CLASS__, 'server' ) );
			// admin
			if ( is_admin() ) {
				require_once GFA_ADMIN_LIB . '/class-groups-file-access-admin.php';
				require_once GFA_ADMIN_LIB . '/class-groups-file-access-admin-user-profile.php';
				require_once GFA_ADMIN_LIB . '/files.php';

				add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
				add_action( 'groups_admin_menu', array( __CLASS__, 'groups_admin_menu' ) );
				add_action( 'groups_network_admin_menu', array( __CLASS__, 'groups_network_admin_menu' ) );
				//add_action( 'contextual_help', array( __CLASS__, 'contextual_help' ), 10, 3 );
				add_filter( 'plugin_action_links_'. plugin_basename( GFA_PLUGIN_FILE ), array( __CLASS__, 'admin_settings_link' ) );
				add_filter( 'network_admin_plugin_action_links_'. plugin_basename( GFA_PLUGIN_FILE ), array( __CLASS__, 'network_admin_settings_link' ) );
			}
			require_once GFA_ADMIN_LIB . '/class-groups-file-access-scan-import.php';
		}
		if ( !isset( $groups_file_access_update ) ) {
			$groups_file_access_update = false;
			self::update();
		}
	}

	private static function ob_start() {
		@ob_start();
	}

	private static function ob_stop_log( $reference = null ) {
		$ob = @ob_get_clean();
		if ( $ob !== false && strlen( $ob ) > 0 ) {
			$trace = substr( $ob, 0, self::MAX_TRACE );
			if ( strlen( $trace ) < strlen( $ob ) ) {
				$trace .= '...';
			}
			error_log( sprintf(
				'Groups File Access has observed undue output while attending a file request %s: %s',
				$reference !== null ? ' [' . esc_html( $reference ) . ']' : '',
				addslashes( $trace )
			) );
		}
	}

	/**
	 * Detects a file request and forwards it to the server if legitimate.
	 */
	public static function server() {

		if ( isset( $_GET[self::$parameter] ) ) {

			$source_file = null;
			$source_line = null;
			$headers_sent = headers_sent( $source_file, $source_line );
			if ( $headers_sent ) {
				error_log( sprintf(
					'Groups File Access has observed undue output before it could attend a file request, produced on line %d in %s',
					intval( $source_line ),
					$source_file
				) );
			}

			self::ob_start();

			$code = null;
			$file_id = intval( $_GET[self::$parameter] );
			global $wpdb;
			$protocol = $_SERVER["SERVER_PROTOCOL"];
			if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ) {
				$protocol = 'HTTP/1.0';
			}
			$file_table = _groups_get_tablename( 'file' );
			if ( $file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id=%d", $file_id ) ) ) {

				$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
				$amazon_s3  = isset( $options[Groups_File_Access::AMAZON_S3] ) ? $options[Groups_File_Access::AMAZON_S3] : Groups_File_Access::AMAZON_S3_DEFAULT;
				$file_meta_table = _groups_get_tablename( 'file_meta' );
				$type_query = $wpdb->prepare( "SELECT meta_value FROM $file_meta_table WHERE meta_key = 'type' AND file_id = %d", intval( $file_id ) );
				$type = $wpdb->get_var( $type_query );

				if ( file_exists( $file->path ) || ( $amazon_s3 && $type === 'amazon-s3' ) ) {

					$user_id     = get_current_user_id();
					$service_key = null;
					$gfsid       = null;

					// user access
					$can_access = self::can_access( $user_id, $file_id );

					// service key access
					if ( !$can_access ) {
						$service_key = isset( $_REQUEST['service_key'] ) ? $_REQUEST['service_key'] : null;
						if ( $service_key ) {
							$users = get_users( array( 'meta_key' => 'gfa_service_key', 'meta_value' => $service_key ) );
							if ( $user = array_shift( $users ) ) {
								$can_access = self::can_access( $user->ID, $file_id );
								if ( $can_access ) {
									$user_id = $user->ID;
								}
							}
						}
					}

					// session access
					if ( !$can_access) {
						if ( isset( $_REQUEST['gfsid'] ) ) {
							$tmp_user_id = Groups_File_Access_Session::get_user_id( $_REQUEST['gfsid'] );
							$can_access = self::can_access( $tmp_user_id, $file_id );
						}
					}

					if ( $can_access ) {
						self::ob_stop_log( __LINE__ );
						$service_action = isset( $_REQUEST['service_action'] ) ? $_REQUEST['service_action'] : self::SERVICE_ACTION_SERVE;
						if ( $service_action ) {
							if ( GFA_File_Renderer::serve( $file, GFA_UPLOADS_DIR ) !== false ) {
								// increment counter for user and file
								$file_access_table = _groups_get_tablename( 'file_access' );
								$wpdb->query( $wpdb->prepare( "INSERT INTO $file_access_table (file_id,user_id,count) VALUES (%d,%d,1) ON DUPLICATE KEY UPDATE count=count+1", $file->file_id, $user_id ) );
								do_action( 'groups_file_served', $file_id, $user_id );
							}
						} else {
							GFA_File_renderer::probe( $file, GFA_UPLOADS_DIR );
						}
					} else {
						self::ob_stop_log( __LINE__ );
						if ( !$user_id && ( $service_key === null ) && ( $gfsid === null ) ) {
							$options = get_option( I_Groups_File_Access::PLUGIN_OPTIONS , array() );
							$login_redirect = isset( $options[I_Groups_File_Access::LOGIN_REDIRECT] ) ? $options[I_Groups_File_Access::LOGIN_REDIRECT] : false;
							if ( $login_redirect ) {
								$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
								wp_redirect( wp_login_url( $current_url ) );
								exit;
							}
						}
						// no redirect or already logged in or invalid service key provided
						header( "$protocol 403 Forbidden" );
						$code = 403;
					}
				} else {
					self::ob_stop_log( __LINE__ );
					header( "$protocol 404 Not Found" );
					$code = 404;
				}
			} else {
				self::ob_stop_log( __LINE__ );
				header( "$protocol 404 Not Found" );
				$code = 404;
			}

			// IMPORTANT: NO ob_end_clean() at this point as it has already been handled in all cases above.

			if ( $code !== null ) {
				switch ( $code ) {
					case 403 :
						$title   = '403 Forbidden';
						$heading = 'Forbidden';
						$message = 'You don\'t have permission to access this resource on this server.';
						break;
					case 404 :
						$title = '404 Not Found';
						$heading = 'Not Found';
						$message = 'The requested URL was not found on this server.';
						break;
				}
				// @since 1.7.0
				$title   = apply_filters( 'groups_file_access_server_response_title', $title, $code, $file_id );
				$heading = apply_filters( 'groups_file_access_server_response_heading', $heading, $code, $file_id );
				$message = apply_filters( 'groups_file_access_server_response_message', $message, $code, $file_id );
				$document = apply_filters(
					'groups_file_access_server_response_document',
					'<!DOCTYPE HTML>' .
					'<html>' .
					'<head>' .
					'<title>%s</title>' . // $title
					'</head>' .
					'<body>' .
					'<h1>%s</h1>' . // $heading
					'<p>%s</p>' . // $message
					'</body>' .
					'</html>',
					$code,
					$file_id
				);
				printf(
					$document,
					$title,
					$heading,
					$message
				); // WPCS: XSS ok.
			}
			exit;
		}
	}

	/**
	 * Determine if a user can access a file.
	 *
	 * @param int $user_id
	 * @param int $file_id
	 *
	 * @return true if the user can access the file, otherwise false
	 */
	public static function can_access( $user_id, $file_id ) {
		global $wpdb;
		$can_access = false;
		// 1. check if the user belongs to an authorized group
		$file_group_table = _groups_get_tablename( 'file_group' );
		if ( $file_groups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $file_group_table WHERE file_id = %d", intval( $file_id ) ) ) ) {
			foreach ( $file_groups as $file_group ) {
				if ( Groups_User_Group::read( $user_id , $file_group->group_id ) ) {
					$can_access = true;
					break;
				}
			}
		}
		// 2. check the user's access count
		if ( $can_access ) {
			$file_table = _groups_get_tablename( 'file' );
			$file_access_table = _groups_get_tablename( 'file_access' );
			$user_file_access = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_access_table WHERE file_id = %d AND user_id = %d", intval( $file_id ), intval( $user_id ) ) );
			$max_count = $wpdb->get_var( $wpdb->prepare( "SELECT max_count FROM $file_table WHERE file_id = %d", intval( $file_id ) ) );
			if ( ( intval( $max_count ) !== 0 ) && $user_file_access && ( $user_file_access->count >= intval( $max_count ) ) ) {
				$can_access = false;
			}
		}
		$can_access = apply_filters( 'groups_file_access_can_access', $can_access, $user_id, $file_id );
		return $can_access;
	}

	/**
	 * Returns the number of times the user has accessed the file.
	 *
	 * @param int $user_id
	 * @param int $file_id
	 *
	 * @return int
	 */
	public static function get_count( $user_id, $file_id ) {
		global $wpdb;
		$file_access_table = _groups_get_tablename( 'file_access' );
		$user_file_access = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_access_table WHERE file_id = %d AND user_id = %d", intval( $file_id ), intval( $user_id ) ) );
		if ( $user_file_access ) {
			return intval( $user_file_access->count );
		} else {
			return 0;
		}
	}

	/**
	 * Returns the maximum number of accesses allowed per user for the given file.
	 *
	 * @param int $file_id
	 *
	 * @return int|boolean number of accesses allowed, 0 for unlimited, null on error
	 */
	public static function get_max_count( $file_id ) {
		global $wpdb;
		$file_table = _groups_get_tablename( 'file' );
		$max_count = $wpdb->get_var( $wpdb->prepare( "SELECT max_count FROM $file_table WHERE file_id = %d", intval( $file_id ) ) );
		if ( $max_count !== null ) {
			return intval( $max_count );
		} else {
			return null;
		}
	}

	/**
	 * Returns the number of remaining accesses on the file for the given user.
	 *
	 * @param int $user_id
	 * @param int $file_id
	 *
	 * @return int number of accesses remaining or INF if unlimited
	 */
	public static function get_remaining( $user_id, $file_id ) {
		$count = self::get_count( $user_id, $file_id );
		$max_count = self::get_max_count( $file_id );
		if ( self::can_access($user_id, $file_id)) {
			if ( $max_count === 0 ) {
				return INF;
			} else if ( $max_count === null ) {
				return 0;
			} else {
				return $max_count - $count;
			}
		} else {
			return 0;
		}
	}

	/**
	 * Checks if the Groups plugin is there and the uploads directory could be created.
	 *
	 * @param boolean $disable (optional) If true, disables the plugin if the dependencies are not met. Default: false.
	 *
	 * @return true on success
	 */
	public static function check_dependencies( $disable = false ) {
		$result = true;
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
			$active_plugins = array_merge( $active_plugins, $active_sitewide_plugins );
		}
		if ( !( $groups_is_active = in_array( 'groups/groups.php', $active_plugins ) ) ) {
			self::$admin_messages[] = "<div class='error'>" . sprintf( __( 'The <strong>Groups File Access</strong> plugin requires the <a href="https://www.itthinx.com/plugins/groups/" target="_blank">Groups</a> plugin. Please install and activate it.', 'groups-file-access' ), GFA_UPLOADS_DIR ) . "</div>";
		}
		if ( !$groups_is_active ) {
			if ( $disable ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				deactivate_plugins( array( GFA_FILE ) );
			}
			$result = false;
		}
		return $result;
	}

	/**
	 * Adds the File Access submenu item on the Groups menu.
	 *
	 * @return array
	 */
	public static function groups_admin_menu( $pages = array() ) {

		if ( ( file_exists( GFA_UPLOADS_DIR ) ) ) {
			$page = add_submenu_page(
				'groups-admin',
				__( 'Files', 'groups-file-access' ),
				__( 'Files', 'groups-file-access' ),
				GROUPS_ADMINISTER_GROUPS,
				'groups-admin-files',
				'groups_file_access_admin_files'
			);
			$pages[] = $page;
			add_action( 'load-' . $page, array( __CLASS__, 'contextual_help' ) );
			add_action( 'admin_print_styles-' . $page, array( 'Groups_Admin', 'admin_print_styles' ) );
			add_action( 'admin_print_scripts-' . $page, array( 'Groups_Admin', 'admin_print_scripts' ) );
			add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'admin_print_styles' ) );

			$page = add_submenu_page(
				'groups-admin',
				__( 'File Import', 'groups-file-access' ),
				__( 'File Import', 'groups-file-access' ),
				GROUPS_ADMINISTER_GROUPS,
				'groups-admin-import-files',
				array( 'Groups_File_Access_Scan_Import', 'admin_import_files' )
			);
			$pages[] = $page;
			add_action( 'load-' . $page, array( __CLASS__, 'contextual_help' ) );
			add_action( 'admin_print_styles-' . $page, array( 'Groups_Admin', 'admin_print_styles' ) );
			add_action( 'admin_print_scripts-' . $page, array( 'Groups_Admin', 'admin_print_scripts' ) );
			add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'admin_print_styles' ) );
		}

		$page = add_submenu_page(
			'groups-admin',
			__( 'File Access', 'groups-file-access' ),
			__( 'File Access', 'groups-file-access' ),
			GROUPS_ADMINISTER_OPTIONS,
			'groups-admin-file-access',
			array( 'Groups_File_Access_Admin', 'groups_admin_file_access' )
		);
		$pages[] = $page;
		add_action( 'load-' . $page, array( __CLASS__, 'contextual_help' ) );
		add_action( 'admin_print_styles-' . $page, array( 'Groups_Admin', 'admin_print_styles' ) );
		add_action( 'admin_print_scripts-' . $page, array( 'Groups_Admin', 'admin_print_scripts' ) );
		add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'admin_print_styles' ) );

		return $pages;
	}

	/**
	 * Network admin menu addition on Groups.
	 */
	public static function groups_network_admin_menu() {
		$page = add_submenu_page(
			'groups-network-admin',
			__( 'File Access', 'groups-file-access' ),
			__( 'File Access', 'groups-file-access' ),
			GROUPS_ADMINISTER_OPTIONS,
			'groups-network-admin-file-access',
			array( 'Groups_File_Access_Admin', 'groups_network_admin_file_access' )
		);
		// $pages[] = $page;
		add_action( 'admin_print_styles-' . $page, array( 'Groups_Admin', 'admin_print_styles' ) );
		add_action( 'admin_print_scripts-' . $page, array( 'Groups_Admin', 'admin_print_scripts' ) );
		add_action( 'admin_print_styles-' . $page, array( __CLASS__, 'admin_print_styles' ) );
	}

	/**
	 * Adds plugin links.
	 *
	 * @param array $links
	 * @param array $links with additional links
	 *
	 * @return array $links with additions
	 */
	public static function admin_settings_link( $links ) {
		$links[] = '<a href="' . get_admin_url( null,'admin.php?page=groups-admin-file-access' ) . '">' . esc_html__( 'Settings', 'groups-file-access' ) . '</a>';
		$links[] = '<a href="' . get_admin_url( null,'admin.php?page=groups-admin-files' ) . '">' . esc_html__( 'Files', 'groups-file-access' ) . '</a>';
		return $links;
	}

	/**
	 * Adds network plugin settings link.
	 *
	 * @param array $links
	 *
	 * @return array $links with additions
	 */
	public static function network_admin_settings_link( $links ) {
		if ( is_network_admin() ) {
			$links[] = '<a href="' . network_admin_url( 'admin.php?page=groups-network-admin-file-access' ) . '">' . esc_html__( 'Settings', 'groups-file-access' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Loads styles for the Groups admin section.
	 *
	 * @see Groups_Admin::admin_menu()
	 */
	public static function admin_print_styles() {
		wp_enqueue_style( 'groups_file_access_admin' );
	}

	/**
	 * Contextual help - adds help tabs on admin screens.
	 */
	public static function contextual_help() {
		if ( $screen = get_current_screen() ) {
			$help = false;
			$help_title = __( 'Groups', GROUPS_PLUGIN_DOMAIN );
			$screen_id = $screen->base;
			$ids = array(
				'groups-admin-files' => __( 'Files', GROUPS_PLUGIN_DOMAIN ),
				'groups-admin-file-access' => __( 'File Access', GROUPS_PLUGIN_DOMAIN )
			);
			foreach ( $ids as $id => $title ) {
				$i = strpos( $screen_id, $id );
				if ( $i !== false ) {
					if ( $i + strlen( $id ) == strlen( $screen_id ) ) {
						$screen_id = $id;
						$help = true;
						$help_title = $title;
						break;
					}
				}
			}
			if ( $help ) {
				$help_content = '';
				switch ( $screen_id ) {
					case 'groups-admin-files' :
						require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
						$help_content .= GFA_Help::get_help( $screen_id );
						break;
					case 'groups-admin-file-access' :
						require_once GFA_VIEWS_LIB . '/class-gfa-help.php';
						$help_content .= GFA_Help::get_help( $screen_id );
						break;
				}
				$screen->add_help_tab(
					array(
						'id'      => $screen_id,
						'title'   => $help_title,
						'content' => $help_content
					)
				);
			}
		}
	}

	/**
	 * Sends HTML email.
	 * $message must use <br/> not \r\n as line breaks.
	 *
	 * @param string $email
	 * @param string $subject the email subject (do NOT pass it translated, it will be done here)
	 * @param string $message the email message (do NOT pass it translated, it will be done here)
	 * @param array $variables (optional) IPN variables used for token substitution
	 */
	public static function mail( $email, $subject, $message, $tokens = array() ) {

		require_once GFA_UTY_LIB . '/class-gfa-utility.php';

		// email headers
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset="' . get_option( 'blog_charset' ) . '"' . "\r\n";

		// translate
		$subject = __( $subject, 'groups-file-access' );
		$message = __( $message, 'groups-file-access' );

		$user = null;
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$tokens['user_id'] = $user->ID;
		} else {
			if ( isset( $tokens['user_id'] ) ) {
				$user = get_user_by( 'id', intval( $tokens['user_id'] ) );
			}
		}

		// token substitution
		$site_title = wp_specialchars_decode( get_bloginfo( 'blogname' ), ENT_QUOTES );
		$site_url   = get_bloginfo( 'url' );
		$tokens = array_merge(
			$tokens,
			array(
				'site_title' => $site_title,
				'site_url'   => $site_url,
				'username'   => ( $user ? esc_html( $user->user_login ) : __( '(unknown user)', 'groups-file-access' ) )
			)
		);
		foreach ( $tokens as $key => $value ) {
			$substitute = GFA_Utility::filter( $value );
			$subject    = str_replace( "[" . $key . "]", $substitute, $subject );
			$message    = str_replace( "[" . $key . "]", $substitute, $message );
		}
		@wp_mail( $email, wp_filter_nohtml_kses( $subject ), $message, $headers );
	}

	/**
	 * Remove the uninstall hook.
	 *
	 * @param string $file
	 * @param string|array $callback
	 */
	public static function remove_uninstall_hook( $file, $callback ) {
		$uninstallable_plugins = (array) get_option( 'uninstall_plugins' );
		unset( $uninstallable_plugins[plugin_basename($file)] );
		update_option('uninstall_plugins', $uninstallable_plugins);
	}

	/**
	 * Takes care of sending the notification email out.
	 *
	 * @param int $file_id
	 * @param int $user_id
	 */
	public static function groups_file_served( $file_id, $user_id ) {
		global $wpdb;
		$options = get_option( self::PLUGIN_OPTIONS , array() );
		$notify_admin  = isset( $options[self::NOTIFY_ADMIN] ) ? $options[self::NOTIFY_ADMIN] : self::NOTIFY_ADMIN_DEFAULT;
		$admin_email   = !empty( $options[self::ADMIN_EMAIL] ) ? $options[self::ADMIN_EMAIL] : get_bloginfo( 'admin_email' );
		$admin_subject = isset( $options[self::ADMIN_SUBJECT] ) ? esc_attr( wp_filter_nohtml_kses( $options[self::ADMIN_SUBJECT] ) ) : self::ADMIN_DEFAULT_SUBJECT;
		$admin_message = isset( $options[self::ADMIN_MESSAGE] ) ? $options[self::ADMIN_MESSAGE] : self::ADMIN_DEFAULT_MESSAGE;
		if ( $notify_admin ) {
			if ( !empty( $admin_email ) ) {
				$file_table = _groups_get_tablename( 'file' );
				if ( $file = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $file_table WHERE file_id=%d", $file_id ) ) ) {
					$base_url    = get_bloginfo( 'url' );
					$file_url    = GFA_File_Renderer::render_url( $file, $base_url );
					$ip_address  = isset( $_SERVER['REMOTE_ADDR'] ) ? strip_tags( trim( $_SERVER['REMOTE_ADDR'] ) ) : __( '(unknown)', 'groups-file-access' );
					$server_ip   = isset( $_SERVER['SERVER_ADDR'] ) ? strip_tags( trim( $_SERVER['SERVER_ADDR'] ) ) : __( '(unknown)', 'groups-file-access' );
					$referrer    = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : __( '(unknown)', 'groups-file-access' );
					$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( $_SERVER['REQUEST_URI'] ) : __( '(unknown)', 'groups-file-access' );
					$now         = time();
					$datetime    = gmdate( __( 'Y-m-d H:i:s', 'groups-file-access' ) , $now );
					$request = '';
					if ( !empty( $_REQUEST ) ) {
						$request_pairs = array();
						foreach( $_REQUEST as $key => $value ) {
							$request_pairs[] = esc_html( $key ) . ' = ' . esc_html( $value );
						}
						$request = implode( ', ', $request_pairs );
					}
					$tokens = array(
						'file_id'     => $file_id,
						'file_path'   => $file->path,
						'file_url'    => $file_url,
						'user_id'     => $user_id,
						'ip'          => $ip_address,
						'server_ip'   => $server_ip,
						'referrer'    => $referrer,
						'datetime'    => $datetime,
						'request'     => $request,
						'request_uri' => $request_uri
					);
					self::mail( $admin_email, $admin_subject, $admin_message, $tokens );
				}
			}
		}

	}

	/**
	 * Returns an array of blog_ids for current blogs.
	 *
	 * @return array of int with blog ids
	 */
	public static function get_blogs() {
		global $wpdb;
		$result = array();
		if ( is_multisite() ) {
			$blogs = $wpdb->get_results( $wpdb->prepare(
				"SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d AND archived = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC",
				$wpdb->siteid
			) );
			if ( is_array( $blogs ) ) {
				foreach( $blogs as $blog ) {
					$result[] = $blog->blog_id;
				}
			}
		} else {
			$result[] = get_current_blog_id();
		}
		return $result;
	}

	/**
	 * Provide the service key for the current user or the given user (creates the key if it does not already exist).
	 *
	 * @since 2.3.0
	 *
	 * @param int $user_id
	 *
	 * @return string|null
	 */
	public static function get_service_key( $user_id = null ) {
		$service_key = null;
		if ( $user_id === null ) {
			$user_id = get_current_user_id();
		} else {
			$user_id = intval( $user_id );
		}
		if ( $user_id ) {
			$service_key = get_user_meta( $user_id, 'gfa_service_key', true );
			if ( !$service_key ) {
				$service_key = md5( time() );
				add_user_meta( $user_id, 'gfa_service_key', $service_key );
			}
		}
		return $service_key;
	}
}
Groups_File_Access::init();
