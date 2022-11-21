<?php

namespace WPFormsSaveResume;

use WPForms\Helpers\Transient;
use WPFormsSaveResume\Email\EmailNotification;

/**
 * The Frontend.
 *
 * @since 1.0.0
 */
class Frontend {

	/**
	 * Current form data.
	 *
	 * @var array
	 *
	 * @since 1.0.0
	 */
	protected $form_data;

	/**
	 * Entry object.
	 *
	 * @var object
	 *
	 * @since 1.0.0
	 */
	protected $entry;

	/**
	 * Form ID saved in transient.
	 *
	 * @since 1.2.0
	 *
	 * @var false|mixed
	 */
	protected $transient_data;

	/**
	 * Unique user ID.
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	private $user_uuid;

	/**
	 * Init.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();

		$this->user_uuid = wpforms_is_collecting_cookies_allowed() && ! empty( $_COOKIE['_wpfuuid'] ) ? sanitize_key( $_COOKIE['_wpfuuid'] ) : '';
	}

	/**
	 * Init method.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		// Ajax processing.
		add_action( 'wp_ajax_nopriv_wpforms_save_resume', [ $this, 'process_entry' ] );
		add_action( 'wp_ajax_wpforms_save_resume', [ $this, 'process_entry' ] );

		add_filter( 'wpforms_field_properties', [ $this, 'load_field_data' ], 10, 3 );

		// Front-end related hooks.
		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_css' ] );
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_js' ] );
		add_filter( 'wpforms_frontend_output_container_before', [ $this, 'display_save_resume_container_open' ], 10, 1 );
		add_filter( 'wpforms_frontend_output_container_after', [ $this, 'display_disclaimer' ], 10, 1 );
		add_filter( 'wpforms_frontend_output_container_after', [ $this, 'display_confirmation' ], 10, 1 );
		add_filter( 'wpforms_frontend_output_container_after', [ $this, 'display_save_resume_container_close' ], 999, 1 );
		add_filter( 'wpforms_frontend_container_class', [ $this, 'hide_form' ], 10, 2 );
		add_filter( 'wpforms_frontend_load', [ $this, 'display_form' ], 10, 2 );
		add_action( 'wpforms_frontend_output_form_before', [ $this, 'display_entry_expired_message' ], 10, 2 );

		add_action( 'wpforms_process_complete', [ $this, 'delete_entry' ], 10, 4 );

		// Notifications.
		add_action( 'wp', [ $this, 'send_email' ] );

		// Conversational Forms integration.
		add_action( 'wpforms_conversational_forms_enqueue_styles', [ $this, 'enqueue_conversational_forms_styles' ] );
		add_filter( 'wpforms_conversational_forms_start_button_disabled', [ $this, 'is_locked_filter' ], 10 );

		// Preventing multiple form submissions for one unique resume link from multiple opened tabs.
		add_filter( 'wpforms_process_initial_errors', [ $this, 'multiple_submission_check' ], 10, 2 );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms List of forms on the current page.
	 */
	public function enqueue_css( $forms ) {

		if ( ! empty( $forms ) && ! $this->has_forms_with_save_resume( $forms ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-save-resume',
			WPFORMS_SAVE_RESUME_URL . "assets/css/wpforms-save-resume{$min}.css",
			[],
			WPFORMS_SAVE_RESUME_VERSION
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms List of forms on the current page.
	 */
	public function enqueue_js( $forms ) {

		if ( ! $this->has_forms_with_save_resume( $forms ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-save-resume',
			WPFORMS_SAVE_RESUME_URL . "assets/js/wpforms-save-resume{$min}.js",
			[ 'wpforms', 'wpforms-validation' ],
			WPFORMS_SAVE_RESUME_VERSION,
			true
		);

		wp_localize_script(
			'wpforms-save-resume',
			'wpforms_save_resume',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Enqueue styles for Conversational Forms compatibility.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_conversational_forms_styles() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-save-resume-conversational',
			WPFORMS_SAVE_RESUME_URL . "assets/css/wpforms-save-resume-conversational-forms{$min}.css",
			[ 'wpforms-conversational-forms' ],
			WPFORMS_SAVE_RESUME_VERSION
		);
	}

	/**
	 * Whether any of the form has the Save and Resume functionality enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param array $forms List of forms on the current page.
	 */
	private function has_forms_with_save_resume( $forms ) {

		$is_enabled = false;

		foreach ( (array) $forms as $form ) {
			if ( wpforms_save_resume()->is_enabled( $form ) ) {
				$is_enabled = true;

				break;
			}
		}

		return $is_enabled;
	}


	/**
	 * Create a new entry.
	 *
	 * @since 1.0.0
	 */
	public function process_entry() {

		// Make sure we have required data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $_POST['wpforms'] ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$form_id = ! empty( $_POST['wpforms']['id'] ) ? absint( $_POST['wpforms']['id'] ) : 0;

		if ( $form_id === 0 ) {
			wp_send_json_error();
		}

		$entry = new Entry();

		// Prepare entry data.
		// Check if entry is spam.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput
		if ( is_string( $entry->prepare_data( $form_id, stripslashes_deep( $_POST['wpforms'] ) ) ) ) {
			wp_send_json_error();
		}

		$entry_id = Entry::get_existing_partial_entry_id( $form_id );
		$data     = $entry_id !== 0 ? $entry->update_entry( $entry_id ) : $entry->add_entry();

		/**
		 * Fire after partial entry was processed.
		 *
		 * @since 1.2.0
		 *
		 * @param int $form_id  Form ID.
		 * @param int $entry_id Entry ID.
		 */
		do_action( 'wpforms_save_resume_frontend_process_finished', $form_id, $entry_id );

		wp_send_json_success( $data );
	}

	/**
	 * Load entry to the form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Properties.
	 * @param array $field      Field.
	 * @param array $form_data  Form information.
	 *
	 * @return mixed
	 */
	public function load_field_data( $properties, $field, $form_data ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_GET['wpforms_resume_entry'] ) ) {
			return $properties;
		}

		$entry = wpforms()->get( 'entry' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification
		$hash     = ! empty( $_GET['wpforms_resume_entry'] ) ? $_GET['wpforms_resume_entry'] : '';
		$entry_id = Entry::get_entry_by_hash( $hash );

		if ( $entry_id === 0 ) {
			return $properties;
		}

		$entry_data = $entry->get( $entry_id );

		if ( empty( $entry_data ) ) {
			return $properties;
		}

		// In case multiple forms are displayed on the same page.
		if ( (int) $entry_data->form_id !== (int) $form_data['id'] ) {
			return $properties;
		}

		$entry_data = wpforms_decode( $entry_data->fields );
		$id         = (int) ! empty( $field['id'] ) ? $field['id'] : 0;

		if ( ! isset( $entry_data[ $id ] ) ) {
			return $properties;
		}

		$entry = new Entry();

		return $entry->get_entry( $properties, $field, $entry_data );
	}

	/**
	 * Templates for confirmation block.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form information.
	 */
	public function display_confirmation( $form_data ) {

		if ( empty( $form_data['settings']['save_resume_enable'] ) ) {
			return $form_data;
		}

		if (
			empty( $form_data['settings']['save_resume_enable_resume_link'] ) &&
			empty( $form_data['settings']['save_resume_enable_email_notification'] ) &&
			empty( $form_data['settings']['save_resume_enable_automatically_send_email'] )
		) {
			return $form_data;
		}

		$confirmation         = ! empty( $form_data['settings']['save_resume_confirmation_message'] ) ? $form_data['settings']['save_resume_confirmation_message'] : Settings::get_default_confirmation_message();
		$confirmation_callout = ! empty( $form_data['settings']['save_resume_confirmation_message_callout'] ) ? $form_data['settings']['save_resume_confirmation_message_callout'] : '';
		$action               = remove_query_arg( 'wpforms-save-resume' );
		?>

		<div class="wpforms-save-resume-confirmation" style="display: none">

			<?php if ( ! empty( $confirmation_callout ) ) : ?>
				<div class="wpforms-confirmation-container-full">
					<?php echo wp_kses_post( wpautop( $confirmation_callout ) ); ?>
				</div>
			<?php endif; ?>

			<div class='message'>
				<?php echo wp_kses_post( wpautop( $confirmation ) ); ?>
			</div>

			<div class="wpforms-save-resume-actions">
				<?php if ( ! empty( $form_data['settings']['save_resume_enable_resume_link'] ) ) : ?>
					<div class="wpforms-field">
					<label class="wpforms-field-label wpforms-save-resume-label">
						<?php esc_html_e( 'Copy Link', 'wpforms-save-resume' ); ?>
					</label>
					<div class="wpforms-save-resume-shortcode-container">
						<input type="text" class="wpforms-save-resume-shortcode" value="" disabled />
						<span class="wpforms-save-resume-shortcode-copy" title="<?php esc_attr_e( 'Copy resume link to clipboard', 'wpforms-save-resume' ); ?>">
							<span class="copy-icon"></span>
						</span>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( ! empty( $form_data['settings']['save_resume_enable_email_notification'] ) ) : ?>
					<form class="wpforms-validate wpforms-form wpforms-save-resume-email-notification" method="post" action="<?php echo esc_url( $action ); ?>" data-token="<?php echo esc_attr( wpforms()->get( 'token' )->get( true ) ); ?>">
						<div class="wpforms-field wpforms-field-email">
							<label class="wpforms-field-label wpforms-save-resume-label">
								<?php esc_html_e( 'Email', 'wpforms-save-resume' ); ?>
								<span class="wpforms-required-label">*</span>
							</label>
							<input type="email" name="wpforms[save_resume_email]" required>
						</div>
						<div class="wpforms-submit-container">
							<?php wp_nonce_field( 'wpforms_save_resume_process_entries' ); ?>
							<input type="hidden" name="wpforms[form_id]" value="<?php echo esc_attr( $form_data['id'] ); ?>">
							<input type="hidden" name="wpforms[entry_id]" class="wpforms-save-resume-entry-id" value="">
							<button type="submit" name="wpforms[save-resume]" class="wpforms-submit" value="wpforms-submit">
								<?php esc_html_e( 'Send Link', 'wpforms-save-resume' ); ?>
							</button>
						</div>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<?php
	}

	/**
	 * Templates for disclaimer block.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form information.
	 */
	public function display_disclaimer( $form_data ) {

		if ( empty( $form_data['settings']['save_resume_enable'] ) ) {
			return $form_data;
		}

		if ( empty( $form_data['settings']['save_resume_disclaimer_enable'] ) ) {
			return $form_data;
		}

		$message = ! empty( $form_data['settings']['save_resume_disclaimer_message'] ) ? $form_data['settings']['save_resume_disclaimer_message'] : Settings::get_default_disclaimer_message();
		?>

		<div class="wpforms-save-resume-disclaimer" style="display: none">
			<div class='message'>
				<?php echo wp_kses_post( wpautop( $message ) ); ?>
			</div>

			<div class="wpforms-form">
				<button type="submit" class="wpforms-save-resume-disclaimer-continue wpforms-submit">
					<?php esc_html_e( 'Continue', 'wpforms-save-resume' ); ?>
				</button>
				<a href="#" class="wpforms-save-resume-disclaimer-back">
					<span><?php esc_html_e( 'Go Back', 'wpforms-save-resume' ); ?></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Append wrapper to main form container.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form information.
	 */
	public function display_save_resume_container_open( $form_data ) {

		if ( empty( $form_data['settings']['save_resume_enable'] ) ) {
			return $form_data;
		}

		printf( '<div class="wpforms-container-save-resume">' );
	}

	/**
	 * Append wrapper closing tag to form container.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form information.
	 */
	public function display_save_resume_container_close( $form_data ) {

		if ( empty( $form_data['settings']['save_resume_enable'] ) ) {
			return $form_data;
		}

		printf( '</div>' );
	}

	/**
	 * Process email form submitting.
	 *
	 * @since 1.0.0
	 */
	public function send_email() {

		// Security check.
		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'wpforms_save_resume_process_entries' ) ) {
			return;
		}

		// Required data check.
		if ( ! isset( $_POST['submit'] ) && empty( $_POST['wpforms']['save_resume_email'] ) ) {
			return;
		}

		$address = sanitize_email( wp_unslash( $_POST['wpforms']['save_resume_email'] ) );

		if ( ! is_email( $address ) ) {
			return;
		}

		$entry_id = ! empty( $_POST['wpforms']['entry_id'] ) ? absint( $_POST['wpforms']['entry_id'] ) : 0;
		$form_id  = ! empty( $_POST['wpforms']['form_id'] ) ? absint( $_POST['wpforms']['form_id'] ) : 0;
		$token    = ! empty( $_POST['wpforms']['token'] ) ? sanitize_key( $_POST['wpforms']['token'] ) : '';

		// Token check before sending.
		$is_valid_token = wpforms()->get( 'token' )->verify( $token );

		// If spam - return early.
		if ( ! $is_valid_token ) {

			// Logs spam entry depending on log levels set.
			wpforms_log(
				'Spam Entry (Partial) ' . uniqid(),
				'Email notification has not been delivered.',
				[
					'type'    => [ 'spam' ],
					'parent'  => $entry_id,
					'form_id' => $form_id,
				]
			);

			return;
		}

		$this->email( $form_id, $entry_id, $address );

		Transient::set( 'wpforms_save_resume-' . $this->user_uuid, $form_id, MINUTE_IN_SECONDS );

		$return_back_url = ! empty( $_REQUEST['_wp_http_referer'] ) ? esc_url_raw( wp_unslash( $_REQUEST['_wp_http_referer'] ) ) : home_url();

		if ( ! empty( $return_back_url ) ) {
			wp_safe_redirect( $return_back_url );
			exit;
		}
	}

	/**
	 * Send email with partial link.
	 *
	 * @since 1.2.0
	 *
	 * @param int    $form_id  Form id.
	 * @param int    $entry_id Current entry id.
	 * @param string $address  Email address.
	 */
	private function email( $form_id, $entry_id, $address ) {

		$form_data = ! empty( $form_id ) ? wpforms()->get( 'form' )->get( $form_id, [ 'content_only' => true ] ) : [];
		$message   = ! empty( $form_data['settings']['save_resume_email_notification_message'] ) ? $form_data['settings']['save_resume_email_notification_message'] : Settings::get_default_email_notification();

		// phpcs:disable WPForms.PHP.ValidateHooks.InvalidHookName

		/** This filter is documented in wpforms/includes/functions.php */
		$message = apply_filters( 'wpforms_process_smart_tags', $message, $form_data, [], $entry_id );

		// phpcs:enable WPForms.PHP.ValidateHooks.InvalidHookName

		$email = [
			'address' => $address,
			'subject' => Settings::get_email_subject(),
			'message' => $message,
		];

		( new EmailNotification() )->send( $email );
	}

	/**
	 *
	 * Add .wpforms-save-resume-hide class if form should be hidden on the frontend.
	 *
	 * @since 1.2.0
	 *
	 * @param array $classes   Array of form classes.
	 * @param array $form_data Form information.
	 *
	 * @return array
	 */
	public function hide_form( $classes, $form_data ) {

		if ( $this->transient_data === $form_data['id'] ) {
			$classes[] = 'wpforms-save-resume-hide';
		}

		return $classes;
	}

	/**
	 * Append additional HTML to form if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $load_form Indicates whether a form should be loaded.
	 * @param array $form_data Form data.
	 *
	 * @return mixed
	 */
	public function display_form( $load_form, $form_data ) {

		$this->transient_data = Transient::get( 'wpforms_save_resume-' . $this->user_uuid );

		if ( $this->transient_data === $form_data['id'] ) {
			$message = ! empty( $form_data['settings']['save_resume_email_settings_message'] ) ? $form_data['settings']['save_resume_email_settings_message'] : Settings::get_default_email_sent_message();
			?>

			<div class="wpforms-save-resume-confirmation">
				<?php if ( $message ) : ?>
					<?php echo wp_kses_post( wpautop( $message ) ); ?>
				<?php endif; ?>
			</div>

			<?php
			Transient::delete( 'wpforms_save_resume-' . $this->user_uuid );
		}

		return $load_form;
	}

	/**
	 * Load text message if the resume link was expired.
	 *
	 * @since 1.0.0
	 * @deprecated 1.2.0
	 *
	 * @param bool  $load_form Indicates whether a form should be loaded.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function display_expired_message( $load_form, $form_data ) {

		_deprecated_function( __METHOD__, '1.2.0 of the WPForms Save and Resume addon', __CLASS__ . '::display_entry_expired_message()' );

		// The new method signature requires `$form` as a second argument, but it's not used.
		// We always pass all arguments available for a specific hook for consistency.
		// We don't have a form here, but empty array workaround is sufficient.
		$this->display_entry_expired_message( $form_data, [] );

		return $load_form;
	}

	/**
	 * Load text message if the resume link was expired.
	 *
	 * @since 1.2.0
	 *
	 * @param array $form_data Form data.
	 * @param array $form      Current form.
	 *
	 * @return void
	 */
	public function display_entry_expired_message( $form_data, $form ) {

		if ( ! wpforms_save_resume()->is_enabled( $form_data ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_GET['wpforms_resume_entry'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification
		$hash     = ! empty( $_GET['wpforms_resume_entry'] ) ? $_GET['wpforms_resume_entry'] : '';
		$entry_id = Entry::get_entry_by_hash( $hash );

		$entry_data = $entry_id !== 0 ? wpforms()->get( 'entry' )->get( $entry_id ) : [];

		if ( ! empty( $entry_data ) ) {
			return;
		}

		/**
		 * Change expired messages text.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message   Message.
		 * @param array  $form_data Form data.
		 */
		$message = apply_filters( 'wpforms_save_resume_frontend_expired_message', Settings::get_expired_message(), $form_data );

		printf(
			'<div class="wpforms-save-resume-expired-message %s">%s</div>',
			wpforms_setting( 'disable-css', '1' ) === '1' ? 'wpforms-save-resume-expired-message-full' : '',
			wp_kses_post( wpautop( $message ) )
		);
	}

	/**
	 * Filter locked state.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_locked_filter() {

		return ! empty( Transient::get( 'wpforms_save_resume-' . $this->user_uuid ) );
	}

	/**
	 * Delete partial entry which was successfully completed.
	 *
	 * @since 1.2.0
	 *
	 * @param array $fields    The fields that have been submitted.
	 * @param array $entry     The post data submitted by the form.
	 * @param array $form_data Form data.
	 * @param int   $entry_id  The entry ID.
	 */
	public function delete_entry( $fields, $entry, $form_data, $entry_id ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $form_data['settings']['save_resume_enable'] ) ) {
			return;
		}

		$partial_entry_id = Entry::get_existing_partial_entry_id( $form_data['id'] );

		if ( $partial_entry_id === 0 ) {
			return;
		}

		// We need to add $partial_entry_id meta to the new Entry for future checks
		// of multiple form submissions for one unique resume link from multiple opened tabs.
		Entry::set_partial_id_meta( $entry_id, $form_data['id'], $partial_entry_id );

		wpforms()->get( 'entry' )->delete( $partial_entry_id, [ 'cap' => false ] );
	}

	/**
	 * Check for multiple form submissions for one unique resume link from multiple opened tabs.
	 *
	 * @since 1.3.0
	 *
	 * @param array $errors    Form submit errors.
	 * @param array $form_data Form information.
	 *
	 * @return array
	 */
	public function multiple_submission_check( $errors, $form_data ) {

		if ( ! wpforms_save_resume()->is_enabled( $form_data ) ) {
			return $errors;
		}

		$hash               = Entry::get_resume_hash();
		$entry_meta_handler = wpforms()->get( 'entry_meta' );

		if ( ! $hash || ! $entry_meta_handler ) {
			return $errors;
		}

		$partial_entry_id = Entry::get_entry_by_hash( $hash );
		$form_id          = ! empty( $form_data['id'] ) ? $form_data['id'] : 0;
		$page_url         = wp_get_raw_referer();

		if ( ! $partial_entry_id || ! $form_id || ! $page_url ) {
			return $errors;
		}

		$meta = $entry_meta_handler->get_meta(
			[
				'data'    => $partial_entry_id,
				'form_id' => $form_id,
				'type'    => Entry::PARTIAL_ENTRY_META_ID_TYPE,
				'number'  => 1,
			]
		);

		if ( empty( $meta ) ) {
			return $errors;
		}

		$clear_url                         = remove_query_arg( 'wpforms_resume_entry', $page_url );
		$errors[ $form_id ]['save_resume'] = 'twice';
		$errors[ $form_id ]['header']      = sprintf(
			wp_kses( /* translators: %s - page URL without Save and Resume GET variables. */
				__( 'Unfortunately, the link you used to resume the form submission was already used. <a href="%s">Click here</a> to fill in the form again.', 'wpforms-save-resume' ),
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

		return $errors;
	}
}
