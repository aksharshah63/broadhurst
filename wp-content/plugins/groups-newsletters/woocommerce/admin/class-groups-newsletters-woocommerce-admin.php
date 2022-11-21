<?php
/**
 * class-groups-newsletters-woocommerce-admin.php
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
 * WooCommerce Groups Newsletters settings.
 */
class Groups_Newsletters_WooCommerce_Admin {

	const NONCE = 'woocommerce-groups-newsletters-admin-nonce';

	/**
	 * Admin setup.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 50 );
	}

	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page(
			'woocommerce',
			__( 'Newsletters' ),
			__( 'Newsletters' ),
			'manage_woocommerce',
			'woocommerce_groups_newsletters',
			array( __CLASS__, 'woocommerce_groups_newsletters' )
		);
// 		add_action( 'admin_print_scripts-' . $admin_page, array( __CLASS__, 'admin_print_scripts' ) );
// 		add_action( 'admin_print_styles-' . $admin_page, array( __CLASS__, 'admin_print_styles' ) );
	}

	/**
	 * Renders the admin section.
	 */
	public static function woocommerce_groups_newsletters() {

		if ( !current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Access denied.', 'groups-newsletters' ) );
		}

		$options = get_option( 'woocommerce-groups-newsletters', null );
		if ( $options === null ) {
			if ( add_option( 'woocommerce-groups-newsletters', array(), null, 'no' ) ) {
				$options = get_option( 'woocommerce-groups-newsletters' );
			}
		}

		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST[self::NONCE], 'set' ) ) {
				$options['checkout-opt-in']         = isset( $_POST['checkout-opt-in'] );
				$options['checkout-opt-in-default'] = isset( $_POST['checkout-opt-in-default'] );
				$options['checkout-opt-in-label']   = trim( wp_strip_all_tags( $_POST['checkout-opt-in-label'] ) );
				update_option( 'woocommerce-groups-newsletters', $options );
			}
		}

		$checkout_opt_in         = isset( $options['checkout-opt-in'] ) ? $options['checkout-opt-in'] : true;
		$checkout_opt_in_default = isset( $options['checkout-opt-in-default'] ) ? $options['checkout-opt-in-default'] : true;
		$checkout_opt_in_label   = isset( $options['checkout-opt-in-label'] ) ? wp_strip_all_tags( $options['checkout-opt-in-label'] ) : __( 'Subscribe to our newsletters', 'groups-newsletters' );

		echo '<div class="woocommerce-groups-newsletters">';

		echo '<h2>' . esc_html__( 'Newsletters', 'groups-newsletters' ) . '</h2>';

		echo '<form action="" name="options" method="post">';
		echo '<div>';

		echo '<p>';
		echo '<label>';
		printf( '<input name="%s" type="checkbox" %s />', 'checkout-opt-in', $checkout_opt_in ? ' checked="checked" ' : '' );
		echo ' ';
		esc_html_e( 'Customers can subscribe to newsletters at checkout', 'groups-newsletters' );
		echo '</label>';
		echo '</p>';
		echo '<p class="description">';
		esc_html_e( 'Enable this option to allow customers to subscribe to newsletters at checkout.', 'groups-newsletters' );
		echo '</p>';

		echo '<p>';
		echo '<label>';
		printf( '<input name="%s" type="checkbox" %s />', 'checkout-opt-in-default', $checkout_opt_in_default ? ' checked="checked" ' : '' );
		echo ' ';
		esc_html_e( 'Checked by default', 'groups-newsletters' );
		echo '</label>';
		echo '</p>';
		echo '<p class="description">';
		esc_html_e( 'The option to subscribe to newsletters at checkout is checked by default.', 'groups-newsletters' );
		echo '</p>';

		echo '<p>';
		echo '<label>';
		esc_html_e( 'Label', 'groups-newsletters' );
		echo ' ';
		printf( '<input style="width:62%%;" name="%s" type="text" value="%s" />', 'checkout-opt-in-label', esc_attr( $checkout_opt_in_label ) );
		echo '</label>';
		echo '</p>';
		echo '<p class="description">';
		printf( __( 'The label text for the newsletter subscription option presented at checkout. The default is <code>%s</code>.', 'groups-newsletters' ), esc_html__( 'Subscribe to our newsletters', 'groups-newsletters' ) );
		echo '</p>';

		echo '<p>';
		echo wp_nonce_field( 'set', self::NONCE, true, false );
		echo '<input class="button" type="submit" name="submit" value="' . esc_attr__( 'Save', 'groups-newsletters' ) . '"/>';
		echo '</p>';
		echo '</div>';

		echo '</form>';

		echo '</div>'; // .woocommerce-groups-newsletters

	}
}
Groups_Newsletters_WooCommerce_Admin::init();
