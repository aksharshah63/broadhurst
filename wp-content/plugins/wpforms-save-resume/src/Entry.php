<?php

namespace WPFormsSaveResume;

use WPForms\Helpers\Crypto;

/**
 * The Class for communicating with DB.
 *
 * @since 1.0.0
 */
class Entry {
	/**
	 * Partial entry meta id type.
	 *
	 * @since 1.3.0
	 */
	const PARTIAL_ENTRY_META_ID_TYPE = 'partial_entry_meta_id';

	/**
	 * Fields.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Form ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int Form ID.
	 */
	private $form_id;

	/**
	 * Form data.
	 *
	 * @since 1.0.0
	 *
	 * @var array Form data.
	 */
	private $form_data;

	/**
	 * Fields not allowed to be saved.
	 *
	 * @since 1.0.0
	 *
	 * @var string[] Fields.
	 */
	private $not_allowed_fields = [
		'file-upload',
		'signature',
		'password',
		'authorize_net',
		'stripe-credit-card',
		'square',
		'payment-total',
		'captcha',
	];

	/**
	 * Format and sanitize raw data.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $form_id Form ID.
	 * @param object $entry   Entry.
	 */
	public function prepare_data( $form_id, $entry ) {

		wpforms()->process->fields = [];
		$this->form_id             = $form_id;

		// If the honeypot was triggers we assume this is a spammer.
		if ( isset( $entry['hp'] ) && ! empty( $entry['hp'] ) ) {
			wp_send_json_error();
		}

		// Get the form settings for this form.
		$this->form_data = wpforms()->get( 'form' )->get( $this->form_id, [ 'content_only' => true ] );

		// Format fields.
		foreach ( $this->form_data['fields'] as $field ) {

			$field_submit = isset( $entry['fields'][ $field['id'] ] ) ? $entry['fields'][ $field['id'] ] : '';

			// Exclude fields which is not supported.
			if ( in_array( $field['type'], $this->not_allowed_fields, true ) ) {
				continue;
			}

			do_action( "wpforms_process_format_{$field['type']}", $field['id'], $field_submit, $this->form_data );
		}

		/**
		 * Filter post-process fields before saving.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $fields    Fields data array.
		 * @param object $entry     Entry.
		 * @param array  $form_data Form data.
		 */
		$this->fields = apply_filters( 'wpforms_process_filter_save_resume', wpforms()->process->fields, $entry, $this->form_data );

		// Validate anti-spam token.
		$antispam = wpforms()->get( 'token' )->validate( $this->form_data, $this->fields, (array) $entry );

		if ( $antispam && is_string( $antispam ) && ! wpforms_is_amp() ) {

			// Logs spam entry depending on log levels set.
			wpforms_log(
				'Spam Entry (Partial) ' . uniqid(),
				[ $antispam, $entry ],
				[
					'type'    => [ 'spam' ],
					'form_id' => $this->form_data['id'],
				]
			);

			return $antispam;
		}

		/**
		 * Triggers when Partial fields are ready to be saved.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $fields    Fields data array.
		 * @param object $entry     Entry.
		 * @param array  $form_data Form data.
		 */
		do_action( 'wpforms_process_save_resume', $this->fields, $entry, $this->form_data );
	}

	/**
	 * Add new partial entry.
	 *
	 * @since 1.0.0
	 */
	public function add_entry() {

		$user_id = get_current_user_id();
		$user_ip = wpforms_get_ip();

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		$user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 256 ) : '';
		$user_uuid  = wpforms_is_collecting_cookies_allowed() && ! empty( $_COOKIE['_wpfuuid'] ) ? sanitize_key( $_COOKIE['_wpfuuid'] ) : '';
		$date       = gmdate( 'Y-m-d H:i:s' );

		// If GDPR enhancements are enabled and user details are disabled
		// globally or in the form settings, discard the IP and UA.
		if ( ! wpforms_is_collecting_ip_allowed( $this->form_data ) ) {
			$user_agent = '';
			$user_ip    = '';
		}

