<?php
/**
 * Logger
 */
class Itthinx_Updates_Log {

	const INFO    = 'INFO';
	const WARNING = 'WARNING';
	const ERROR   = 'ERROR';

	const LOGFILE_PREFIX = 'log-';

	public static function enable( $enable ) {
		$enable = $enable ? true : false;
		if ( is_multisite() ) {
			delete_site_option( 'itthinx_updates_log' );
			add_site_option( 'itthinx_updates_log', $enable );
		} else {
			delete_option( 'itthinx_updates_log' );
			add_option( 'itthinx_updates_log', $enable, '', 'no' );
		}
	}

	public static function enabled() {
		if ( is_multisite() ) {
			$enabled = get_site_option( 'itthinx_updates_log' );
		} else {
			$enabled = get_option( 'itthinx_updates_log' );
		}
		return $enabled ? true : false;
	}

	public static function get_logfile() {
		return ITTHINX_UPDATES_LOG_DIR . '/' . self::get_logfilename();
	}

	private static function get_logfilename() {
		$logfile = null;
		if ( is_multisite() ) {
			$logfile = get_site_option( 'itthinx_updates_logfilename', $logfile );
		} else {
			$logfile = get_option( 'itthinx_updates_logfilename', $logfile );
		}
		if ( empty( $logfile ) ) {
			$logfile = self::set_logfilename();
		}
		return $logfile;
	}

	private static function set_logfilename() {
		$logfile = self::LOGFILE_PREFIX . md5( rand() ) . '.log';
		if ( is_multisite() ) {
			delete_site_option( 'itthinx_updates_logfilename' );
			if ( !empty( $logfile ) ) {
				add_site_option( 'itthinx_updates_logfilename', $logfile );
			}
		} else {
			delete_option( 'itthinx_updates_logfilename' );
			if ( !empty( $logfile ) ) {
				add_option( 'itthinx_updates_logfilename', $logfile, '', 'no' );
			}
		}
		return $logfile;
	}

	/**
	 * Log message.
	 * 
	 * Example:
	 * Itthinx_Updates_Log::log( sprintf( '%s : %s', __METHOD__, var_export( $foo, true ) ) );
	 * 
	 * @param string $message
	 * @param string $level (optional) self::INFO (default), self::WARNING or self::ERROR
	 */
	public static function log( $message, $level = self::INFO ) {
		if ( self::enabled() ) {
			$now = date( 'Y-m-d H:i:s O', time() );
			switch( $level ) {
				case self::WARNING :
				case self::ERROR :
					break;
				default :
					$level = self::INFO;
			}
			$log = sprintf( "%s - %s : %s\r\n", $now, $level, $message );
			if ( !@error_log( $log, 3, self::get_logfile() ) ) {
				error_log(  $log );
			}
		}
	}
}

