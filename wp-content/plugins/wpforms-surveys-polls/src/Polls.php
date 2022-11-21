<?php

namespace WPFormsSurveys;

use WPFormsSurveys\Reporting\Fields;

/**
 * Poll functionality.
 *
 * @since 1.0.0
 */
class Polls {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->init();
	}

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Register CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueues' ) );

		// Enable CSS for AJAX forms with poll results.
		add_action( 'wpforms_frontend_css', array( $this, 'enqueues_for_ajax_submissions' ) );

		// Poll results shortcode.
		add_shortcode( 'wpforms_poll', array( $this, 'shortcode' ) );

		// Enable WordPress shortcodes in the confirmation message setting.
		add_filter( 'wpforms_frontend_confirmation_message', array( $this, 'confirmation_message' ), 20, 2 );
	}

	/**
	 * Register Poll CSS.
	 *
	 * @since 1.0.0
	 */
	public function enqueues() {

		$min = wpforms_get_min_suffix();

		// CSS.
		wp_register_style(
			'wpforms-polls',
			wpforms_surveys_polls()->url . "assets/css/wpforms-polls{$min}.css",
			array(),
			WPFORMS_SURVEYS_POLLS_VERSION
		);
	}

	/**
	 * Enqueue frontend field CSS.
	 *
	 * @since 1.6.3
	 *
	 * @param array $forms Forms on the current page.
	 */
	public function enqueues_for_ajax_submissions( $forms ) {

		foreach ( $forms as $form ) {
			if ( ! empty( $form['settings']['poll_enable'] ) && ! empty( $form['settings']['ajax_submit'] ) ) {
				wp_enqueue_style( 'wpforms-polls' );

				return;
			}
		}
	}

	/**
	 * Polls shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public function shortcode( $atts ) {

		$atts = shortcode_atts(
			array(
				'label'    => false,
				'form_id'  => false,
				'field_id' => false,
				'counts'   => false,
			),
			$atts,
			'wpforms_poll'
		);

		if ( empty( $atts['form_id'] ) || empty( $atts['field_id'] ) ) {
			return '';
		}

		ob_start();

		$this->display_results( $atts );

		return ob_get_clean();
	}

	/**
	 * Display poll results for a specific field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Results arguments.
	 */
	public function display_results( $args ) {

		$form_id  = absint( $args['form_id'] );
		$field_id = absint( $args['field_id'] );

		// Get form data so we can access the field settings.
		$form_data = wpforms()->form->get(
			$form_id,
			array(
				'content_only' => true,
			)
		);

		// Confirm it's a valid form and field.
		if ( empty( $form_data ) || empty( $form_data['fields'][ $field_id ]['type'] ) ) {
			return;
		}

		// Supported poll fields types are select, radio, and checkbox.
		if ( ! in_array( $form_data['fields'][ $field_id ]['type'], array( 'select', 'radio', 'checkbox' ), true ) ) {
			return;
		}

		// Fetch the results.
		$results = Fields::get_survey_field_data( $form_data['fields'][ $field_id ], $form_id );

		// Field choices.
		$choices = $form_data['fields'][ $field_id ]['choices'];

		// Bail if there are no answers or choices, which should never happen.
		if ( empty( $results['answers'] ) || empty( $choices ) ) {
			return;
		}

		// Build the results output display.
		echo '<div class="wpforms-poll-results" id="wpforms-poll-results-' . absint( $form_id ) . '">';

		if ( $args['label'] && 'false' !== $args['label'] ) {
			echo '<div class="wpforms-poll-label">' . esc_html( sanitize_text_field( $results['question'] ) ) . '</div>';
		}

		$is_dynamic_choices = ! empty( $form_data['fields'][ $field_id ]['dynamic_choices'] );
		$choices            = $is_dynamic_choices ? $this->get_dynamic_choices( $form_data['fields'][ $field_id ] ) : $choices;

		foreach ( $choices as $choice_key => $choice ) {

			// Find the choice in the answers.
			$count_and_percent = $this->get_answer_count_and_percent_of_choice( $results['answers'], $choice_key, $choice, $is_dynamic_choices );

			echo '
			<div class="wpforms-poll-answer" id="wpforms-poll-answer-' . absint( $choice_key ) . '">
				<div class="wpforms-poll-answer-label-wrap">
					<div class="wpforms-poll-answer-percent">
						<span>' . esc_html( $count_and_percent['percent'] ) . '%</span>';

			if ( $args['counts'] && 'false' !== $args['counts'] ) {
				echo ' <div class="wpforms-poll-answer-count">';
				printf(
					/* translators: %s - votes count. */
					esc_html( _n( '(%s vote)', '(%s votes)', $count_and_percent['count'], 'wpforms-surveys-polls' ) ),
					esc_html( number_format_i18n( $count_and_percent['count'] ) )
				);
				echo '</div>';
			}

			echo '
					</div>
					<div class="wpforms-poll-answer-label">
						' . esc_html( sanitize_text_field( $choice['label'] ) ) . '
					</div>
				</div>
				<div class="wpforms-poll-answer-bar-wrap">
					<div class="wpforms-poll-answer-bar" style="width:' . esc_attr( $count_and_percent['percent'] ) . '%;"></div>
				</div>
			</div>';
		}

		if ( $args['counts'] ) {
			echo '<div class="wpforms-poll-total">';
			printf(
				/* translators: %s - total votes. */
				esc_html__( 'Total Votes: %s', 'wpforms-surveys-polls' ),
				esc_html( number_format_i18n( absint( $results['answered'] ) ) )
			);
			echo '</div>';
		}

		echo '</div>';

		// Load our poll styling.
		wp_enqueue_style( 'wpforms-polls' );
	}

	/**
	 * Get the result choices for the dynamic field.
	 *
	 * @since 1.9.0
	 *
	 * @param array $field_data The dynamic field.
	 *
	 * @return array
	 */
	private function get_dynamic_choices( $field_data ) {

		$options = [];

		switch ( $field_data['dynamic_choices'] ) {
			case 'post_type':
				$options = get_posts(
					[
						'nopaging'       => true,
						'no_found_rows'  => true,
						'post_type'      => $field_data['dynamic_post_type'],
						'posts_per_page' => -1,
						'post_status'    => 'publish',
					]
				);
				break;

			case 'taxonomy':
				$options = get_terms(
					[
						'fields'     => 'id=>name',
						'hide_empty' => false,
						'taxonomy'   => $field_data['dynamic_taxonomy'],
					]
				);
				break;
		}

		if ( ! is_array( $options ) ) {
			return [];
		}

		$choices = [];

		foreach ( $options as $option ) {

			if ( $option instanceof \WP_Post ) {
				$label = $option->post_title;
			} else {
				$label = (string) $option;
			}

			$choices[] = [
				'label' => $label,
				'value' => '',
				'image' => '',
			];
		}

		return $choices;
	}

	/**
	 * Get the answer count and percentage of a choice in a poll field.
	 *
	 * @since 1.9.0
	 *
	 * @param array $answers            Array containing the answers of a poll field.
	 * @param int   $choice_key         The choice key.
	 * @param array $choice             Array containing info about the choice.
	 * @param bool  $is_dynamic_choices Whether in the context of the dynamic field or not.
	 *
	 * @return int[]
	 */
	private function get_answer_count_and_percent_of_choice( $answers, $choice_key, $choice, $is_dynamic_choices ) {

		foreach ( $answers as $answer ) {

			$get_the_count_and_percent = false;

			/*
			 * For the dynamic choices, we are using the Label (post title, category name)
			 * and NOT post ID or term / taxonomy ID to track for the submitted answers.
			 */
			if (
				( $is_dynamic_choices && $choice['label'] === $answer['value'] ) ||
				( isset( $answer['choice_id'] ) && $answer['choice_id'] === $choice_key )
			) {
				$get_the_count_and_percent = true;
			}

			if ( $get_the_count_and_percent ) {
				return [
					'count'   => absint( $answer['count'] ),
					'percent' => absint( $answer['percent'] ),
				];
			}
		}

		return [
			'count'   => 0,
			'percent' => 0,
		];
	}

	/**
	 * First, enable shortcodes in the confirmation message form setting if it contains
	 * a Poll shortcode.
	 *
	 * Second, if Poll results are enabled in the form settings, detect and
	 * automatically append the results to the confirmation message.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message   Confirmation message.
	 * @param array  $form_data Form data and settings.
	 *
	 * @return string
	 */
	public function confirmation_message( $message, $form_data ) {

		// Check if automatic poll reporting is enabled.
		if ( ! empty( $form_data['settings']['poll_enable'] ) ) {

			$args = apply_filters(
				'wpforms_poll_results_confirmation_defaults',
				array(
					'label'  => true,
					'counts' => true,
				)
			);

			foreach ( $form_data['fields'] as $field ) {
				if ( in_array( $field['type'], array( 'select', 'radio', 'checkbox' ), true ) ) {

					ob_start();

					$this->display_results(
						array(
							'label'    => $args['label'],
							'form_id'  => $form_data['id'],
							'field_id' => $field['id'],
							'counts'   => $args['counts'],
						)
					);

					$message .= ob_get_clean();
				}
			}
		} elseif ( has_shortcode( $message, 'wpforms_poll' ) ) {

			// Check for shortcode in confirmation message.
			return do_shortcode( $message );
		}

		return $message;
	}
}
