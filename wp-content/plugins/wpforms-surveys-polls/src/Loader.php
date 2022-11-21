<?php

namespace WPFormsSurveys;

/**
 * WPForms Surveys and Polls loader class.
 *
 * @since 1.0.0
 */
final class Loader {

	/**
	 * Have the only available instance of the class.
	 *
	 * @var Loader
	 *
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * URL to a plugin directory. Used for assets.
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	public $url = '';

	/**
	 * Initiate main plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Loader
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Loader ) ) {
			self::$instance = new Loader();
		}

		return self::$instance;
	}

	/**
	 * Loader constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->url = plugin_dir_url( __DIR__ );

		add_action( 'wpforms_loaded', [ $this, 'init' ], 15 );
	}

	/**
	 * All the actual plugin loading is done here.
	 *
	 * @since 1.0.0
	 */
	public function init() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		( new Migrations\Migrations() )->init();

		new Reporting\Ajax();
		new Fields\LikertScale\Field();
		new Fields\NetPromoterScore\Field();
		new Polls();

		// The admin_init action is too late for FSE.
		// We have to run it before register_block_type() is executed in \WPForms\Integrations\Gutenberg\FormSelector.
		new Admin();

		add_action(
			'admin_init',
			function() {
				new Reporting\Admin();
				new Templates\Poll();
				new Templates\Survey();
				new Templates\NPSSurveySimple();
				new Templates\NPSSurveyEnhanced();
			}
		);

		if ( is_admin() ) {
			( new Fields\LikertScale\EntriesEdit() )->init();
			( new Fields\NetPromoterScore\EntriesEdit() )->init();
		}

		// Register the updater of this plugin.
		$this->updater();
	}

	/**
	 * Load the plugin updater.
	 *
	 * @since 1.0.0
	 */
	private function updater() {

		$url = $this->url;

		add_action( 'wpforms_updater', function( $key ) use ( $url ) {

			new \WPForms_Updater(
				array(
					'plugin_name' => 'WPForms Surveys and Polls',
					'plugin_slug' => 'wpforms-surveys-polls',
					'plugin_path' => plugin_basename( WPFORMS_SURVEYS_POLLS_FILE ),
					'plugin_url'  => trailingslashit( $url ),
					'remote_url'  => WPFORMS_UPDATER_API,
					'version'     => WPFORMS_SURVEYS_POLLS_VERSION,
					'key'         => $key,
				)
			);
		} );
	}
}
