<?php

namespace WPFormsSaveResume\Admin;

use WPFormsSaveResume\Settings;

/**
 * Class Builder.
 *
 * @since 1.0.0
 */
class Builder {

	/**
	 * Initialize.
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

		add_action( 'wpforms_builder_enqueues', [ $this, 'enqueue_assets' ] );
		add_action( 'wpforms_form_settings_panel_content', [ $this, 'panel_content' ], 30, 2 );
		add_filter( 'wpforms_builder_settings_sections', [ $this, 'builder_settings_register' ], 30, 2 );
		add_filter( 'wpforms_builder_strings', [ $this, 'builder_strings' ] );
	}

	/**
	 * Enqueue a JavaScript file and inline CSS styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_assets() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-save-resume-admin-builder',
			WPFORMS_SAVE_RESUME_URL . "assets/js/admin-builder-save-resume{$min}.js",
			[ 'wpforms-builder' ],
			WPFORMS_SAVE_RESUME_VERSION,
			true
		);

		wp_enqueue_style(
			'wpforms-save-resume-admin-builder',
			WPFORMS_SAVE_RESUME_URL . "assets/css/admin-builder-save-resume{$min}.css",
			[],
			WPFORMS_SAVE_RESUME_VERSION
		);

		wp_localize_script(
			'wpforms-save-resume-admin-builder',
			'wpforms_save_resume',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Save and Resume form builder register settings area.
	 *
	 * @since 1.0.0
	 *
	 * @param array $sections Settings area sections.
	 *
	 * @return array
	 */
	public function builder_settings_register( $sections ) {

		$sections['save_resume'] = esc_html__( 'Save and Resume', 'wpforms-save-resume' );

		return $sections;
	}

