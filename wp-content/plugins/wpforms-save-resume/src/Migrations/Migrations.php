<?php

namespace WPFormsSaveResume\Migrations;

use WPForms\Migrations\Base;

/**
 * Class Migrations handles addon upgrade routines.
 *
 * @since 1.2.0
 */
class Migrations extends Base {

	/**
	 * WP option name to store the migration versions.
	 *
	 * @since 1.2.0
	 */
	const MIGRATED_OPTION_NAME = 'wpforms_save_resume_versions';

	/**
	 * Current plugin version.
	 *
	 * @since 1.2.0
	 */
	const CURRENT_VERSION = WPFORMS_SAVE_RESUME_VERSION;

	/**
	 * Name of plugin used in log messages.
	 *
	 * @since 1.2.0
	 */
	const PLUGIN_NAME = 'WPForms Save and Resume';

	/**
	 * Upgrade classes.
	 *
	 * @since 1.2.0
	 */
	const UPGRADE_CLASSES = [
		'Upgrade120',
	];
}
