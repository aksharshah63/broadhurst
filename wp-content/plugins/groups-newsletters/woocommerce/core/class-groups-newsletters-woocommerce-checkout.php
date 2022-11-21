<?php
/**
 * class-groups-newsletters-woocommerce-checkout.php
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
 * Renders and processes the newsletter subscription at checkout.
 */
class Groups_Newsletters_WooCommerce_Checkout {

	/**
	 * Kick in late with our actions, hook on the init action.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
	}

	/**
	 * Adds action hooks. 
	 */
	public static function wp_init() {
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'woocommerce_after_checkout_billing_form' ) );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'woocommerce_checkout_order_processed' ), 10, 3 );
	}

	/**
	 * Adds the newsletter subscription opt-in field.
	 *
	 * @param WC_Checkout $checkout
	 */
	public static function woocommerce_after_checkout_billing_form( $checkout ) {

		// don't show this if the user is already subscribed
		if ( $user_id = get_current_user_id() ) {
			$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );
			if ( $is_subscriber == 'yes' ) {
				return;
			}
		}

		$options = get_option( 'woocommerce-groups-newsletters', null );
		$checkout_opt_in_label = esc_html__( 'Subscribe to our newsletters', 'groups-newsletters' );
		if ( isset( $options['checkout-opt-in-label'] ) ) {
			$checkout_opt_in_label = wp_strip_all_tags( $options['checkout-opt-in-label'] );
		}

		echo '<div class="woocommerce-groups-newsletters checkout-opt-in">';
		if ( empty( $_POST ) ) {
			$checked = isset( $options['checkout-opt-in-default'] ) ? $options['checkout-opt-in-default'] : true;
		} else {
			$checked = !empty( $_POST['groups-newsletters-checkout-opt-in'] );
		}

		woocommerce_form_field(
			'groups-newsletters-checkout-opt-in',
			array(
				'type'  => 'checkbox',
				'label' => $checkout_opt_in_label,
				'class' => array( 'form-row-wide' )
			),
			$checked
		);
		echo '</div>';
	}

	/**
	 * Process the newsletter opt-in if chosen.
	 *
	 * @param int $order_id
	 * @param array $posted
	 * @param WC_Order $order
	 */
	public static function woocommerce_checkout_order_processed( $order_id, $posted, $order ) {

		if ( class_exists( 'Groups_Newsletters_Controller' ) && !empty( $_POST['groups-newsletters-checkout-opt-in'] ) ) {

			if ( $user_id = $order->get_user_id() ) {

				// customer has a user account

				if ( $user_id == get_current_user_id() ) { // should be the same as the current user, fishy if not
					$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );
					if ( $is_subscriber != 'yes' ) {
						if ( update_user_meta( $user_id, 'groups_newsletters_subscriber', 'yes' ) ) {
							$hash = md5( time() + rand( 0, time() ) );
							$datetime = date( 'Y-m-d H:i:s', time() );
							update_user_meta( $user_id, 'groups_newsletters_hash', $hash );
							update_user_meta( $user_id, 'groups_newsletters_datetime', $datetime );
							do_action( 'groups_newsletters_user_subscribed', $user_id );
						}
					}
				}

			} else {

				// guest checkout

				global $wpdb;

				$email = $order->get_billing_email();
				$subscriber_captured_before = false;

				$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );
				if ( $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $subscriber_table WHERE email = %s", $email ) ) ) {
					if ( isset( $results[0] ) ) {
						// already subscribed
						$subscriber_captured_before = true;
					}
				}
				$send_email = false;
				if ( !$subscriber_captured_before ) {
					// new subscriber
					$hash = md5( time() + rand( 0, time() ) );
					$datetime = date( 'Y-m-d H:i:s', time() );
					if ( $wpdb->query( $wpdb->prepare(
						"INSERT INTO $subscriber_table SET email = %s, status = 0, subscribed = %s, hash = %s",
						$email, $datetime, $hash
					) ) ) {
						$send_email = true;
					}
				} else {
					// resend if this subscriber hasn't activated the subscription
					if ( $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $subscriber_table WHERE email = %s AND status = 0", $email ) ) ) {
						if ( isset( $results[0] ) ) {
							$hash = $results[0]->hash;
							$send_email = true;
						}
					}
				}
				if ( $send_email ) {
					// send activation link email
					$tokens = array(
						'activation_url' => add_query_arg( array( 'groups_newsletters_activation' => urlencode( $hash ), 'email' => urlencode( $email ) ), get_bloginfo( 'url' ) ),
						'email'          => 'email',
						'site_url'       => get_bloginfo( 'url' ),
						'site_title'     => wp_specialchars_decode( get_bloginfo( 'blogname' ), ENT_QUOTES )
					);
					$tokens = apply_filters( 'groups_newsletters_activation_tokens', $tokens );
					$subject = apply_filters( 'groups_newsletters_activation_subject', __( 'Please confirm your subscription', 'groups-newsletters' ) );
					$message =
						'<p>' .
						esc_html__( 'We have received a request to subscribe this email address to our newsletters.', 'groups-newsletters' ) .
						'</p>' .
						'<p>' .
						esc_html__( 'If that is correct, please confirm your newsletter subscription by clicking the activation link below.', 'groups-newsletters' ) .
						'</p>' .
						'<p>' .
						'<a href="[activation_url]">[activation_url]</a>' .
						'</p>' .
						'<p>' .
						esc_html__( 'You can also copy it and paste it in the URL bar of your browser.', 'groups-newsletters' ) .
						'</p>' .
						'<p>' .
						esc_html__( 'In case you have not requested to be subscribed to our newsletters, please disregard this message and do not click or visit the activation link.', 'groups-newsletters' ) .
						'</p>' .
						'<p>' .
						esc_html__( 'Greetings,', 'groups-newsletters' ) .
						'<br/>' .
						'<a href="[site_url]">[site_title]</a>' .
						'</p>';
					$message = apply_filters( 'groups_newsletters_activation_message', $message );
					foreach( $tokens as $key => $value ) {
						$subject = str_replace( '[' . $key . ']', wp_strip_all_tags( $value ), $subject );
						$message = str_replace( '[' . $key . ']', $value, $message );
					}
					require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-mailer.php';
					Groups_Newsletters_Mailer::mail( $email, $subject, $message );
				}

			}

		}
	}
}
Groups_Newsletters_WooCommerce_Checkout::init();
