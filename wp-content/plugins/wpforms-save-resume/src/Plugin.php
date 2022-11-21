<?php

namespace WPFormsSaveResume;

use WPForms_Updater;
use WPFormsSaveResume\Admin\Admin;
use WPFormsSaveResume\Admin\Builder;
use WPFormsSaveResume\Tasks\DeleteExpiredEntriesTask;
use WPFormsSaveResume\Migrations\Migrations;

/**
 * The Plugin.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Private plugin constructor to avoid initialization of new instances.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {}

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public function init() {

		$this->hooks();

		return $this;
	}

	/**
	 * Plugin hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {

		add_action( 'wpforms_loaded', [ $this, 'setup' ], 20 );
		add_action( 'wpforms_updater', [ $this, 'updater' ] );
		add_filter( 'wpforms_tasks_get_tasks', [ $this, 'register_cleaning_task' ] );
		add_action( 'wpforms_display_submit_after', [ $this, 'display_save_resume' ], 10, 1 );
	}

	/**
	 * Get a single instance of the addon.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function get_instance() {

		static $instance = null;

		if ( ! $instance instanceof self ) {
			$instance = ( new self() )->init();
		}

		return $instance;
	}

	/**
	 * Run plugin.
	 *
	 * @since 1.0.0
	 */
	public function setup() {

		register_deactivation_hook( WPFORMS_SAVE_RESUME_FILE, [ $this, 'deactivate' ] );

		( new Migrations() )->init();

		if ( wpforms_is_admin_page( 'builder' ) || wp_doing_ajax() ) {
			( new Builder() )->init();
		}

		( new ResumeLink() )->init();
		( new Admin() )->init();
		( new Frontend() )->init();
	}

	/**
	 * Load the addon updater.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key License key.
	 */
	public function updater( $key ) {

		new WPForms_Updater(
			[
				'plugin_name' => 'WPForms Save and Resume',
				'plugin_slug' => 'wpforms-save-resume',
				'plugin_path' => plugin_basename( WPFORMS_SAVE_RESUME_FILE ),
				'plugin_url'  => trailingslashit( WPFORMS_SAVE_RESUME_URL ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_SAVE_RESUME_VERSION,
				'key'         => $key,
			]
		);
	}

	/**
	 * Register expired entries cleanup task.
	 *
	 * @since 1.0.0
	 *
	 * @param array $tasks List of already registered tasks.
	 *
	 * @return array
	 */
	public function register_cleaning_task( $tasks ) {

		$tasks[] = DeleteExpiredEntriesTask::class;

		return $tasks;
	}

	/**
	 * Display Save and Resume Later link where it is enabled.
	 *
	 * @since 1.2.0
	 *
	 * @param array $form_data Form data.
	 */
	public function display_save_resume( $form_data ) {

		if ( ! wpforms_is_admin_page( 'builder' ) && ! $this->is_enabled( $form_data ) ) {
			return;
		}

		$link = ! empty( $form_data['settings']['save_resume_link_text'] ) ?
			$form_data['settings']['save_resume_link_text'] :
			Settings::get_default_link_text();

		printf( '<a href="#" class="wpforms-save-resume-button"><span>%s</span></a>', esc_attr( $link ) );
	}

	/**
	 * Cancel cleanup partial entries task, remove the task.
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		( new DeleteExpiredEntriesTask() )->cancel();

		if ( class_exists( 'ActionScheduler_DBStore' ) ) {
			\ActionScheduler_DBStore::instance()->cancel_actions_by_hook( DeleteExpiredEntriesTask::ACTION );
		}
	}

	/**
	 * Whether Save and Resume is enabled for the form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function is_enabled( $form_data ) {

		return isset( $form_data['settings']['save_resume_enable'] );
	}
}
