<?php
/**
 * class-groups-newsletters-shortcodes.php
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
 * Newsletter shortcodes.
 */
class Groups_Newsletters_Shortcodes {

	/**
	 * Adds shortcodes.
	 */
	public static function init() {

		add_shortcode( 'groups_newsletters_subscribe', array( __CLASS__, 'groups_newsletters_subscribe' ) );
		add_shortcode( 'groups_newsletters_user_subscribe', array( __CLASS__, 'groups_newsletters_user_subscribe' ) );

		// see below add_shortcode( 'groups_newsletters_unsubscribe', array( __CLASS__, 'groups_newsletters_unsubscribe' ) );

		add_shortcode( 'groups_newsletters_activation', array( __CLASS__, 'groups_newsletters_activation' ) );

		add_shortcode( 'groups_newsletters', array( __CLASS__, 'groups_newsletters' ) );
		add_shortcode( 'groups_newsletters_search', array( __CLASS__, 'groups_newsletters_search' ) );
		add_shortcode( 'groups_newsletters_tags', array( __CLASS__, 'groups_newsletters_tags' ) );
		add_shortcode( 'groups_newsletters_stories', array( __CLASS__, 'groups_newsletters_stories' ) );

		add_shortcode( 'groups_newsletters_user', array( __CLASS__, 'groups_newsletters_user' ) );

		// add_filter( 'get_search_form', array( __CLASS__, 'get_search_form' ) );

		// currently we don't load any specific CSS or Javascript
		// add_action( 'the_posts', array( __CLASS__, 'the_posts' ) );
	}

	/**
	 * Enqueue style based on shortcode presence. This does not modify the $posts.
	 *
	 * Currently not used.
	 *
	 * @param array $posts posts to show
	 * @return array of $posts
	 */
	public static function the_posts( $posts ) {
		if ( Groups_Newsletters_Options::get_option( 'shortcode-css', true ) ) {
			$load = false;
			if ( !wp_script_is( 'groups-newsletters' ) ) {
				foreach( $posts as $post ) {
					// quickly look for a possible Groups Newsletters shortcode
					if ( strpos( $post->post_content, "[groups_newsletters" ) !== false ) {
						$load = true;
						break;
					}
				}
			}
			if ( $load ) {
				wp_enqueue_style( 'groups-newsletters', GROUPS_NEWSLETTERS_PLUGIN_URL . '/css/groups-newsletters.css', array(), GROUPS_NEWSLETTERS_PLUGIN_VERSION );
			}
		}
		return $posts;
	}


