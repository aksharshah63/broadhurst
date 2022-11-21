<?php

namespace WPFormsSurveys\Migrations;

use WPForms\Migrations\Base;

/**
 * Class Migrations handles addon upgrade routines.
 *
 * @since 1.9.0
 */
class Migrations extends Base {

	/**
	 * WP option name to store the migration versions.
	 *
	 * @since 1.9.0
	 */
	const MIGRATED_OPTION_NAME = 'wpforms_surveys_polls_versions';

	/**
	 * Current plugin version.
	 *
	 * @since 1.9.0
	 */
	const CURRENT_VERSION = WPFORMS_SURVEYS_POLLS_VERSION;

	/**
	 * Name of plugin used in log messages.
	 *
	 * @since 1.9.0
	 */
	const PLUGIN_NAME = 'WPForms Surveys and Polls';

	/**
	 * Upgrade classes.
	 *
	 * @since 1.9.0
	 */
	const UPGRADE_CLASSES = [
		'Upgrade190',
	];
}
