<?php

namespace WPFormsSaveResume\Migrations;

use WPForms\Migrations\UpgradeBase;
use WPForms\Tasks\Actions\Migration173Task;

/**
 * Class Save Resume addon v1.2.0 upgrade.
 *
 * @since 1.2.0
 *
 * @noinspection PhpUnused
 */
class Upgrade120 extends UpgradeBase {

	/**
	 * Run upgrade.
	 *
	 * @since 1.2.0
	 *
	 * @return bool|null Upgrade result:
	 *                   true  - the upgrade completed successfully,
	 *                   false - in the case of failure,
	 *                   null  - upgrade started but not yet finished (background task).
	 */
	public function run() {

		return $this->run_async( Migration173Task::class );
	}
}