		// Prepare the args to be saved.
		$data = [
			'form_id'    => absint( $this->form_id ),
			'user_id'    => absint( $user_id ),
			'status'     => 'partial',
			'fields'     => wp_json_encode( $this->fields ),
			'ip_address' => sanitize_text_field( $user_ip ),
			'user_agent' => sanitize_text_field( $user_agent ),
			'user_uuid'  => sanitize_text_field( $user_uuid ),
			'date'       => $date,
		];

		// Save.
		$entry_id = wpforms()->get( 'entry' )->add( $data );

		// Save entry fields.
		wpforms()->get( 'entry_fields' )->save( $this->fields, $this->form_data, $entry_id );

		$hash = $this->get_hash( $entry_id );

		$verification_link = self::generate_hash_url( $hash, $this->form_id );

		wpforms()->get( 'entry_meta' )->add(
			[
				'entry_id' => $entry_id,
				'form_id'  => absint( $this->form_id ),
				'user_id'  => absint( $user_id ),
				'type'     => 'partial',
				'data'     => $verification_link,
			],
			'entry_meta'
		);

		return [
			'hash'     => $verification_link,
			'entry_id' => $entry_id,
		];
	}

	/**
	 * Update entry.
	 *
	 * @since 1.0.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return array
	 */
	public function update_entry( $entry_id ) {

		// Prepare the args to be updated.
		$data = [
			'viewed'        => 0,
			'fields'        => wp_json_encode( $this->fields ),
			'date_modified' => gmdate( 'Y-m-d H:i:s' ),
		];

		$entry      = wpforms()->get( 'entry' );
		$entry_meta = wpforms()->get( 'entry_meta' );
		$hash       = $this->get_hash( $entry_id );

		$verification_link = self::generate_hash_url( $hash, $this->form_id );

		$entry->update( $entry_id, $data, '', '', [ 'cap' => false ] );

		$entry_meta->update(
			$entry_id,
			[ 'data' => $verification_link ],
			'entry_id'
		);

		// Save entry fields.
		wpforms()->get( 'entry_fields' )->save( $this->fields, $this->form_data, $entry_id, true );

		return [
			'hash'     => $verification_link,
			'entry_id' => $entry_id,
		];
	}

	/**
	 * Load entry data to fields properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties Properties.
	 * @param array $field      Field.
	 * @param array $data       Entry data.
	 *
	 * @return array
	 */
	public function get_entry( $properties, $field, $data ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity

		if ( in_array( $field['type'], $this->not_allowed_fields, true ) ) {
			return $properties;
		}

		$id    = (int) ! empty( $field['id'] ) ? $field['id'] : 0;
		$input = 'primary';

		// Radio, select, checkbox, gdpr-checkbox.
		if ( isset( $field['choices'] ) ) {
			$value_key = in_array( $field['type'], [ 'payment-checkbox', 'payment-select', 'payment-multiple' ], true ) ? 'value_choice' : 'value_raw';

			if ( ! isset( $data[ $id ][ $value_key ] ) ) {
				return $properties;
			}

			$delimiter = ! empty( $field['dynamic_choices'] ) ? ',' : "\n";
			$value     = explode( $delimiter, $data[ $id ][ $value_key ] );

			foreach ( $value as $single_value ) {
				$properties = ! empty( $field['dynamic_choices'] ) ? $this->get_dynamic_value_choices( trim( $single_value ), $properties ) : $this->get_value_choices( trim( $single_value ), $properties, $field );
			}

			return $properties;
		}

		if ( $field['type'] === 'net_promoter_score' ) {

			$get_value = stripslashes( sanitize_text_field( $data[ $id ]['value'] ) );

			if ( ! empty( $properties['inputs'][ $get_value ] ) ) {
				$properties['inputs'][ $get_value ]['attr']['checked'] = true;
			}

			return $properties;
		}

		if ( $field['type'] === 'rating' ) {
			return $this->get_rating_field_value( $data[ $id ]['value'], $input, $properties );
		}

		if ( $field['type'] === 'likert_scale' ) {

			if ( ! empty( $data[ $id ]['value_raw'] ) ) {

				$properties = $this->get_likert_scale_value( $data[ $id ]['value_raw'], $properties );
			}

			return $properties;
		}

		if ( in_array( $field['type'], [ 'richtext', 'textarea' ], true ) ) {

			$properties['inputs'][ $input ]['attr']['value'] = stripslashes( $data[ $id ]['value'] );

			return $properties;
		}

		if ( $field['type'] === 'date-time' ) {

			$properties = $this->get_date_time_field_value( $properties, $field, $data );

			return $properties;
		}

		// Common fields type which are processing the same.
		$inputs = [
			'address1',
			'address2',
			'city',
			'state',
			'postal',
			'country',
			'primary',
			'secondary',
			'first',
			'middle',
			'last',
		];

		foreach ( $inputs as $input ) {

			$value = isset( $data[ $id ][ $input ] ) ? $input : 'value';
			$properties['inputs'][ $input ]['attr']['value'] = stripslashes( sanitize_text_field( $data[ $id ][ $value ] ) );
		}

		return $properties;
	}

	/**
	 * Get choices values for Multiple choices, select, radio fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $get_value  Value.
	 * @param array  $properties Properties.
	 * @param array  $field      Field.
	 *
	 * @return array
	 */
	protected function get_value_choices( $get_value, $properties, $field ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity

		$default_key = null;

		// For fields that have normal choices we need to add extra logic.
		foreach ( $field['choices'] as $choice_key => $choice_arr ) {
			$choice_value_key = isset( $field['show_values'] ) ? 'value' : 'label';

			if (
				( isset( $choice_arr[ $choice_value_key ] ) &&
				  strtoupper( sanitize_text_field( $choice_arr[ $choice_value_key ] ) ) === strtoupper( $get_value )
				) ||
				(
					empty( $choice_arr[ $choice_value_key ] ) &&
					/* translators: %d - choice number. */
					$get_value === sprintf( esc_html__( 'Choice %d', 'wpforms-save-resume' ), (int) $choice_key )
				)
			) {
				$default_key = $choice_key;

				// Stop iterating over choices.
				break;
			}
		}

		// Redefine default choice only if population value has changed anything.
		if ( $default_key !== null ) {
			foreach ( $field['choices'] as $choice_key => $choice_arr ) {
				if ( $choice_key === $default_key ) {
					$properties['inputs'][ $choice_key ]['default']              = true;
					$properties['inputs'][ $choice_key ]['container']['class'][] = 'wpforms-selected';

					break;
				}
			}
		}

		return $properties;
	}

	/**
	 * Get choices values for Dynamic fields.
	 *
	 * @since 1.0.1
	 *
	 * @param string $get_value  Value.
	 * @param array  $properties Properties.
	 *
	 * @return array
	 */
	private function get_dynamic_value_choices( $get_value, $properties ) {

		$default_key = null;

		foreach ( $properties['inputs'] as $input_key => $input_arr ) {
			// Dynamic choices support only integers in its values.
			if ( absint( $get_value ) === $input_arr['attr']['value'] ) {
				$default_key = $input_key;

				// Stop iterating over choices.
				break;
			}
		}

		// Redefine default choice only if population value has changed anything.
		if ( $default_key !== null ) {
			$properties['inputs'][ $default_key ]['default']              = true;
			$properties['inputs'][ $default_key ]['container']['class'][] = 'wpforms-selected';
		}

		return $properties;
	}

	/**
	 * Get Likert scale values.
	 *
	 * @since 1.0.0
	 *
	 * @param array $raw_value  Value.
	 * @param array $properties Properties.
	 *
	 * @return array
	 */
	protected function get_likert_scale_value( $raw_value, $properties ) {

		$inputs = [];

		foreach ( $raw_value as $row => $column_array ) {
			foreach ( (array) $column_array as $column ) {
				$inputs[] = 'r' . (int) $row . '_c' . (int) $column;
			}
		}

		if ( empty( $inputs ) ) {
			return $properties;
		}

		foreach ( $inputs as $key ) {
			if ( isset( $properties['inputs'][ $key ] ) ) {
				$properties['inputs'][ $key ]['attr']['checked'] = true;
			}
		}

		return $properties;
	}

	/**
	 * Get Rating field value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw_value  Value.
	 * @param string $input      Input.
	 * @param array  $properties Properties.
	 *
	 * @return array
	 */
	protected function get_rating_field_value( $raw_value, $input, $properties ) {

		if ( ! is_string( $raw_value ) ) {
			return $properties;
		}

		$properties['inputs'][ $input ]['rating']['default'] = (int) $raw_value;

		return $properties;
	}

	/**
	 * Get Date/Time field value.
	 *
	 * @since 1.2.0
	 *
	 * @param array $properties Properties.
	 * @param array $field      Field.
	 * @param array $data       Entry data.
	 *
	 * @return array
	 */
	private function get_date_time_field_value( $properties, $field, $data ) {

		$id = ! empty( $field['id'] ) ? (int) $field['id'] : 0;

		foreach ( [ 'date', 'time' ] as $input ) {

			$value   = isset( $data[ $id ][ $input ] ) ? $input : 'value';
			$formats = wpforms_date_formats();

			$properties['inputs'][ $input ]['attr']['value'] =
				( $field['format'] === 'date' || ( $input === 'date' && $field['format'] === 'date-time' ) ) &&
				! empty( $data[ $id ]['unix'] ) && isset( $formats[ $field['date_format'] ] ) ?
					gmdate( $formats[ $field['date_format'] ], (int) $data[ $id ]['unix'] ) :
					stripslashes( sanitize_text_field( $data[ $id ][ $value ] ) );

			if (
				! empty( $field['date_type'] ) &&
				$field['date_type'] === 'dropdown' &&
				! empty( $data[ $id ]['unix'] )
			) {
				$properties['inputs'][ $input ]['default'] = [
					'd' => gmdate( 'd', $data[ $id ]['unix'] ),
					'm' => gmdate( 'm', $data[ $id ]['unix'] ),
					'y' => gmdate( 'Y', $data[ $id ]['unix'] ),
				];
			}
		}

		return $properties;
	}

	/**
	 * Check if entry already exists.
	 *
	 * @since      1.0.0
	 * @deprecated 1.2.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return int
	 */
	public static function check_if_exists( $form_id ) {

		_deprecated_function( __METHOD__, '1.2.0 of the WPForms Save and Resume addon', __CLASS__ . '::get_existing_partial_entry_id()' );

		return self::get_existing_partial_entry_id( $form_id );
	}

	/**
	 * Get partial entry ID via full URL, if it exists.
	 *
	 * @since 1.2.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return int Partial entry ID or 0 if it does not exist.
	 */
	public static function get_existing_partial_entry_id( $form_id ) {

		if ( wpforms_is_empty_string( self::get_resume_hash() ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$url   = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : esc_url_raw( wpforms_current_url() );
		$query = wp_parse_url( $url, PHP_URL_QUERY );

		wp_parse_str( $query, $query_vars );

		/**
		 * This is a workaround to save readable URLs in the database and compare them later. `Crypto::encrypt()` uses
		 * `base64_encode()`, which may pad the hash with 0-2 `=` symbols, and we save the URL in this form.
		 * But each time we pass this hash with `=` through `add_query_arg()` in `generate_hash_url()`,
		 * it removes the last `=` symbol.
		 *
		 * So for the lookup in the database we manually add an `=` symbol and replace spaces with `+`.
		 * This was done to avoid creating a migration and to have readable URLs in the DB.
		 */
		$hash = str_replace( ' ', '+', $query_vars['wpforms_resume_entry'] );

		if ( mb_substr( $hash, -1 ) === '=' ) {
			$hash .= '=';
		}

		$entry = wpforms()->get( 'entry_meta' )->get_meta(
			[
				'data'    => self::generate_hash_url( $hash, $form_id ),
				'form_id' => $form_id,
				'type'    => 'partial',
				'number'  => 1,
			]
		);

		return empty( $entry ) ? 0 : $entry[0]->entry_id;
	}

	/**
	 * Generate the hash from entry_id and append it to the current URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash    Hash.
	 * @param int    $form_id Form ID.
	 *
	 * @return string
	 */
	private static function generate_hash_url( $hash, $form_id ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$url       = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : esc_url_raw( wpforms_current_url() );
		$url       = remove_query_arg( [ 'wpforms_resume_entry', 'wpforms_form_id' ], $url );
		$form_data = wpforms()->get( 'form' )->get( $form_id, [ 'content_only' => true ] );

		// Clean URL if Dynamic form population is enabled.
		if ( ! empty( $form_data['settings']['dynamic_population'] ) ) {
			$url = self::clean_query_from_wpf( 'wpf' . $form_id, $url );
		}

		return add_query_arg( 'wpforms_resume_entry', $hash, $url );
	}

	/**
	 * Generate hash using entry ID.
	 *
	 * @since 1.2.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return string
	 */
	private function get_hash( $entry_id ) {

		return Crypto::encrypt( (string) $entry_id );
	}

	/**
	 * Remove an item from a query string by wpf+form_id pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $key   Query key or keys to remove.
	 * @param false|string $query Optional. When false uses the current URL. Default false.
	 *
	 * @return string New URL query string.
	 */
	private static function clean_query_from_wpf( $key, $query = false ) {

		if ( $query === false ) {
			return $query;
		}

		$query_vars = wp_parse_url( $query, PHP_URL_QUERY );

		parse_str( $query_vars, $keys );

		foreach ( $keys as $k => $v ) {

			// Check if query key starts with given param.
			if ( strpos( $k, $key ) === 0 ) {
				$query = add_query_arg( $k, false, $query );
			}
		}

		return $query;
	}

	/**
	 * Get link to the partial entry.
	 *
	 * @since 1.0.0
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return string
	 */
	public static function get_hash_url_by_entry( $entry_id ) {

		if ( empty( $entry_id ) ) {
			return '';
		}

		$saved_entry = wpforms()->get( 'entry_meta' )->get_meta(
			[
				'entry_id' => $entry_id,
				'type'     => 'partial',
				'number'   => 1,
			]
		);

		// Bail if this is not the partial entry type.
		if ( empty( $saved_entry ) ) {
			return '';
		}

		return $saved_entry[0]->data;
	}

	/**
	 * Get entry_id from provided hash code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hash Encrypted entry_id.
	 *
	 * @return int Entry ID or 0 when decrypting failed.
	 */
	public static function get_entry_by_hash( $hash ) {

		if ( ! is_string( $hash ) ) {
			return 0;
		}

		$hash = str_replace( ' ', '+', $hash );

		return (int) Crypto::decrypt( $hash );
	}

	/**
	 * Get resume hash.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public static function get_resume_hash() {

		// phpcs:ignore WordPress.Security.NonceVerification
		$url   = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : esc_url_raw( wpforms_current_url() );
		$query = wp_parse_url( $url, PHP_URL_QUERY );

		wp_parse_str( $query, $query_vars );

		return isset( $query_vars['wpforms_resume_entry'] ) ? $query_vars['wpforms_resume_entry'] : '';
	}

	/**
	 * Set entry meta with already deleted partial entry id.
	 *
	 * @since 1.3.0
	 *
	 * @param int $entry_id         Entry id.
	 * @param int $form_id          Form id.
	 * @param int $partial_entry_id Partial entry id.
	 */
	public static function set_partial_id_meta( $entry_id, $form_id, $partial_entry_id ) {

		$entry_meta_handler = wpforms()->get( 'entry_meta' );

		if ( ! $entry_meta_handler ) {
			return;
		}

		$entry_meta_handler->add(
			[
				'entry_id' => $entry_id,
				'form_id'  => absint( $form_id ),
				'user_id'  => get_current_user_id(),
				'type'     => self::PARTIAL_ENTRY_META_ID_TYPE,
				'data'     => $partial_entry_id,
			],
			'entry_meta'
		);
	}
}