	/**
	 * Subscription box.
	 *
	 * recaptcha_widget : uses 'groups_newsletters_neutral' to render a neutral recaptcha like http://www.google.com/recaptcha/demo/custom
	 *
	 * @param array $atts
	 * @param string $content not used
	 */
	public static function groups_newsletters_subscribe( $atts, $content = null ) {

		$output = "";
		$options = shortcode_atts(
			array(
				'description'               => __( 'Subscribe to our newsletters', 'groups-newsletters' ),
				'description_class'         => 'description',
				'description_style'         => 'padding-bottom: 1em;',
				'field_label'               => __( 'Email', 'groups-newsletters' ),
				'field_class'               => 'field',
				'field_style'               => 'padding-bottom: 1em;',
				'after_field_label'         => ' ',
				'email_placeholder'         => __( 'Your email address', 'groups-newsletters' ),

				'captcha_class'             => 'captcha',
				'captcha_style'             => 'padding-bottom: 1em;',
				'captcha_filter'            => '',
				'captcha_validate_filter'   => '',
				'captcha_description'       => __( 'Are you human?', 'groups-newsletters' ),
				'captcha_description_class' => 'captcha-description',
				'captcha_description_style' => 'padding-bottom: 1em;',

				'recaptcha'                 => 'no',
				'recaptcha_public_key'      => '',
				'recaptcha_private_key'     => '',
				'recaptcha_options'         => 'theme:"white"',
				'recaptcha_widget'          => '',

				'recaptcha_error_message'   => __( 'Please solve the captcha to proof that you are human.', 'groups-newsletters' ),
				'captcha_error_message'     => __( 'Please solve the captcha to proof that you are human.', 'groups-newsletters' ),

				'errors_style'              => 'border: 1px solid #c00; border-radius: 4px; background-color: #fee; padding: 1em;',

				'confirm_message'           => __( 'Thank you. Please check your email to confirm your subscription.', 'groups-newsletters' ),
				'confirm_style'             => 'border: 1px solid #0c0; border-radius: 4px; background-color: #efe; padding: 1em;',

				'resend_inactive'           => 'yes',

				'hide_on_activation'        => 'yes',
				'hide_on_cancellation'      => 'yes',

				'user_subscribe_form'       => 'yes',
				'force_user_login'          => 'yes'
			),
			$atts
		);

		extract( $options );

		if ( $user_subscribe_form == 'yes' || $user_subscribe_form == 'true' || $user_subscribe_form === true ) {
			if ( is_user_logged_in() ) {
				return self::groups_newsletters_user_subscribe( $options, $content );
			}
		}

		if ( $hide_on_activation == 'yes' || $hide_on_activation == 'true' || $hide_on_activation === true ) {
			if ( isset( $_REQUEST['groups_newsletters_activation'] ) && isset( $_REQUEST['email'] ) ) {
				return '';
			}
		}
		if ( $hide_on_cancellation == 'yes' || $hide_on_cancellation == 'true' || $hide_on_cancellation === true ) {
			if ( isset( $_REQUEST['groups_newsletters_cancellation'] ) && isset( $_REQUEST['email'] ) ) {
				return '';
			}
		}

		$subscriber_captured = false;
		$subscriber_captured_before = false;

		$recaptcha_error = null;
		$errors = array();

		$email = !empty( $_POST['email'] ) ? wp_strip_all_tags( $_POST['email'] ) : '';
		if ( isset( $_POST['subscribe'] ) && isset( $_POST['groups_newsletters_nonce'] ) && wp_verify_nonce( $_POST['groups_newsletters_nonce'], 'subscribe' ) ) {

			$valid = true;

			// valid email?
			if ( empty( $email ) || !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
				$errors[] = __( 'You must provide a valid email address.', 'groups-newsletters' );
				$valid = false;
			}

			if ( $force_user_login == 'yes' || $force_user_login == 'true' || $force_user_login === true ) {
				if ( get_user_by( 'email', $email ) ) {
					$errors[] = __( 'Please log in to subscribe.', 'groups-newsletters' );
					$valid = false;
				}
			}

			// captcha
			if ( function_exists( $captcha_filter ) ) {
				$captcha_valid = apply_filters( $captcha_filter, $valid, $_POST, $errors );
				if ( !$captcha_valid ) {
					$errors[] = $captcha_error_message;
				}
				$valid = $valid && $captcha_valid;
			}

			// recaptcha
			if ( $recaptcha == 'yes' || $recaptcha == 'true' || $recaptcha === true ) {
				if ( isset( $_POST['recaptcha_challenge_field'] ) && isset( $_POST['recaptcha_response_field'] ) ) {
					if ( !function_exists( 'recaptcha_check_answer' ) ) {
						require_once GROUPS_NEWSLETTERS_INCLUDES . '/recaptcha/recaptchalib.php';
					}
					$response = recaptcha_check_answer( $recaptcha_private_key, $_SERVER['REMOTE_ADDR'], $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field'] );
					if ( !$response->is_valid ) {
						$recaptcha_error = $response->error;
						$errors[] = $recaptcha_error_message;
						$valid = false;
					}
				}
			}

			// test that the email is not already registered and that it has been activated
			if ( $valid ) {
				global $wpdb;
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
						$subscriber_captured = true;
						$send_email = true;
					} else {
						$errors[] = __( 'Sorry but we are having some trouble subscribing you right now. Please try again in a few minutes.', GROUPS_NEWSLETTER_PLUGIN_DOMAIN );
					}
				} else if ( $resend_inactive == 'yes' || $resend_inactive == 'true' || $resend_inactive === true ) {
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
					$subject = apply_filters( 'groups_newsletters_activation_subject', esc_html__( 'Please confirm your subscription', 'groups-newsletters' ) );
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
					// When two or more forms are on the same page, duplicates would be sent out - avoid that:
					global $groups_newsletters_subscribe;
					if ( !isset( $groups_newsletters_subscribe ) ) {
						$groups_newsletters_subscribe = $email;
						require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-mailer.php';
						Groups_Newsletters_Mailer::mail( $email, $subject, $message );
					}
				}
			}
		}

