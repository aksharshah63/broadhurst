<?php
/**
 * class-groups-newsletters-controller.php
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is provided subject to the license granted.
 * Unauthorized use and distribution is prohibited.
 * See COPYRIGHT.txt and LICENSE.txt
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package groups-newsletters
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin controller and booter.
 */
class Groups_Newsletters_Controller {

	/**
	 * Admin messages
	 *
	 * @var array
	 */
	public static $admin_messages = array();

	/**
	 * Boots the plugin.
	 */
	public static function boot() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		load_plugin_textdomain( 'groups-newsletters', null, 'groups-newsletters/languages' );
		require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-options.php';
		if ( self::check_dependencies() ) {
			require_once GROUPS_NEWSLETTERS_CORE_LIB . '/constants.php';
			require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters.php';
			require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-campaign.php';
			require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-campaign-controller.php';

			require_once GROUPS_NEWSLETTERS_EXT_LIB . '/class-groups-newsletters-story-post-type.php';
			require_once GROUPS_NEWSLETTERS_EXT_LIB . '/class-groups-newsletters-campaign-post-type.php';
			require_once GROUPS_NEWSLETTERS_EXT_LIB . '/class-groups-newsletters-taxonomy.php';
			if ( Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SHOW_REGISTRATION_OPT_IN, true ) ) {
				require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-registration.php';
			}
			require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-shortcodes.php';
			if ( is_admin() ) {
				require_once GROUPS_NEWSLETTERS_ADMIN_LIB . '/class-groups-newsletters-subscribers-list-table.php';
				require_once GROUPS_NEWSLETTERS_ADMIN_LIB . '/class-groups-newsletters-settings.php';
			}
			require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-widget.php';
			require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-subscribe-widget.php';
			require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-tags-widget.php';
			require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-search-widget.php';
			require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-stories-widget.php';
			if ( !is_admin() ) {
				require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-templates.php';
			}
			if ( is_admin() ) {
				if ( Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_SHOW_PROFILE_OPT_IN, true ) ) {
					require_once GROUPS_NEWSLETTERS_ADMIN_LIB . '/class-groups-newsletters-admin-user-profile.php';
				}
				require_once GROUPS_NEWSLETTERS_ADMIN_LIB . '/class-groups-newsletters-admin-users.php';
				require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-export.php';
				require_once GROUPS_NEWSLETTERS_VIEWS_LIB . '/class-groups-newsletters-import.php';
			}
			register_activation_hook( GROUPS_NEWSLETTERS_PLUGIN_FILE, array( __CLASS__, 'activate' ) );
			register_deactivation_hook( GROUPS_NEWSLETTERS_PLUGIN_FILE, array( __CLASS__, 'deactivate' ) );
			add_action( 'init', array( __CLASS__, 'wp_init' ) );
			add_action( 'user_register', array( __CLASS__, 'user_register' ) );
			// @since 2.0.0 WooCommerce integration built-in
			require_once GROUPS_NEWSLETTERS_WOOCOMMERCE_CORE_LIB . '/class-groups-newsletters-woocommerce.php';
		}
	}

	/**
	 * Init hook - recognize activation links.
	 */
	public static function wp_init() {
		if ( isset( $_REQUEST['groups_newsletters_activation'] ) && isset( $_REQUEST['email'] ) ) {
			global $wpdb;
			$subscriber_table = self::get_tablename( 'subscriber' );
			$hash  = $_REQUEST['groups_newsletters_activation'];
			$email = $_REQUEST['email'];
			if ( ( $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $subscriber_table WHERE email = %s AND hash = %s AND status = 0", $email, $hash ) ) ) && isset( $results[0] ) ) {
				if ( $wpdb->query( $wpdb->prepare( "UPDATE $subscriber_table SET status = 1 WHERE subscriber_id = %d", intval( $results[0]->subscriber_id ) ) ) ) {
					do_action( 'groups_newsletters_subscriber_activated', $results[0] );
					// redirect to confirmation page
					$activation_post_id = Groups_Newsletters_Options::get_option( 'activation-post-id', null );
					if ( $activation_post_id !== null ) {
						wp_redirect(
							add_query_arg(
								array(
									'groups_newsletters_activation' => urlencode( $hash ),
									'email' => urlencode( $email )
								),
								get_permalink( $activation_post_id )
							)
						);
						exit;
					}
				}
			}
		} else if ( isset( $_REQUEST['groups_newsletters_cancellation'] ) && isset( $_REQUEST['email'] ) ) {
			global $wpdb;
			$subscriber_table = self::get_tablename( 'subscriber' );
			$hash  = $_REQUEST['groups_newsletters_cancellation'];
			$email = $_REQUEST['email'];
			if ( ( $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $subscriber_table WHERE email = %s AND hash = %s AND status = 1", $email, $hash ) ) ) && isset( $results[0] ) ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM $subscriber_table WHERE subscriber_id = %d", intval( $results[0]->subscriber_id ) ) );
				do_action( 'groups_newsletters_subscriber_cancelled', $results[0] );
				// redirect to confirmation page
				$activation_post_id = Groups_Newsletters_Options::get_option( 'activation-post-id', null );
				if ( $activation_post_id !== null ) {
					wp_redirect(
						add_query_arg(
							array(
								'groups_newsletters_cancellation' => urlencode( $hash ),
								'email' => urlencode( $email )
							),
							get_permalink( $activation_post_id )
						)
					);
					exit;
				}
			} else {
				// registered users can use the cancellation link as well
				if ( $user = get_user_by( 'email', $email ) ) {
					$user_hash = get_user_meta( $user->ID, 'groups_newsletters_hash', true );
					if ( $hash == $user_hash ) {
						$is_subscriber = get_user_meta( $user->ID, 'groups_newsletters_subscriber', true );
						if ( $is_subscriber == 'yes' ) {
							if ( delete_user_meta( $user->ID, 'groups_newsletters_subscriber' ) ) {
								delete_user_meta( $user->ID, 'groups_newsletters_hash' );
								delete_user_meta( $user->ID, 'groups_newsletters_datetime' );
								do_action( 'groups_newsletters_user_unsubscribed', $user->ID );
								// redirect to confirmation page
								$activation_post_id = Groups_Newsletters_Options::get_option( 'activation-post-id', null );
								if ( $activation_post_id !== null ) {
									wp_redirect(
										add_query_arg(
											array(
												'groups_newsletters_cancellation' => urlencode( $hash ),
												'email' => urlencode( $email )
											),
											get_permalink( $activation_post_id )
										)
									);
									exit;
								}
							}
						}
					}
				}
			}
		}
		self::version();
	}

	/**
	 * Version update.
	 */
	private static function version() {
		global $wpdb;
		$subscriber_table = self::get_tablename( 'subscriber' );
		$queue_table      = self::get_tablename( 'queue' );
		$version          = Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_VERSION, null );
		if ( version_compare( $version, GROUPS_NEWSLETTERS_PLUGIN_VERSION ) < 0 ) {
			if (
				( $wpdb->get_var( "SHOW TABLES LIKE '$subscriber_table'" ) == $subscriber_table ) &&
				( $wpdb->get_var( "SHOW TABLES LIKE '$queue_table'" ) == $queue_table )
			) {
				if ( apply_filters( 'groups_newsletters_version_update', true, $version, GROUPS_NEWSLETTERS_PLUGIN_VERSION ) ) {
					Groups_Newsletters_Options::update_option(
						GROUPS_NEWSLETTERS_VERSION,
						GROUPS_NEWSLETTERS_PLUGIN_VERSION
					);
				}
			}
		}
	}

	/**
	 * Remove from subscriber table and add hash to user meta if the user's
	 * email address is there.
	 *
	 * @param int $user_id
	 */
	public static function user_register( $user_id ) {
		if ( $user = get_user_by( 'id', $user_id ) ) {
			global $wpdb;
			$subscriber_table = self::get_tablename( 'subscriber' );
			if ( ( $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $subscriber_table WHERE email = %s", $user->user_email ) ) ) && isset( $results[0] ) ) {
				// only subscribe the user if the email subscription was activated
				if ( $results[0]->status ) {
					if ( update_user_meta( $user_id, 'groups_newsletters_subscriber', 'yes' ) ) {
						update_user_meta( $user_id, 'groups_newsletters_hash', $results[0]->hash );
						update_user_meta( $user_id, 'groups_newsletters_datetime', $results[0]->subscribed );
					}
				}
				$wpdb->query( $wpdb->prepare( "DELETE FROM $subscriber_table WHERE subscriber_id = %d", $results[0]->subscriber_id ) );
			}
			// Note: no actions to be invoked here w/r to newsletter subscription
		}
	}

	/**
	 * Activation hook.
	 *
	 * @param boolean $network_wide
	 */
	public static function activate( $network_wide = false ) {

		global $wpdb;

		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$subscriber_table = self::get_tablename( 'subscriber' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$subscriber_table'" ) != $subscriber_table ) {
			$queries[] = "CREATE TABLE $subscriber_table (
			subscriber_id BIGINT(20) UNSIGNED NOT NULL auto_increment,
			email         VARCHAR(256) NOT NULL,
			status        TINYINT DEFAULT 0,
			subscribed    DATETIME DEFAULT NULL,
			hash          VARCHAR(100) NOT NULL,
			PRIMARY KEY   (subscriber_id),
			UNIQUE INDEX  subscriber_email (email (20)),
			INDEX         subscriber_status (status),
			INDEX         subscriber_hash (hash (20))
			) $charset_collate;";
		} else {
			if ( Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_VERSION, null ) === null ) {
				$queries[] = "ALTER TABLE $subscriber_table DROP INDEX subscriber_email;";
				$queries[] = "ALTER TABLE $subscriber_table ADD INDEX subscriber_email (email (20));";
			}
		}

		$queue_table = self::get_tablename( 'queue' );
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$queue_table'" ) != $queue_table ) {
			$queries[] = "CREATE TABLE $queue_table (
			queue_id      BIGINT(20) UNSIGNED NOT NULL auto_increment,
			campaign_id   BIGINT(20) UNSIGNED NOT NULL,
			newsletter_id BIGINT(20) UNSIGNED NOT NULL,
			email         VARCHAR(256) NOT NULL,
			status        TINYINT DEFAULT 0,
			PRIMARY KEY   (queue_id),
			UNIQUE INDEX  (campaign_id, newsletter_id, email (20))
			) $charset_collate;";
		}

		if ( !empty( $queries ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $queries );
		}

		// Flush rewrite rules after registering our document post type
		Groups_Newsletters_Story_Post_Type::post_type();
		// and the Newsletter taxonomy
		Groups_Newsletters_Taxonomy::taxonomy();
		flush_rewrite_rules( false );
	}

	/**
	 * Deletes plugin data, stories, newsletters and campaigns if so chosen.
	 *
	 * @param boolean $network_wide
	 */
	public static function deactivate( $network_wide = false ) {

		if ( Groups_Newsletters_Options::get_option( Groups_Newsletters::DELETE_DATA, Groups_Newsletters::DELETE_DATA_DEFAULT ) ) {

			global $wpdb;

			// delete all stories
			if ( $posts = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'story'" ) ) {
				foreach( $posts as $post ) {
					wp_delete_post( $post->ID, true );
				}
			}

			// delete all campaigns
			if ( $posts = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'campaign'" ) ) {
				foreach ( $posts as $post ) {
					wp_delete_post( $post->ID, true );
				}
			}

			// Deleting taxonomy terms on uninstall or deactivation ...
			// ... not so fast ...

			// At WordPress 3.6-beta3 the situation is still this:
			// @todo see http://core.trac.wordpress.org/ticket/23069
			// It doesn't work here either, even when we try to register the
			// taxonomies before doing are calls to wp_delete_term(), so the
			// following won't work ...

// 			Groups_Newsletters_Taxonomy::taxonomy();

// 			// delete all newsletters
// 			if ( $term_ids = get_terms( 'newsletter', array( 'fields' => 'ids' ) ) ) {
// 				foreach ( $term_ids as $term_id ) {
// 					wp_delete_term( $term->term_id, 'newsletter' );
// 				}
// 			}

// 			// delete all newsletter tags
// 			if ( $term_ids = get_terms( 'story_tag', array( 'fields' => 'ids' ) ) ) {
// 				foreach ( $term_ids as $term_id ) {
// 					wp_delete_term( $term->term_id, 'story_tag' );
// 				}
// 			}

			// ... and we have to do it manually
			$wpdb->query(
				"DELETE FROM $wpdb->terms " .
				"WHERE term_id IN " .
				"( SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy IN ( 'newsletter', 'story_tag' ) ) "
			);
			// Note that as we have already deleted our stories and campaigns,
			// we won't have to touch $wpdb->term_relationships ... but this
			// would be a problem if someone else is using the terms.
			// Well for now, that would be SEP.

			// user subscribers
			delete_metadata( 'user', null, 'groups_newsletters_subscriber', null, true );

			// tables
			$wpdb->query( 'DROP TABLE IF EXISTS ' . self::get_tablename( 'subscriber' ) );
			$wpdb->query( 'DROP TABLE IF EXISTS ' . self::get_tablename( 'queue' ) );

			// delete plugin options
			Groups_Newsletters_Options::flush_options();
		}
		do_action( 'groups_newsletters_deactivate', $network_wide );

		// Flush rewrite rules on deactivation
		flush_rewrite_rules( false );
	}

	/**
	 * Checks if Groups is activated.
	 *
	 * @return true if Groups is there, false otherwise
	 */
	public static function check_dependencies() {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
			$active_plugins = array_merge( $active_plugins, $active_sitewide_plugins );
		}
		$groups_is_active = in_array( 'groups/groups.php', $active_plugins );
		define( 'GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE', $groups_is_active );
		return true;
	}

	/**
	 * Prints admin notices.
	 */
	public static function admin_notices() {
		if ( !empty( self::$admin_messages ) ) {
			foreach ( self::$admin_messages as $msg ) {
				echo $msg;
			}
		}
	}

	/**
	 * Returns the prefixed table name.
	 *
	 * @param string $name name of the table
	 * @return string prefixed table name
	 */
	public static function get_tablename( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'groups_newsletters_' . $name;
	}
}
Groups_Newsletters_Controller::boot();
