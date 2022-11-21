<?php
/**
 * class-groups-newsletters-woocommerce.php
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
 * @package groups-newsletters
 * @since 2.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots.
 */
class Groups_Newsletters_WooCommerce {

	/**
	 * Hooks and requirement checks.
	 */
	public static function init() {
		if ( self::has_woocommerce() ) {
			$options = get_option( 'woocommerce-groups-newsletters', null );
			$checkout_opt_in = isset( $options['checkout-opt-in'] ) ? $options['checkout-opt-in'] : true;
			if ( $checkout_opt_in ) {
				require_once GROUPS_NEWSLETTERS_WOOCOMMERCE_CORE_LIB . '/class-groups-newsletters-woocommerce-checkout.php';
			}
			if ( is_admin() ) {
				require_once GROUPS_NEWSLETTERS_WOOCOMMERCE_ADMIN_LIB . '/class-groups-newsletters-woocommerce-admin.php';
			}
		}
	}

	/**
	 * Check if we have WooCommerce.
	 *
	 * @return boolean
	 */
	public static function has_woocommerce() {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
			$active_plugins = array_merge( $active_plugins, $active_sitewide_plugins );
		}
		if ( in_array( 'woocommerce-groups-newsletters/woocommerce-groups-newsletters.php', $active_plugins ) ) {
			self::deactivate_legacy();
		}
		return in_array( 'woocommerce/woocommerce.php', $active_plugins );
	}

	/**
	 * Deactivate the legacy integration plugin.
	 */
	public static function deactivate_legacy() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( array( 'woocommerce-groups-newsletters/woocommerce-groups-newsletters.php' ) );
	}
}
Groups_Newsletters_WooCommerce::init();