		if ( !empty( $errors ) ) {
			$output .= sprintf( '<div class="errors" style="%s">', $errors_style );
			foreach( $errors as $error ) {
				$output .= '<div class="error">';
				$output .= $error;
				$output .= '</div>';
			}
			$output .= '</div>';
		}

		if ( $subscriber_captured || $subscriber_captured_before ) {
			$output .= sprintf( '<div class="confirm" style="%s">', $confirm_style );
			$output .= $confirm_message;
			$output .= '</div>';
		} else {

		$captcha = '';

		if ( function_exists( $captcha_filter ) ) {
			$captcha .= apply_filters( $captcha_filter, '' );
		}

		if ( $recaptcha == 'yes' || $recaptcha == 'true' || $recaptcha === true ) {
			if ( !function_exists( 'recaptcha_get_html' ) ) {
				require_once GROUPS_NEWSLETTERS_INCLUDES . '/recaptcha/recaptchalib.php';
			}
			if ( empty( $recaptcha_widget ) ) {
				if ( !empty( $recaptcha_options ) ) {
					$captcha = sprintf( '<script type= "text/javascript">var RecaptchaOptions = {%s};</script>', $recaptcha_options );
				}
				$captcha .= recaptcha_get_html( $recaptcha_public_key, $recaptcha_error );
			} else {
				if ( $recaptcha_widget == 'groups_newsletters_neutral' ) {
					$captcha = sprintf( '<script type= "text/javascript">var RecaptchaOptions = {%s};</script>', self::get_neutral_recaptcha_options() );
					$recaptcha_widget = self::get_neutral_recaptcha();
				} else {
					if ( !empty( $recaptcha_options ) ) {
						$captcha = sprintf( '<script type= "text/javascript">var RecaptchaOptions = {%s};</script>', $recaptcha_options );
					}
				}
				$recaptcha_widget = str_replace('[public_key]', $recaptcha_public_key, $recaptcha_widget );
				$recaptcha_widget = str_replace('[private_key]', $recaptcha_private_key, $recaptcha_widget );
				$captcha .= $recaptcha_widget;
			}
		}
			$output .=
				'<form method="post" action="">' .
				'<div>';

			$output .=
				sprintf( '<div class="%s" style="%s">', esc_attr( $description_class ), esc_attr( $description_style ) ) .
				$description .
				'</div>';

			$output .=
				sprintf( '<div class="%s" style="%s">', esc_attr( $field_class ), esc_attr( $field_style ) ) .
				'<label>' .
				$field_label .
				esc_html( $after_field_label ) .
				sprintf( '<input type="text" name="email" value="%s" placeholder="%s" />', esc_attr( $email ), esc_attr( $email_placeholder ) ) .
				'</label>' .
				'</div>';

			if ( !empty( $captcha ) ) {
				$output .=
					sprintf( '<div class="%s" style="%s">', $captcha_description_class, $captcha_description_style ) .
					$captcha_description .
					'</div>';

				$output .=
					sprintf( '<div class="%s" style="%s">', $captcha_class, $captcha_style ) .
					$captcha .
					'</div>';
			}

			$output .=
				sprintf( '<input type="submit" name="subscribe" value="%s" />', esc_attr__( 'Subscribe', 'groups-newsletters' ) );

			$output .=
				wp_nonce_field( 'subscribe', 'groups_newsletters_nonce', true, false );

			$output .=
				'</div>' .
				'</form>';
		}

