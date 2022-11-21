<?php
/**
 * class-groups-newsletters-newsletter.php
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
 * Newsletter abstraction and handler.
 */
class Groups_Newsletters_Newsletter {

	/**
	 * Term
	 *
	 * @var mixed
	 */
	public $term = null;

	/**
	 * Term id
	 *
	 * @var int
	 */
	private $term_id = null;

	/**
	 * Constructor, loads newsletter based on taxonomy term id.
	 *
	 * @param int $term_id
	 */
	public function __construct( $term_id ) {
		$this->term_id = $term_id;
		if ( $term = get_term( $term_id, 'newsletter' ) ) {
			$this->term = $term;
		}
	}

	/**
	 * Send the newsletter to the given email address.
	 *
	 * @param string $email
	 * @return boolean true if sent, false otherwise
	 */
	public function send( $email ) {
		$result = false;

		$user = null;
		$current_user_id = null;

		$hash = null;
		global $wpdb;
		$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );
		if ( ( $subscriber = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $subscriber_table WHERE email = %s", $email ) ) ) && isset( $subscriber[0] ) ) {
			$hash = $subscriber[0]->hash;
		} else {
			if ( $user = get_user_by( 'email', $email ) ) {
				$hash = get_user_meta( $user->ID, 'groups_newsletters_hash', true );
			}
		}

		// switch current user to recipient
		if ( $user instanceof WP_User ) {
			$current_user_id = get_current_user_id();
			wp_set_current_user( $user->ID );
		} else {
			wp_set_current_user( 0 );
		}

		// We need to get the term after switching the user so that
		// term access restrictions can be applied or the term be obtained
		// for the recipient:
		$term = $this->term;
		$this->term = get_term( $this->term_id, 'newsletter' );

		if ( ( $this->term !== null ) && !is_wp_error( $this->term ) ) {

			$subject = wp_strip_all_tags( $this->term->name );
			$header  = apply_filters( 'groups_newsletters_newsletter_email_header', '' ); // convenience e.g. to get a customized or common header ...
			$footer  = apply_filters( 'groups_newsletters_newsletter_email_footer', '' ); // ... and a footer
			$content = apply_filters( 'groups_newsletters_newsletter_email_content', $this->get_content() ); // allow to filter content
			$message = $header . $content . $footer;

			if ( $hash !== null ) {
				$cancel_url = add_query_arg( array( 'groups_newsletters_cancellation' => urlencode( $hash ), 'email' => urlencode( $email ) ), get_bloginfo( 'url' ) );
				$cancel_link = sprintf( '<a href="%s">%s</a>', esc_url( $cancel_url ), esc_html__( 'Unsubscribe', 'groups-newsletters' ) );
				$message = str_replace( '[unsubscribe_link]', $cancel_link, $message );
			} else {
				// @since 2.1.0 unsubscribed email => don't render (for example in testing)
				$message = str_replace( '[unsubscribe_link]', esc_html__( 'Not available for this recipient.', 'groups-newsletters' ), $message );
			}

			require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-mailer.php';
			Groups_Newsletters_Mailer::mail( $email, $subject, $message );
			$result = true;
		}
		// Now reinstate the instance's original term.
		$this->term = $term;

		// reinstate current user
		if ( $current_user_id !== null ) {
			wp_set_current_user( $current_user_id );
		}

		return $result;
	}

	/**
	 * Returns the rendered newsletter based on email template.
	 *
	 * @return string newsletter HTML
	 */
	public function get_content() {
		$content = '';
		if ( $this->term !== null ) {
			ob_start();
			$locations = array(
				'groups-newsletters/email/' . $this->term->slug . '-' . 'taxonomy-newsletter.php',
				'groups-newsletters/email/taxonomy-newsletter.php'
			);
			$template = locate_template( $locations );
			if ( empty( $template ) ) {
				$template = GROUPS_NEWSLETTERS_DIR . '/templates/email/taxonomy-newsletter.php';
			}
			if ( file_exists( $template ) ) {
				$newsletter = $this;
				require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-story.php';
				include $template;
			}
			$content = ob_get_clean();
		}
		return $content;
	}

	/**
	 * Returns the post ids of the stories associated to the newsletter.
	 *
	 * @return array of int
	 */
	public function get_post_ids() {
		$post_ids = array();
		if ( $this->term !== null ) {
			$post_ids = get_posts(
				array(
					'fields'      => 'ids',
					'numberposts' => -1,
					'post_type'   => 'story',
					'tax_query'  => array(
						array(
							'taxonomy' => 'newsletter',
							'field'    => 'id',
							'terms'    => $this->term->term_id
						)
					),
					'orderby'  => apply_filters(
						'groups_newsletters_newsletter_stories_orderby',
						array(
							'menu_order' => 'ASC',
							'date'       => 'DESC'
						)
					)
				)
			);
		}
		return $post_ids;
	}
}
