<?php
/**
 * class-groups-file-access-session.php
 *
 * Copyright (c) "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-file-access
 * @since groups-file-access 1.5.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GFA session handling so we can access the user ID within a session
 * even if the authentication cookie is not there.
 */
class Groups_File_Access_Session {

	/**
	 * Used to identify myself if I am the session creator.
	 *
	 * @var string
	 */
	const CREATOR = 'gfa_creator';

	/**
	 * Key for the user ID stored in the session.
	 *
	 * @var string
	 */
	const USER_ID = 'gfa_user_id';

	/**
	 * Session timeout in seconds.
	 *
	 * @var int
	 */
	public static $timeout = I_Groups_File_Access::SESSION_ACCESS_TIMEOUT_DEFAULT;

	/**
	 * Transient prefix used to identify sessions temporarily.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'gfsid-';

	/**
	 * Transient prefix used to relate gfsid and session data.
	 *
	 * @var string
	 */
	const TRANSIENT_MAP_PREFIX = 'gfmid-';

	/**
	 * Transient deletion interval.
	 *
	 * @var int
	 */
	const SCHEDULE = 660;

	/**
	 * @var string URL
	 */
	private $original_url = null;

	/**
	 * @var int timestamp
	 */
	private $time = null;

	/**
	 * @var string session ID
	 */
	private $id = null;

	/**
	 * @var int user ID
	 */
	private $user_id = null;

	/**
	 * @var string URL
	 */
	private $url = null;

	public function __construct( $url ) {
		$user_id = intval( get_current_user_id() );
		if ( $o = get_transient( self::get_transient_name( $user_id, $url ) ) ) {
			$this->original_url = $o->original_url;
			$this->time = $o->time;
			$this->id = $o->id;
			$this->user_id = $o->user_id;
			$this->url = $o->url;
		} else {
			$t = time();
			$this->original_url = $url;
			$this->time         = time();
			if ( $user_id ) {
				$separator = '?';
				$url_query = parse_url( $url, PHP_URL_QUERY );
				if ( !empty( $url_query ) ) {
					$separator = '&';
				}
				$this->id           = md5( $url . $user_id . $t );
				$this->user_id      = $user_id;
				$this->url          = $url . $separator . 'gfsid=' . $this->id;
			}
			$tname = self::get_transient_name( $user_id, $url );
			set_transient( $tname, $this, self::$timeout );
			set_transient( self::get_transient_map_name( $this->id ), $tname, self::$timeout );
		}
	}

	/**
	 * Provide the URL
	 *
	 * @return string
	 */
	public function get_url() {
		if ( $this->url !== null ) {
			return $this->url;
		} else {
			return $this->original_url;
		}
	}

	/**
	 * Provide the transient name for user ID and URL.
	 *
	 * @param int $user_id
	 * @param string $url
	 *
	 * @return string
	 */
	private static function get_transient_name( $user_id, $url ) {
		$t = sprintf( '%s%s', self::TRANSIENT_PREFIX, md5( intval( $user_id ) . $url ) );
		return $t;
	}

	/**
	 * Provide the transient map name for the $id.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	private static function get_transient_map_name( $id ) {
		$t = sprintf( '%s%s', self::TRANSIENT_MAP_PREFIX, $id );
		return $t;
	}

	/**
	 * Returns the user ID by gfsid. If valid, the transient lifetime is extended.
	 *
	 * @param string $gfsid
	 *
	 * @return int|null user ID
	 */
	public static function get_user_id( $gfsid ) {
		if ( $tname = get_transient( self::get_transient_map_name( $gfsid ) ) ) {
			if ( $o = get_transient( $tname ) ) {
				// extend lifetime
				set_transient( self::get_transient_map_name( $gfsid ), $tname, self::$timeout );
				set_transient( $tname, $o, self::$timeout );
				return intval( $o->user_id );
			}
		}
		return null;
	}

	/**
	 * Initializes enabled status and registers the transient deletion hook and schedules.
	 */
	public static function init() {
		add_action( 'groups_file_access_session_delete_transients', array( __CLASS__, 'delete_transients' ) );
		if ( !wp_next_scheduled( 'groups_file_access_session_delete_transients' ) ) {
			wp_schedule_single_event( time() + self::SCHEDULE, 'groups_file_access_session_delete_transients' );
		}
		$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
		self::$timeout = intval( isset( $options[Groups_File_Access::SESSION_ACCESS_TIMEOUT] ) ? $options[Groups_File_Access::SESSION_ACCESS_TIMEOUT] : Groups_File_Access::SESSION_ACCESS_TIMEOUT_DEFAULT );
	}

	/**
	 * Deletes expired transients.
	 *
	 * Transients are ridiculous, this should happen automatically for transients with a timeout.
	 */
	public static function delete_transients() {
		global $wpdb;
		$now = time();
		$q = sprintf(
			"SELECT * FROM $wpdb->options " .
			"WHERE " .
			"( option_name LIKE '_transient_timeout_%s%%' AND option_value < %d ) ".
			"OR " .
			"( option_name LIKE '_transient_timeout_%s%%' AND option_value < %d ) ",
			self::TRANSIENT_PREFIX,
			$now,
			self::TRANSIENT_MAP_PREFIX,
			$now
		);
		if ( $ts = $wpdb->get_results( $q ) ) {
			foreach( $ts as $t ) {
				$transient_name = str_replace( '_transient_timeout_', '', $t->option_name );
				delete_transient( $transient_name );
			}
		}
	}

	/**
	 * Returns true if general session access is enabled.
	 *
	 * @return boolean
	 */
	public static function enabled() {
		$options = get_option( Groups_File_Access::PLUGIN_OPTIONS , array() );
		return isset( $options[Groups_File_Access::SESSION_ACCESS] ) ? $options[Groups_File_Access::SESSION_ACCESS] : Groups_File_Access::SESSION_ACCESS_DEFAULT;
	}
}
Groups_File_Access_Session::init();