		return $output;
	}

	public static function groups_newsletters_user_subscribe( $atts, $content = null ) {

		$output = "";

		$options = shortcode_atts(
			array(
				'description'               => __( 'Subscribe to our newsletters', 'groups-newsletters' ),
				'description_class'         => 'description',
				'description_style'         => 'padding-bottom: 1em;',

				'user_confirm_message'      => __( 'Thank you. Your subscription is confirmed.', 'groups-newsletters' ),
				'user_confirm_style'        => 'border: 1px solid #0c0; border-radius: 4px; background-color: #efe; padding: 1em;',

				'user_unsubscribed_message' => __( 'Thank you. Your subscription has been removed.', 'groups-newsletters' ),
				'user_unsubscribed_style'   => 'border: 1px solid #0c0; border-radius: 4px; background-color: #efe; padding: 1em;',

				'log_in_message'            =>
					sprintf(
						__(
							'Please %slog in%s to subscribe.',
							'groups-newsletters'
						),
						sprintf( '<a href="%s">', esc_url( wp_login_url( add_query_arg( array() ) ) ) ),
						'</a>'
					),
				'log_in_style'              => 'border: 1px solid #cc0; border-radius: 4px; background-color: #ffe; padding: 1em;',
				'show_log_in_message'       => 'yes',

				'show_unsubscribe'          => 'yes',
				'subscriber_message'        => __( 'You are subscribed to our newsletters.', 'groups-newsletters' ),
				'subscriber_message_style'   => '',

			),
			$atts
		);

		extract( $options );

		$subscribed = false;
		$unsubscribed = false;

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );
			if ( isset( $_POST['groups_newsletters_nonce'] ) && wp_verify_nonce( $_POST['groups_newsletters_nonce'], 'subscribe' ) ) {
				if ( isset( $_POST['subscribe'] ) ) {
					if ( $is_subscriber != 'yes' ) {
						if ( update_user_meta( $user_id, 'groups_newsletters_subscriber', 'yes' ) ) {
							$hash = md5( time() + rand( 0, time() ) );
							$datetime = date( 'Y-m-d H:i:s', time() );
							update_user_meta( $user_id, 'groups_newsletters_hash', $hash );
							update_user_meta( $user_id, 'groups_newsletters_datetime', $datetime );
							$subscribed = true;
							do_action( 'groups_newsletters_user_subscribed', $user_id );
						}
					}
				} else if ( isset( $_POST['unsubscribe'] ) ) {
					if ( $is_subscriber == 'yes' ) {
						if ( delete_user_meta( $user_id, 'groups_newsletters_subscriber' ) ) {
							delete_user_meta( $user_id, 'groups_newsletters_hash' );
							delete_user_meta( $user_id, 'groups_newsletters_datetime' );
							$unsubscribed = true;
							do_action( 'groups_newsletters_user_unsubscribed', $user_id );
						}
					}
				}
			}

			// must update status as it could have changed
			$is_subscriber = get_user_meta( $user_id, 'groups_newsletters_subscriber', true );

			if ( $subscribed || $unsubscribed ) {
				if ( $subscribed ) {
					$output .= sprintf( '<div class="confirm" style="%s">', $user_confirm_style );
					$output .= $user_confirm_message;
					$output .= '</div>';
				} else {
					$output .= sprintf( '<div class="unsubscribed" style="%s">', $user_unsubscribed_style );
					$output .= $user_unsubscribed_message;
					$output .= '</div>';
				}
			}

			if ( $is_subscriber != 'yes' ) {
				$output .=
					'<form method="post" action="">' .
					'<div>';

				$output .=
					sprintf( '<div class="%s" style="%s">', esc_attr( $description_class ), esc_attr( $description_style ) ) .
					$description .
					'</div>';

				$output .=
					sprintf( '<input type="submit" name="subscribe" value="%s" />', esc_attr__( 'Subscribe', 'groups-newsletters' ) );

				$output .=
					wp_nonce_field( 'subscribe', 'groups_newsletters_nonce', true, false );

				$output .=
					'</div>' .
				'</form>';
			} else {
				$output .=
				sprintf( '<div style="%s">', esc_attr( $subscriber_message_style ) ) .
				$subscriber_message .
				'</div>';
			}

			if ( $show_unsubscribe == 'yes' || $show_unsubscribe == 'true' || $show_unsubscribe === true ) {
				if ( $is_subscriber == 'yes' ) {
					$output .=
						'<form method="post" action="">' .
						'<div>';

					$output .=
						sprintf( '<div class="%s" style="%s">', esc_attr( $description_class ), esc_attr( $description_style ) ) .
						$description .
						'</div>';

					$output .=
						sprintf( '<input type="submit" name="unsubscribe" value="%s" />', esc_attr__( 'Unsubscribe', 'groups-newsletters' ) );

					$output .=
						wp_nonce_field( 'subscribe', 'groups_newsletters_nonce', true, false );

					$output .=
						'</div>' .
						'</form>';
				}
			}
		} else {
			if ( $show_log_in_message == 'yes' || $show_log_in_message == 'true' || $show_log_in_message === true ) {
				$output .= sprintf( '<div class="log_in" style="%s">', $log_in_style );
				$output .= $log_in_message;
				$output .= '</div>';
			}
		}
		return $output;
	}

	/**
	 * Unsubscribe box.
	 *
	 * @todo to consider later, input email address to unsubscribe, double opt-out
	 *
	 * @param array $atts
	 * @param string $content not used
	 */
	public static function groups_newsletters_unsubscribe( $atts, $content = null ) {
		$output = "";
		$options = shortcode_atts(
			array(
			),
			$atts
		);
		extract( $options );

		// ...

		return $output;
	}

	/**
	 * Activation/cancellation messages.
	 *
	 * @param array $atts
	 * @param string $content
	 * @return string
	 */
	public static function groups_newsletters_activation( $atts, $content = null ) {
		$output = "";
		$options = shortcode_atts(
			array(
				'activation_message' => __( 'Thank you, your newsletter subscription has been activated.', 'groups-newsletters' ),
				'cancellation_message' => __( 'Thank you, your newsletter subscription has been cancelled.', 'groups-newsletters' )
			),
			$atts
		);
		extract( $options );
		if ( isset( $_REQUEST['groups_newsletters_activation'] ) && isset( $_REQUEST['email'] ) ) {
			$output .= '<p class="groups-newsletters-activation">' . $activation_message .'</p>';
		} else if ( isset( $_REQUEST['groups_newsletters_cancellation'] ) && isset( $_REQUEST['email'] ) ) {
			$output .= '<p class="groups-newsletters-cancellation">' . $cancellation_message . '</p>';
		}
		return $output;
	}

	/**
	 * Renders the list of newsletters.
	 * @param array $atts
	 * @param string $content not used
	 */
	public static function groups_newsletters( $atts, $content = null ) {
		$output = "";
		$options = shortcode_atts(
			array(
				'newsletter'  => '',
				'newsletters' => '',
				'show_count' => 1,
				'orderby' => 'name',
				'order' => 'ASC'
			),
			$atts
		);
		extract( $options );
		$show_count = (string) $show_count;
		switch( $show_count ) {
			case 'yes' :
			case 'true' :
			case '1' :
				$show_count = 1;
				break;
			case 'no' :
			case 'false' :
			case '0' :
				$show_count = 0;
				break;
		}

		// @todo to consider later, restrictions on newsletters viewing
// 		$user_id = get_current_user_id();
// 		$term_ids = array();
// 		$exclude_term_ids = array();
// 		$terms = get_terms(
// 			'newsletter',
// 			array(
// 				'hide_empty' => 0
// 			)
// 		);
// 		foreach( $terms as $term ) {
// 			if ( user is allowed to view the newsletter: $user_id, $term->term_id ) {
// 				$term_ids[] = $term->term_id;
// 			} else {
// 				$exclude_term_ids[] = $term->term_id;
// 			}
// 		}

		$output .= wp_list_categories( array(
			'taxonomy'   => 'newsletter',
			'orderby'    => $orderby,
			'order'      => $order,
			'show_count' => $show_count,
			'hide_empty' => 0,
			'title_li'   => '',
			'show_option_none' => esc_html__( 'There are no newsletters.', 'groups-newsletters' ),
			'echo'       => 0,
// 			'include'    => $term_ids,
// 			'exclude'    => $exclude_term_ids
		) );
		return $output;
	}

	public static function groups_newsletters_search( $atts, $content = null ) {
		$form =
		'<form role="search" method="get" id="searchform" action="' . esc_url( home_url( '/' ) ) . '" >
		<div><label class="screen-reader-text" for="s">' . esc_html__( 'Search for:', 'groups-newsletters' ) . '</label>
		<input type="text" placeholder="' . esc_html__( 'Search for &hellip;', 'groups-newsletters' ) . '" value="' . esc_attr( get_search_query() ) . '" name="s" id="s" />
		<input type="hidden" value="story" name="post_type" id="post_type" />
		<input type="submit" id="searchsubmit" value="' . esc_attr__( 'Search' ) . '" />
		</div>
		</form>';
		return $form;
	}

