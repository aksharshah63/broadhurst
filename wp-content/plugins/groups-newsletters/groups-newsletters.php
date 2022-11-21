<?php
/**
 * groups-newsletters.php
 *
 * Copyright (c) 2013-2021 www.itthinx.com
 *
 * =============================================================================
 *
 *                             LICENSE RESTRICTIONS
 *
 *           This plugin is provided subject to the license granted.
 *              Unauthorized use and distribution is prohibited.
 *                     See COPYRIGHT.txt and LICENSE.txt.
 *
 * This plugin relies on code and/or resources that are NOT licensed under the
 * GNU General Public License. Files licensed under the GNU General Public
 * License state so explicitly in their header, unless the license is implied.
 *
 * =============================================================================
 *
 * You MUST be granted a license by the copyright holder for those parts that
 * are not provided under the GPLv3 license.
 *
 * If you have not been granted a license DO NOT USE this plugin until you have
 * BEEN GRANTED A LICENSE.
 *
 * Use of this plugin without a granted license constitutes an act of COPYRIGHT
 * INFRINGEMENT and LICENSE VIOLATION and may result in legal action taken
 * against the offending party.
 *
 * Being granted a license is GOOD because you will get support and contribute
 * to the development of useful free and premium themes and plugins that you
 * will be able to enjoy.
 *
 * Thank you!
 *
 * Visit www.itthinx.com for more information.
 *
 * =============================================================================
 *
 * This code is released under the GNU General Public License.
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
 *
 * Plugin Name: Groups Newsletters
 * Plugin URI: https://www.itthinx.com/shop/groups-newsletters/
 * Description: Newsletter Campaigns for Subscribers and <a href="https://www.itthinx.com/plugins/groups/">Groups</a>. Supports newsletter sign-ups at checkout, to let customers subscribe during checkout with WooCommerce.
 * Author: itthinx
 * Author URI: https://www.itthinx.com
 * WC requires at least: 5.0
 * WC tested up to: 5.6
 * Version: 2.1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GROUPS_NEWSLETTERS_PLUGIN_VERSION', '2.1.0' );
if ( !function_exists( 'itthinx_plugins' ) ) {
	require_once 'itthinx/itthinx.php';
}
itthinx_plugins( __FILE__ );
define( 'GROUPS_NEWSLETTERS_PLUGIN_DOMAIN',  'groups-newsletters' );
define( 'GROUPS_NEWSLETTERS_PLUGIN_FILE',    __FILE__ );
define( 'GROUPS_NEWSLETTERS_PLUGIN_URL',     trailingslashit( plugins_url( 'groups-newsletters' ) ) );
define( 'GROUPS_NEWSLETTERS_FILE',           __FILE__ );
define( 'GROUPS_NEWSLETTERS_DIR',            untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'GROUPS_NEWSLETTERS_ADMIN_LIB',      GROUPS_NEWSLETTERS_DIR . '/lib/admin' );
define( 'GROUPS_NEWSLETTERS_CORE_LIB',       GROUPS_NEWSLETTERS_DIR . '/lib/core' );
define( 'GROUPS_NEWSLETTERS_EXT_LIB',        GROUPS_NEWSLETTERS_DIR . '/lib/ext' );
define( 'GROUPS_NEWSLETTERS_VIEWS_LIB',      GROUPS_NEWSLETTERS_DIR . '/lib/views' );
define( 'GROUPS_NEWSLETTERS_TEMPLATES',      GROUPS_NEWSLETTERS_DIR . '/templates' );
define( 'GROUPS_NEWSLETTERS_INCLUDES',       GROUPS_NEWSLETTERS_DIR . '/includes' );
define( 'GROUPS_NEWSLETTERS_WOOCOMMERCE_DIR',       GROUPS_NEWSLETTERS_DIR . '/woocommerce' );
define( 'GROUPS_NEWSLETTERS_WOOCOMMERCE_ADMIN_LIB', GROUPS_NEWSLETTERS_WOOCOMMERCE_DIR . '/admin' );
define( 'GROUPS_NEWSLETTERS_WOOCOMMERCE_CORE_LIB',  GROUPS_NEWSLETTERS_WOOCOMMERCE_DIR . '/core' );
require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-controller.php';
