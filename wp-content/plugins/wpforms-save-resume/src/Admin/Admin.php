<?php

namespace WPFormsSaveResume\Admin;

/**
 * The Admin.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * Handle name for wp_register_styles handle.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	const HANDLE = 'wpforms-save-resume';

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

		add_filter( 'wpforms_entries_table_counts', [ $this, 'entries_table_counts' ], 10, 2 );
		add_filter( 'wpforms_entries_table_views', [ $this, 'entries_table_views' ], 10, 3 );
		add_filter( 'wpforms_entry_details_sidebar_details_status', [ $this, 'entries_details_sidebar_status' ], 10, 3 );
		add_filter( 'wpforms_entry_details_sidebar_actions_link', [ $this, 'entries_details_sidebar_actions' ], 10, 3 );
		add_filter( 'wpforms_entries_table_column_status', [ $this, 'entries_table_column_status' ], 10, 2 );

		// Save and Resume button styles for the Gutenberg/Block editor.
		add_action( 'enqueue_block_editor_assets', [ $this, 'gutenberg_enqueues' ] );

		// Set editor style for block type editor. Must run at 20 in add-ons.
		add_filter( 'register_block_type_args', [ $this, 'register_block_type_args' ], 20, 2 );

		if ( wpforms_is_admin_page( 'entries' ) ) {
			add_action( 'wpforms_entry_details_sidebar_details', [ $this, 'add_expires_date_metabox' ], 10, 2 );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_script' ] );
		}
	}

	/**
	 * Enable the displaying status for forms which have Partial entries.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $show      Whether to show the Status column or not.
	 * @param object $entry     Entry information.
	 * @param array  $form_data Form data.
	 *
	 * @return bool
	 */
	public function entries_details_sidebar_status( $show, $entry, $form_data ) {

		if ( wpforms_save_resume()->is_enabled( $form_data ) ) {
			return true;
		}

		return $show;
	}

	/**
	 * For partial entries remove the link to resend email notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $links     List of links in sidebar.
	 * @param object $entry     Entry information.
	 * @param array  $form_data Form data.
	 *
	 * @return array
	 */
	public function entries_details_sidebar_actions( $links, $entry, $form_data ) {

		if ( wpforms_save_resume()->is_enabled( $form_data ) ) {
			unset( $links['notifications'] );
		}

		return $links;
	}

	/**
	 * Enable the Status column for forms that have Partial entries.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $show      Whether to show the Status column or not.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function entries_table_column_status( $show, $form_data ) {

		if ( wpforms_save_resume()->is_enabled( $form_data ) ) {
			return true;
		}

		return $show;
	}

	/**
	 * Get counts for partial entries.
	 *
	 * @since 1.0.0
	 *
	 * @param array $counts    Entries count list.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	public function entries_table_counts( $counts, $form_data ) {

		if ( wpforms_save_resume()->is_enabled( $form_data ) ) {
			$counts['partial'] = wpforms()->get( 'entry' )->get_entries(
				[
					'form_id' => absint( $form_data['id'] ),
					'status'  => 'partial',
				],
				true
			);
		}

		return $counts;
	}

	/**
	 * Create view for partial entries.
	 *
	 * @since 1.0.0
	 *
	 * @param array $views     Filters for entries various states.
	 * @param array $form_data Form data.
	 * @param array $counts    Entries count list.
	 *
	 * @return array
	 */
	public function entries_table_views( $views, $form_data, $counts ) {

		if ( wpforms_save_resume()->is_enabled( $form_data ) ) {

			$base = add_query_arg(
				[
					'page'    => 'wpforms-entries',
					'view'    => 'list',
					'form_id' => absint( $form_data['id'] ),
				],
				admin_url( 'admin.php' )
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
			$partial = '&nbsp;<span class="count">(<span class="partial-num">' . $counts['partial'] . '</span>)</span>';

			$views['partial'] = sprintf(
				'<a href="%1$s" class="%2$s">%3$s</a>',
				esc_url( add_query_arg( 'status', 'partial', $base ) ),
				$current === 'partial' ? ' current' : '',
				esc_html__( 'Partial', 'wpforms-save-resume' ) . $partial
			);
		}

		return $views;
	}

	/**
	 * Load styles for the Gutenberg editor.
	 *
	 * @since 1.0.0
	 */
	public function gutenberg_enqueues() {

		// Add inline CSS without the need to enquire handler.
		wp_register_style( 'wpforms-save-resume-admin', false );
		wp_enqueue_style( 'wpforms-save-resume-admin' );

		$custom_css = /* @lang CSS */
			'div.wpforms-container-full .wpforms-form .wpforms-save-resume-button { 
				font-family: sans-serif; 
				font-size: 14px; 
				line-height: 17px; 
				text-decoration: underline; 
				color: #777777; 
				cursor: pointer; 
				margin: 0 20px; 
			}';

		wp_add_inline_style( 'wpforms-save-resume-admin', $custom_css );

		if ( version_compare( get_bloginfo( 'version' ), '5.5', '>=' ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_register_style(
			self::HANDLE,
			WPFORMS_SAVE_RESUME_URL . "assets/css/wpforms-save-resume{$min}.css",
			[],
			WPFORMS_SAVE_RESUME_VERSION
		);
	}

	/**
	 * Set editor style handle for block type editor.
	 *
	 * @see WPForms_Field_File_Upload::register_block_type_args
	 *
	 * @since 1.2.0
	 *
	 * @param array  $args       Array of arguments for registering a block type.
	 * @param string $block_type Block type name including namespace.
	 */
	public function register_block_type_args( $args, $block_type ) {

		if ( $block_type !== 'wpforms/form-selector' ) {
			return $args;
		}

		$min = wpforms_get_min_suffix();

		wp_register_style(
			self::HANDLE,
			WPFORMS_SAVE_RESUME_URL . "assets/css/wpforms-save-resume{$min}.css",
			[ $args['editor_style'] ],
			WPFORMS_SAVE_RESUME_VERSION
		);

		$args['editor_style'] = self::HANDLE;

		return $args;
	}

	/**
	 * Load required scripts.
	 *
	 * @since 1.2.0
	 */
	public function enqueue_script() {

		$min = wpforms_get_min_suffix();

		wp_enqueue_style(
			'tooltipster',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.tooltipster/jquery.tooltipster.min.css',
			[],
			'4.2.6'
		);

		wp_enqueue_script(
			'tooltipster',
			WPFORMS_PLUGIN_URL . 'assets/lib/jquery.tooltipster/jquery.tooltipster.min.js',
			[ 'jquery', 'wpforms-save-resume-admin' ],
			'4.2.6',
			true
		);

		wp_enqueue_script(
			'wpforms-save-resume-admin',
			WPFORMS_SAVE_RESUME_URL . "assets/js/admin-save-resume{$min}.js",
			[ 'jquery' ],
			WPFORMS_SAVE_RESUME_VERSION,
			true
		);
	}

	/**
	 * Add new row to Entry Detail box in Entry view/edit page.
	 *
	 * @since 1.2.0
	 *
	 * @param object $entry     Entry values.
	 * @param array  $form_data Form data and settings.
	 *
	 * @return void
	 */
	public function add_expires_date_metabox( $entry, $form_data ) {

		if ( ! wpforms_save_resume()->is_enabled( $form_data ) ) {
			return;
		}

		if ( $entry->status !== 'partial' ) {
			return;
		}

		// phpcs:disable WPForms.PHP.ValidateHooks.InvalidHookName
		/** This filter is documented in src/Tasks/DeleteExpiredEntriesTask.php */
		$period = apply_filters( 'wpforms_save_resume_tasks_deleteexpiredentriestask_expire_period', '-30 days' );
		// phpcs:enable WPForms.PHP.ValidateHooks.InvalidHookName
		$start = strtotime( $period );
		$end   = strtotime( $entry->date_modified );

		// In case if entry already expired, do not show resume link and days to expire and.
		if ( $end < $start ) {
			return;
		}

		$date_diff = round( ( $end - $start ) / DAY_IN_SECONDS );
		// Get partial entry meta object.
		$saved_entry = wpforms()->get( 'entry_meta' )->get_meta(
			[
				'entry_id' => $entry->entry_id,
				'type'     => 'partial',
				'number'   => 1,
			]
		);

		$expires_in = sprintf( /* translators: %d - number of days before the entry expires. */
			_n( '%d day', '%d days', $date_diff, 'wpforms-save-resume' ),
			number_format_i18n( $date_diff )
		);

		printf(
			'<p class="wpforms-entry-expires">
				<span class="dashicons dashicons-admin-page wpforms-save-resume-help-tooltip" title="%1$s"></span> 
				%2$s <strong>%3$s, </strong>
				<a href="%4$s" target="_blank" rel="noopener" class="wpforms-entry-copy-link" data-success-copy="%5$s">
					<strong>%6$s</strong>
				</a>
			</p>',
			esc_html__( 'Save and Resume', 'wpforms-save-resume' ),
			esc_html__( 'Expires:', 'wpforms-save-resume' ),
			esc_html( $expires_in ),
			esc_url( $saved_entry[0]->data ),
			esc_html__( 'Copied!', 'wpforms-save-resume' ),
			esc_html__( 'Copy Link', 'wpforms-save-resume' )
		);
	}
}