	/**
	 * Add a content for `Save and Resume` panel.
	 *
	 * @since 1.0.0
	 *
	 * @param \WPForms_Builder_Panel_Settings $instance Settings panel instance.
	 */
	public function panel_content( $instance ) {

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-save_resume">';

		echo '<div class="wpforms-panel-content-section-title">';
		esc_html_e( 'Save and Resume', 'wpforms-save-resume' );
		echo '</div><!-- .wpforms-panel-content-section-title -->';

		if ( ! wpforms_save_resume()->is_enabled( $instance->form_data ) ) {

			echo '<div class="wpforms-save-resume-description">';
			printf(
				wp_kses( /* translators: %s - Link to the WPForms.com doc article. */
					__( 'Allow users to save their progress and resume submitting this form later. A link will be sent to the user which will allow them to simply return to this form with their progress intact. Sensitive information like credit cards and file uploads will not be saved and the user will have to re-enter them. <a href="%s" target="_blank" rel="noopener noreferrer">Read more about Save and Resume.</a>', 'wpforms-save-resume' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				esc_url( wpforms_utm_link( 'https://wpforms.com/docs/how-to-install-and-use-the-save-and-resume-addon-with-wpforms/', 'Builder Settings', 'Save and Resume Documentation' ) )
			);
			echo '</div>';
		}

		wpforms_panel_field(
			'toggle',
			'settings',
			'save_resume_enable',
			$instance->form_data,
			__( 'Enable Save and Resume', 'wpforms-save-resume' )
		);

		echo '<div class="wpforms-save-resume-sub-panel">';

		wpforms_panel_field(
			'text',
			'settings',
			'save_resume_link_text',
			$instance->form_data,
			__( 'Link Text', 'wpforms-save-resume' ),
			[
				'tooltip' => __( 'This text is displayed next to the submit button, and allows users to save their progress.', 'wpforms-save-resume' ),
				'default' => Settings::get_default_link_text(),
			]
		);

		echo '<div class="wpforms-save-resume-divider"></div>';

		// Disclaimer block.
		$disclaimer = wpforms_panel_field(
			'toggle',
			'settings',
			'save_resume_disclaimer_enable',
			$instance->form_data,
			__( 'Enable Disclaimer Page', 'wpforms-save-resume' ),
			[],
			false
		);

		wpforms_panel_fields_group(
			$disclaimer,
			[
				'description' => __( 'This page is displayed before the user actually commits to saving their progress.', 'wpforms-save-resume' ),
				'title'       => __( 'Disclaimer Page', 'wpforms-save-resume' ),
			]
		);

		wpforms_panel_field(
			'tinymce',
			'settings',
			'save_resume_disclaimer_message',
			$instance->form_data,
			__( 'Display Message', 'wpforms-save-resume' ),
			[
				'tinymce'       => [
					'editor_height' => 175,
				],
				'class'         => 'wpforms-save-resume-required',
				'default'       => Settings::get_default_disclaimer_message(),
				'input_class'   => 'wpforms-required',
				'after_tooltip' => '&nbsp;<span class="required">*</span>',
			]
		);

		echo '<div class="wpforms-save-resume-divider"></div>';

		// Confirmation block.
		$confirmation = wpforms_panel_field(
			'tinymce',
			'settings',
			'save_resume_confirmation_message',
			$instance->form_data,
			__( 'Display Message', 'wpforms-save-resume' ),
			[
				'default'       => Settings::get_default_confirmation_message(),
				'tinymce'       => [
					'editor_height' => 175,
				],
				'class'         => 'wpforms-save-resume-required',
				'input_class'   => 'wpforms-required',
				'after_tooltip' => '&nbsp;<span class="required">*</span>',
			],
			false
		);

		wpforms_panel_fields_group(
			$confirmation,
			[
				'description' => __( 'This page is displayed once the user clicks the Save and Resume link and provides instructions for resuming.', 'wpforms-save-resume' ),
				'title'       => __( 'Confirmation Page', 'wpforms-save-resume' ),
			]
		);

		// Resume Link.
		wpforms_panel_field(
			'toggle',
			'settings',
			'save_resume_enable_resume_link',
			$instance->form_data,
			__( 'Enable Resume Link', 'wpforms-save-resume' ),
			[
				'tooltip' => __( 'Allow the user to copy a link, which they can paste in their address bar to resume later.', 'wpforms-save-resume' ),
				'default' => ! isset( $instance->form_data['settings']['save_resume_enable'] ) ? '1' : 0,
			]
		);

		// Enable Notification.
		wpforms_panel_field(
			'toggle',
			'settings',
			'save_resume_enable_email_notification',
			$instance->form_data,
			__( 'Enable Email Notification', 'wpforms-save-resume' ),
			[
				'tooltip' => __( 'Allow the user to receive an email with a link to resume later.', 'wpforms-save-resume' ),
				'default' => ! isset( $instance->form_data['settings']['save_resume_enable'] ) ? '1' : 0,
			]
		);

		echo '<div class="wpforms-save-resume-email-settings">';
		echo '<div class="wpforms-save-resume-divider"></div>';

		// Email Notification.
		$notification_message = wpforms_panel_field(
			'textarea',
			'settings',
			'save_resume_email_notification_message',
			$instance->form_data,
			__( 'Email Notification', 'wpforms-save-resume' ),
			[
				'default'       => Settings::get_default_email_notification(),
				'smarttags'     => [
					'type' => 'other',
				],
				'class'         => 'wpforms-save-resume-email-message wpforms-save-resume-required',
				'rows'          => 6,
				'input_class'   => 'wpforms-required',
				'after_tooltip' => '&nbsp;<span class="required">*</span>',
			],
			false
		);

		wpforms_panel_fields_group(
			$notification_message,
			[
				'description' => __( 'When the user chooses to email a link, they are shown the following success message and receive an email with the link.', 'wpforms-save-resume' ),
				'title'       => __( 'Email Settings', 'wpforms-save-resume' ),
			]
		);

		// Email Settings.
		wpforms_panel_field(
			'tinymce',
			'settings',
			'save_resume_email_settings_message',
			$instance->form_data,
			__( 'Display Message', 'wpforms-save-resume' ),
			[
				'tinymce'       => [
					'editor_height' => 175,
				],
				'class'         => 'wpforms-save-resume-required',
				'default'       => Settings::get_default_email_sent_message(),
				'input_class'   => 'wpforms-required',
				'after_tooltip' => '&nbsp;<span class="required">*</span>',
			]
		);

		/**
		 * Display content after plugin's panel in the Form Builder.
		 *
		 * @since 1.0.0
		 *
		 * @param array $form_data Form data.
		 */
		do_action( 'wpforms_save_resume_admin_builder_panel_content_after', $instance->form_data );

		echo '</div>';
		echo '</div>';
		echo '</div><!-- .wpforms-panel-content-section-save_resume -->';
	}

	/**
	 * Add localized strings from addon to builder's strings array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $strings Form builder JS strings.
	 *
	 * @return array
	 */
	public function builder_strings( $strings ) {

		$strings['save_resume_email_link_settings_required'] = esc_html__( 'One option must be enabled so that your visitors can resume their entry.', 'wpforms-save-resume' );
		$strings['save_resume_required_text_fields']         = esc_html__( 'You started to configure the Save and Resume addon. Please fill out all required fields or disable the addon.', 'wpforms-save-resume' );
		$strings['save_resume_disabled_entry_storage']       = esc_html__( 'Save and Resume can\'t be activated because entry storage is disabled. Please visit the General settings and enable entry storage for this form.', 'wpforms-save-resume' );

		return $strings;
	}
}
