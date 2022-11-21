<?php
/**
 * class-groups-newsletters-options.php
 *
 * Copyright (c) www.itthinx.com
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
 * @package groups-newsletters
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Groups Newsletters plugin options.
 */
class Groups_Newsletters_Options {

	const OPTIONS = 'groups_newsletters_options';

	/**
	 * Initialize options.
	 */
	public static function init() {
		$options = get_option( self::OPTIONS );
		if ( $options === false ) {
			$options = array();
			add_option( self::OPTIONS, $options, null, 'no' );
		}
	}

	/**
	 * Get options.
	 *
	 * @return array
	 */
	private static function get_options() {
		$options = get_option( self::OPTIONS );
		if ( $options === false ) {
			self::init();
			$options = get_option( self::OPTIONS );
		}
		return $options;
	}

	/**
	 * Returns an option value.
	 *
	 * @param string $option option key
	 * @param mixed $default default value when option is not set
	 * @return string option value
	 */
	public static function get_option( $option, $default = null ) {
		$options = self::get_options();
		$value = isset( $options[$option] ) ? $options[$option] : null;
		if ( $value === null ) {
			$value = $default;
		}
		return $value;
	}

	/**
	 * Updates an option.
	 *
	 * @param string $option option key
	 * @param mixed $value the option value
	 */
	public static function update_option( $option, $value ) {
		$options = self::get_options();
		$options[$option] = $value;
		update_option( self::OPTIONS, $options );
	}

	/**
	 * Deletes an option.
	 *
	 * @param string $option option key
	 */
	public static function delete_option( $option ) {
		$options = self::get_options();
		if ( isset( $options[$option] ) ) {
			unset( $options[$option] );
			update_option( self::OPTIONS, $options );
		}
	}

	/**
	 * Delete all options.
	 */
	public static function flush_options() {
		delete_option( self::OPTIONS );
	}
}
