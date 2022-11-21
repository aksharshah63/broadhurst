<?php

namespace WPFormsSurveys\Fields\NetPromoterScore;

/**
 * Editing Net Promoter Score field entries.
 *
 * @since 1.6.3
 */
class EntriesEdit extends \WPForms\Pro\Forms\Fields\Base\EntriesEdit {

	/**
	 * Constructor.
	 *
	 * @since 1.6.3
	 */
	public function __construct() {

		parent::__construct( 'net_promoter_score' );
	}

	/**
	 * Init.
	 *
	 * @since 1.6.3
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.6.3
	 */
	private function hooks() {

		// Make this field editable.
		add_filter( 'wpforms_pro_admin_entries_edit_field_editable', array( $this, 'editable' ), 10, 2 );

		// Pass Entries edit field object.
		add_filter(
			"wpforms_pro_admin_entries_edit_field_object_{$this->field_object->type}",
			function () {
				return $this;
			}
		);
	}

	/**
	 * Enqueues for the Edit Entry page.
	 *
	 * @since 1.6.3
	 */
	public function enqueues() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'wpforms-surveys-polls',
			wpforms_surveys_polls()->url . "assets/css/wpforms-surveys-polls{$min}.css",
			array(),
			WPFORMS_SURVEYS_POLLS_VERSION
		);
	}

	/**
	 * Display the field on the Edit Entry page.
	 *
	 * @since 1.6.3
	 *
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data and settings.
	 * @param array $form_data   Form data and settings.
	 */
	public function field_display( $entry_field, $field, $form_data ) {

		if ( ! empty( $entry_field['value'] ) ) {
			$field['properties']['inputs'][ $entry_field['value'] ]['attr']['checked'] = true;
		}

		$this->field_object->field_display( $field, null, $form_data );
	}

	/**
	 * Make this field editable.
	 *
	 * @since 1.6.3
	 *
	 * @param bool   $editable Flag editable.
	 * @param string $type     Field type slug.
	 *
	 * @return bool True for this field.
	 */
	public function editable( $editable, $type ) {

		return ! empty( $type ) && $type === $this->field_object->type ? true : $editable;
	}
}
