<?php
/**
 * class-groups-newsletters-campaign-controller.php
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
 * Campaign controller.
 *
 * Example:
 *
 * Sending limit: 10 per iteration
 *
 * Campaigns (A oldest, C newest) :
 *
 *                 A     B     C
 * pending emails  10    30    7
 *
 * Emails sent on iteration i
 *
 *      i     A     B     C
 *     -----------------------
 *      1     10    0     0
 *      2     --    10    0
 *      3           10    0
 *      4           10    0
 *      5           --    7
 *      6                 --
 *
 * As a campaign can be put on hold, the process
 * is adequate even when an urgent newsletter should
 * be sent while other older campaigns are running.
 * These would be put on hold manually and continued
 * once the urgent campaign has finished.
 */
class Groups_Newsletters_Campaign_Controller {

	/**
	 * Schedules.
	 */
	public static function init() {
		add_action( 'groups_newsletters_work', array( __CLASS__, 'work' ), 10, 0 );
		if ( !wp_next_scheduled( 'groups_newsletters_work' ) ) {
			$next = time() + Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_WORK_CYCLE, GROUPS_NEWSLETTERS_WORK_CYCLE_DEFAULT );
			wp_schedule_single_event( $next, 'groups_newsletters_work' );
		}
		add_action( 'groups_newsletters_deactivate', array( __CLASS__, 'deactivate' ) );
	}

	/**
	 * Clears scheduled work.
	 *
	 * @param boolean $network_wide
	 */
	public static function deactivate( $network_wide = false ) {
		wp_clear_scheduled_hook( 'groups_newsletters_work' );
	}

	/**
	 * Work through running campaigns.
	 */
	public static function work() {
		$campaign_ids = self::get_running_campaign_ids();
		$c = count( $campaign_ids );
		if ( $c > 0 ) {
			$i = 0;
			$n = self::get_limit_per_work_cycle();
			while ( $n > 0 && $i < $c ) {
				$campaign = new Groups_Newsletters_Campaign( $campaign_ids[$i] );
				$sent = $campaign->work( $n );
				$n -= $sent;
				$i++;
			}
		}
	}

	/**
	 * Returns the post IDs of currently running campaigns, oldest first.
	 *
	 * @return array of int, campaign post ids
	 */
	public static function get_running_campaign_ids() {
		// get campaigns in order of age, oldest first
		global $wpdb;
		$ids = array();
		if ( $results = $wpdb->get_results(
			"SELECT p.ID FROM $wpdb->posts p " .
			"LEFT JOIN $wpdb->postmeta m ON p.ID = m.post_id " .
			"WHERE p.post_type = 'campaign' AND p.post_status = 'publish' AND m.meta_key = 'campaign_status' AND m.meta_value = 'running' " .
			"ORDER BY p.post_date ASC"
		) ) {
			foreach ( $results as $result ) {
				$ids[] = $result->ID;
			}
		}
		return $ids;
	}

	/**
	 * Returns the maximum number of emails that are allowed to be sent per
	 * cycle.
	 *
	 * @return int limit per cycle
	 */
	public static function get_limit_per_work_cycle() {
		return Groups_Newsletters_Options::get_option( GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE, GROUPS_NEWSLETTERS_LIMIT_PER_WORK_CYCLE_DEFAULT );
	}
}
Groups_Newsletters_Campaign_Controller::init();
