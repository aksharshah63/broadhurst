<?php
/**
 * Plugin Name: Groups File Access
 * Plugin URI: https://www.itthinx.com/shop/groups-file-access/
 * Description: Groups File Access allows to restrict user access to files by group membership.
 * Version: 2.4.0
 * Author: itthinx
 * Author URI: https://www.itthinx.com
 * Text Domain: groups-file-access
 * Domain Path: /languages
 * License: Proprietary or GPLv3 with extensions and exceptions
 *
 * groups-file-access.php
 *
 * Copyright (c) 2012 - 2022 www.itthinx.com
 *
 * This software is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * Parts of this software are released under the GNU General Public License
 * Version 3.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This software relies on code that is NOT licensed under the GNU General
 * Public License. Files licensed under the GNU General Public License state
 * so explicitly in their header.
 *
 * =============================================================================
 *
 * You MUST be granted a license by the copyright holder to use this software.
 *
 * DO NOT USE this software unless you have BEEN GRANTED A LICENSE.
 *
 * Use of this software without a granted license constitutes an act of
 * COPYRIGHT INFRINGEMENT and LICENSE VIOLATION and may result in legal action
 * taken against the offending party.
 *
 * Being granted a license is GOOD because you will get support and contribute
 * to the development of useful free and premium software that you will be
 * able to enjoy.
 *
 * This software is legitimately distributed and supported on itthinx.com ONLY.
 *
 * Thank you!
 *
 * Visit www.itthinx.com for more information.
 *
 * =============================================================================
 *
 * This code is released under a proprietary license and parts under
 * the GNU General Public License Version 3.
 * The following additional terms apply to all files as per section
 * "7. Additional Terms." See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * included COPYRIGHT.txt and LICENSE.txt for more details.
 *
 * All legal, copyright and license notices and all author attributions
 * must be preserved in all files and user interfaces.
 *
 * Where modified versions of this material are allowed under the applicable
 * license, modified version must be marked as such and the origin of the
 * modified material must be clearly indicated, including the copyright
 * holder, the author and the date of modification and the origin of the
 * modified material.
 *
 * This material may not be used for publicity purposes and the use of
 * names of licensors and authors of this material for publicity purposes
 * is prohibited.
 *
 * The use of trade names, trademarks or service marks, licensor or author
 * names is prohibited unless granted in writing by their respective owners.
 *
 * Where modified versions of this material are allowed under the applicable
 * license, anyone who conveys this material (or modified versions of it) with
 * contractual assumptions of liability to the recipient, for any liability
 * that these contractual assumptions directly impose on those licensors and
 * authors, is required to fully indemnify the licensors and authors of this
 * material.
 *
 * This header and all notices must be kept intact.
 *
 * GPL-licensed parts : GPL-licensed files are ONLY THOSE THAT EXPLICITLY STATE
 * that they are released under the GNU General Public License in their header,
 * or those where the license is implied.
 *
 * Non-GPL-licensed parts : All other parts of this software including, but not
 * limited to algorithms, PHP code, Javascript code or CSS code, texts, images
 * and designs are proprietary and you MUST NOT distribute them and you MUST
 * NOT create or distribute derivative work based on them or including them.
 *
 * See LICENSE.txt for the full license.
 *
 * @author itthinx
 * @package groups-file-access
 * @since groups-file-access 1.0.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
define( 'GFA_PLUGIN_VERSION', '2.4.0' );
if ( !function_exists( 'itthinx_plugins' ) ) {
	require_once 'itthinx/itthinx.php';
}
itthinx_plugins( __FILE__ );
define( 'GFA_PLUGIN_DOMAIN', 'groups-file-access' );
define( 'GFA_PLUGIN_FILE', __FILE__ );
define( 'GFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GFA_FILE', __FILE__ );
define( 'GFA_DIR', WP_PLUGIN_DIR . '/groups-file-access' );
define( 'GFA_CORE_LIB', GFA_DIR . '/lib/core' );
define( 'GFA_AWS_LIB', GFA_DIR . '/vendor/aws' );
require_once GFA_CORE_LIB . '/boot.php';