// 	public static function get_search_form( $form ) {
// 		global $wp_query;
// 		if ( $wp_query->is_search ) {
// 			if ( isset( $wp_query->query['post_type'] ) && ( $wp_query->query['post_type'] == 'story' ) ) {
// 				if ( !strpos( $form, '<input type="hidden" value="story" name="post_type" id="post_type" />' ) ) {
// 					$form = str_replace(
// 						'</form>',
// 						'<div><input type="hidden" value="story" name="post_type" id="post_type" /></div></form>',
// 						$form
// 					);
// 					$form = str_replace(
// 						'<form',
// 						sprintf( '<p>%s</p><form', __( 'Story search', 'groups-newsletters' ) ),
// 						$form
// 					);
// 				}
// 			}
// 		}
// 		return $form;
// 	}

	/**
	 * Renders a story tag cloud.
	 * @param array $atts
	 * @param string $content
	 * @return string HTML
	 */
	public static function groups_newsletters_tags( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'smallest'  => 8,
				'largest'   => 22,
				'unit'      => 'pt',
				'number'    => 45,
				'format'    => 'flat',
				'separator' => "\n",
				'orderby'   => 'name',
				'order'     => 'ASC',
				'exclude'   => '',
				'include'   => '',
				'link'      => 'view'
			),
			$atts
		);

		$atts['taxonomy'] = 'story_tag';
		$atts['echo']     = false;

		return wp_tag_cloud( $atts );
	}

	/**
	 * Renders a list of stories.
	 *
	 * @param array $atts
	 * @param string $content not used
	 * @return string
	 */
	public static function groups_newsletters_stories( $atts, $content = null ) {

		$args = shortcode_atts(
			array(
				'number'             => 10,
				'numberposts'        => 10,
				'show_author'        => false,
				'show_date'          => true,
				'show_comment_count' => true,
				'orderby'            => 'post_date',
				'order'              => 'DESC',
				'newsletter_id'      => null,
				'term_id'            => null,
				'all'                => false,
				'post_status'        => 'publish',
			),
			$atts
		);

		$args['post_type'] = 'story';

		if ( isset( $args['number'] ) ) {
			$args['numberposts'] = $args['number'];
			unset( $args['number'] );
		}

		$show_author = isset( $args['show_author'] ) && $args['show_author'];
		unset( $args['show_author'] );

		$show_date = isset( $args['show_date'] ) && $args['show_date'];
		unset( $args['show_date'] );

		$show_comment_count = isset( $args['show_comment_count'] ) && $args['show_comment_count'];
		unset( $args['show_comment_count'] );

		// dumb but post_title won't work
		if ( isset( $args['orderby'] ) && ( $args['orderby'] == 'post_title' ) ) {
			$args['orderby'] = 'title';
		}

		if ( !empty( $args['term_id'] ) ) {
			$args['newsletter_id'] = $args['term_id'];
		}
		if ( !empty( $args['newsletter_id'] ) ) {
			if ( ( $args['newsletter_id'] == '[current]' ) || $args['newsletter_id'] == '{current}' ) {
				$newsletter_id = null;
				global $wp_query;
				if ( $o = $wp_query->get_queried_object() ) {
					if ( isset( $o->taxonomy ) && ( $o->taxonomy == 'newsletter' ) ) {
						$newsletter_id = $o->term_id;
					}
				}
			} else {
				$newsletter_id = $args['newsletter_id'];
			}
			if ( $newsletter_id ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'newsletter',
						'field' => 'id',
						'terms' => $newsletter_id,
						'include_children' => false
					)
				);
			}
		}
		unset( $args['newsletter_id'] );
		unset( $args['term_id'] );

		$output = '';

		$stories = get_posts( $args );
		if ( count( $stories ) > 0 ) {
			$output .= '<ul>';
			foreach( $stories as $story ) {
				$author = '';
				if ( $show_author ) {
					$author = ' ' . sprintf( '<span class="author">by %s</span>', esc_html( get_the_author_meta( 'display_name', $story->post_author ) ) );
				}
				$date = '';
				if ( $show_date ) {
					$date = sprintf(
						', <span class="date">%s</span>',
						esc_html( mysql2date( get_option('date_format'), $story->post_date ) )
					);
				}
				$comment_count = '';
				if ( $show_comment_count ) {
					$comment_count = ', ' . '<span class="comment_count">' . esc_html( sprintf( _n( '1 voice', '%d voices', $story->comment_count ), $story->comment_count ) ) . '</span>';
				}
				if ( 'publish' == $story->post_status ) {
					$output .= sprintf( '<li><a href="%s">%s</a>%s%s%s</li>', esc_url( get_permalink( $story->ID ) ), wp_strip_all_tags( $story->post_title ), $author, $date, $comment_count );
				}
			}
			$output .= '</ul>';
		} else {
			$output .= esc_html__( 'There are no stories.', 'groups-newsletters' );
		}
		return $output;
	}

	/**
	 * Options for neutral recaptcha.
	 * @return string
	 */
	public static function get_neutral_recaptcha_options() {
		return apply_filters(
			'groups_newsletters_get_neutral_recaptcha_options',
			'theme:"custom",custom_theme_widget:"groups_newsletters_recaptcha_widget"'
		);
	}

	/**
	 * Markup for neutral recaptcha.
	 * @return string
	 */
	public static function get_neutral_recaptcha() {
		return apply_filters(
			'groups_newsletters_get_neutral_recaptcha',
			'<div id="groups_newsletters_recaptcha_widget" style="display:none">
<div id="recaptcha_image"></div>
<div class="recaptcha_only_if_incorrect_sol" style="color:red">Incorrect please try again</div>
<span class="recaptcha_only_if_image">Enter the words above:</span>
<span class="recaptcha_only_if_audio">Enter the numbers you hear:</span>
<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" />
<div><a href="javascript:Recaptcha.reload()">Get another CAPTCHA</a></div>
<div class="recaptcha_only_if_image"><a href="javascript:Recaptcha.switch_type(\'audio\')">Get an audio CAPTCHA</a></div>
<div class="recaptcha_only_if_audio"><a href="javascript:Recaptcha.switch_type(\'image\')">Get an image CAPTCHA</a></div>
<div><a href="javascript:Recaptcha.showhelp()">Help</a></div>
</div>
<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k=[public_key]">
</script>
<noscript>
<iframe src="http://www.google.com/recaptcha/api/noscript?k=[public_key]" height="300" width="500" frameborder="0"></iframe><br>
<textarea name="recaptcha_challenge_field" rows="3" cols="40">
</textarea>
<input type="hidden" name="recaptcha_response_field" value="manual_challenge">
</noscript>'
		);
	}

	/**
	 * Renders user info, supports anything that the WP_User object can provide, this includes several properties and usermeta.
	 *
	 * Accepted shortcode attributes:
	 *
	 * - key : indicates the desired property of the user
	 * - default : a default string to render if the key is empty or does not exist
	 * - empty : 'yes' or 'no' (default)
	 *
	 * - All HTML is stripped from the output and the output is escaped using esc_html(). This also applies to the default.
	 * - Values that are not strings will produce the default instead.
	 * - Non-existent keys will produce the default.
	 * - If there is no corresponding user (such in the case of plain subscribers), the default will be produced.
	 * - Empty values will produce the default unless the empty="yes" flag is passed to the shortcode.
	 *
	 * Example:
	 * 
	 * Dear [groups_newsletters_user key="first_name" default="Subscriber"], ...
	 *
	 * Where the first name is empty or does not exist, this will produce "Dear Subscriber, ..."
	 *
	 * Among the accepted keys are:
	 *
	 * - ID
	 * - nickname
	 * - description
	 * - user_description
	 * - first_name
	 * - user_firstname
	 * - last_name
	 * - user_lastname
	 * - user_login
	 * - user_nicename
	 * - user_email
	 * - user_url
	 * - user_registered
	 * - user_status
	 * - user_level
	 * - display_name
	 * - locale
	 *
	 * These are explicitly skipped for security or because they do not provide sensible values for display:
	 *
	 * - user_pass
	 * - user_activation_key
	 * - rich_editing
	 * - syntax_highlighting
	 * - spam
	 * - deleted
	 *
	 * @param array $atts shortcode attributes
	 * @param string $content not used
	 */
	public static function groups_newsletters_user( $atts, $content = null ) {

		$args = shortcode_atts(
			array(
				'key'     => '',
				'default' => '',
				'empty'   => 'no'
			),
			$atts
		);

		$empty = $args['empty'] === true || strtolower( $args['empty'] ) === 'true' || strtolower( $args['empty'] ) === 'yes';

		$output = esc_html( wp_strip_all_tags( $args['default'] ) );

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$user = new WP_User( $user_id );
			$key = sanitize_key( $args['key'] );
			switch ( $key ) {
				// avoid the deprecated 'id' key
				case 'id' :
					$key = 'ID';
					break;
				// skip these keys
				case 'user_pass' :
				case 'user_activation_key' :
				case 'rich_editing' :
				case 'syntax_highlighting' :
				case 'spam' :
				case 'deleted' :
					$key = '';
					break;
			}

			if ( $key !== '' && $user->has_prop( $key ) ) {
				$value = $user->get( $key );
				if ( is_string( $value ) ) {
					$value = trim( $value );
					if ( $empty || strlen( $value ) > 0 ) {
						$output = esc_html( wp_strip_all_tags( $value ) );
					}
				}
			}
		}
		return $output;
	}
}
Groups_Newsletters_Shortcodes::init();
