<?php

namespace WPFormsSaveResume;

/**
 * Various default messages that can be configured from the Form Builder.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * The default text for a link.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_default_link_text() {

		return esc_html__( 'Save and Resume Later', 'wpforms-save-resume' );
	}

	/**
	 * Disclaimer default message.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_default_disclaimer_message() {

		return esc_html__( 'Heads up! Saving your progress now will store a copy of your entry on this server and the site owner may have access to it. For security reasons, sensitive information such as credit cards and mailing addresses, along with file uploads will have to be re-entered when you resume.', 'wpforms-save-resume' );
	}

	/**
	 * Confirmation default message.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_default_confirmation_message() {

		$default_message  = __( 'Your form entry has been saved and a unique link has been created which you can access to resume this form.', 'wpforms-save-resume' ) . "\r\n\r\n";
		$default_message .= __( 'Enter your email address to receive the link via email. Alternatively, you can copy and save the link below.', 'wpforms-save-resume' ) . "\r\n\r\n";
		$default_message .= __( 'Please note, this link should not be shared and will expire in 30 days, afterwards your form entry will be deleted.', 'wpforms-save-resume' );

		return $default_message;
	}

	/**
	 * Successfully sent email message.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_default_email_sent_message() {

		$default_message  = __( 'A link to resume this form has been sent to the email address provided.', 'wpforms-save-resume' ) . "\r\n\r\n";
		$default_message .= __( 'Please remember, the link should not be shared and will expire in 30 days.', 'wpforms-save-resume' );

		return $default_message;
	}

	/**
	 * Email notification default message.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_default_email_notification() {

		$default_message  = sprintf( /* translators: %s - form name. */
			esc_html__( 'Thank you for saving %s. Click the link below to resume the form from any device.', 'wpforms-save-resume' ) . "\r\n\r\n",
			'{form_name}'
		);
		$default_message .= '{resume_link}' . "\r\n\r\n";
		$default_message .= esc_html__( 'Remember, the link should not be shared and will expire in 30 days.', 'wpforms-save-resume' );

		return $default_message;
	}

	/**
	 * Expired message text.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_expired_message() {

		$clear_url = remove_query_arg( 'wpforms_resume_entry' );

		return sprintf(
			wp_kses( /* translators: %s - page URL without Save and Resume GET variables. */
				__( 'Unfortunately, the link you used to resume the form submission has expired. <a href="%s">Click here</a> to fill in the form again.', 'wpforms-save-resume' ),
				[
					'a' => [
						'href'   => [],
						'rel'    => [],
						'target' => [],
					],
				]
			),
			$clear_url
		);
	}

	/**
	 * Email subject.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_email_subject() {

		$subject = esc_html__( 'Your Form Submission Resume link' , 'wpforms-save-resume' );

		/**
		 * Email notification subject filter.
		 *
		 * @since 1.0.0
		 *
		 * @param string $subject The email subject.
		 */
		return apply_filters( 'wpforms_save_resume_settings_get_email_subject', $subject );
	}
}
