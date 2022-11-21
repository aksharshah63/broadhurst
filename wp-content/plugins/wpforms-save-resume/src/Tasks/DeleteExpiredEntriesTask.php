<?php

namespace WPFormsSaveResume\Tasks;

use WPForms\Tasks\Task;

/**
 * Class DeleteExpiredEntriesTask.
 *
 * @since 1.0.0
 */
class DeleteExpiredEntriesTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 1.0.0
	 */
	const ACTION = 'wpforms_save_resume_clean';

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );

		$this->init();
	}
	/**
	 * Init.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->hooks();

		$tasks = wpforms()->get( 'tasks' );

		// Add new if none exists.
		if ( $tasks->is_scheduled( self::ACTION ) !== false ) {
			return;
		}

		$this->recurring( strtotime( 'tomorrow' ), DAY_IN_SECONDS )
			->register();
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.1.0
	 */
	private function hooks() {

		add_action( self::ACTION, [ $this, 'process' ] );
	}


	/**
	 * Perform the cleanup action: remove outdated meta for entry emails task.
	 *
	 * @since 1.0.0
	 */
	public function process() {

		$entry   = wpforms()->get( 'entry' );
		$entries = $entry->get_entries( [ 'status' => 'partial' ] );

		$expire_period = '-30 days';

		foreach ( $entries as $item ) {

			/**
			 * Allow changing expiration period of partial entries for the Save and Resume functionality.
			 *
			 * @since 1.0.0
			 *
			 * @param string $expire_period The string with expiration date to parse. Default: '-30 days'.
			 */
			if ( strtotime( $item->date_modified ) < strtotime( apply_filters( 'wpforms_save_resume_tasks_deleteexpiredentriestask_expire_period', $expire_period ) ) ) {
				$entry->delete( $item->entry_id );
			}
		}
	}
}
