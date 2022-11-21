<?php

namespace WPFormsSaveResume\Email;

use WPForms\Emails\Mailer;
use WPFormsSaveResume\Email\Templates\SaveResume;

/**
 * The Notification class.
 *
 * @since 1.0.0
 */
class EmailNotification {

	/**
	 * Send Email.
	 *
	 * @since 1.0.0
	 *
	 * @param array $email Email data to send.
	 *
	 * @return bool|void
	 */
	public function send( $email ) {

		$is_html = 'default' === wpforms_setting( 'email-template', 'default' );

		if ( ! $is_html ) {
			return ( new Mailer() )
				->message( $email['message'] )
				->subject( $email['subject'] )
				->to_email( $email['address'] )
				->send();
		}

		$args = [
			'body' => [
				'message' => str_replace( "\r\n", '<br/>', $email['message'] ),
			],
		];

		$template = ( new SaveResume() )->set_args( $args );

		/**
		 * This filter allows overwriting email template.
		 *
		 * @since 1.0.0
		 *
		 * @param \WPFormsSaveResume\Email\Templates\SaveResume $template Template object.
		 * @param array                                         $email    {
		 *     Email data.
		 *
		 *     @type string   $address Admin email.
		 *     @type string   $subject Email subject.
		 *     @type string   $message Email body.
		 * }
		 */
		$template = apply_filters( 'wpforms_save_resume_email_emailnotification_send_template', $template, $email );

		$content = $template->get();

		if ( ! $content ) {
			return;
		}

		return ( new Mailer() )
			->template( $template )
			->subject( $email['subject'] )
			->to_email( $email['address'] )
			->send();
	}
}
