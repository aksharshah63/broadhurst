<?php

namespace WPFormsSaveResume;

/**
 * The ResumeLink Smart Tag class.
 *
 * @since 1.0.0
 */
class ResumeLink {

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_filter( 'wpforms_smart_tags', [ $this, 'register_tag' ] );
		add_filter( 'wpforms_process_smart_tags', [ $this, 'resume_link' ], 10, 4 );
	}

	/**
	 * Register the new {resume_link} smart tag.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tags List of tags.
	 *
	 * @return array $tags List of tags.
	 */
	public function register_tag( $tags ) {

		$tags['resume_link'] = esc_html__( 'Resume Link', 'wpforms-save-resume' );

		return $tags;
	}

	/**
	 * Check for {resume_link} Smart Tag inside email messages and replace it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message   Message.
	 * @param array  $form_data Form data.
	 * @param array  $fields    List of fields.
	 * @param string $entry_id  Entry ID.
	 *
	 * @return string
	 */
	public function resume_link( $message, $form_data, $fields = [], $entry_id = '' ) {

		// Check to see if SmartTag is in the email notification message.
		if ( strpos( $message, '{resume_link}' ) === false ) {
			return $message;
		}

		$hash_url = Entry::get_hash_url_by_entry( $entry_id );

		$is_html = wpforms_setting( 'email-template', 'default' ) === 'default';
		$link    = esc_url_raw( $hash_url );

		if ( $is_html ) {
			$link = sprintf(
				'<div style="text-align: center"><a href="%1$s" style="text-decoration: none; background: #e27730; padding: 6px 10px; border-radius: 3px; text-align: center; color: beige;">%2$s</a></div>',
				esc_url( $hash_url ),
				esc_html__( 'Resume Form Submission', 'wpforms-save-resume' )
			);
		}

		if ( empty( $hash_url ) ) {
			$link = '';
		}

		return str_replace( '{resume_link}', $link, $message );
	}
}
