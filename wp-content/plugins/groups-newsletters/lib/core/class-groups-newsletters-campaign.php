<?php
/**
 * class-groups-newsletters-campaign.php
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
 * Campaign abstraction and worker.
 */
class Groups_Newsletters_Campaign {

	/**
	 * Post
	 *
	 * @var object
	 */
	private $post = null;

	/**
	 * Class constructor
	 *
	 * @param int $post_id
	 */
	public function __construct( $post_id ) {
		if ( $post = get_post( $post_id ) ) {
			if ( $post->post_type == 'campaign' ) {
				$this->post = $post;
			}
		}
	}

	/**
	 * Campaign worker job. Called by scheduler.
	 *
	 * @param int $n
	 */
	public function work( $n = 0 ) {
		global $wpdb;
		if ( $this->post !== null ) {
			if ( $this->post->post_status == 'publish' ) {
				$campaign_status = get_post_meta( $this->post->ID, 'campaign_status', true );
				$campaign_queued = get_post_meta( $this->post->ID, 'campaign_queued', true );
				if ( $campaign_status == 'running' ) {
					if ( $campaign_queued == 'yes' ) {

						if ( $n > 0 ) {
							$queue_table = Groups_Newsletters_Controller::get_tablename( 'queue' );
							$k = 0;
							$priority = false;
							if ( class_exists( 'Groups_Restrict_Categories' ) && method_exists( 'Groups_Restrict_Categories', 'list_terms_exclusions' ) ) {
								$priority = has_filter( 'list_terms_exclusions', array( 'Groups_Restrict_Categories', 'list_terms_exclusions' ) );
								if ( $priority !== false ) {
									remove_filter( 'list_terms_exclusions', array( __CLASS__, 'list_terms_exclusions' ), $priority );
								}
							}
							$term_ids = wp_get_post_terms( $this->post->ID, 'newsletter', array( 'fields' => 'ids' ) );
							if ( $priority !== false ) {
								add_filter( 'list_terms_exclusions', array( 'Groups_Restrict_Categories', 'list_terms_exclusions' ), $priority, 3 );
							}
							if ( count( $term_ids ) > 0 ) {
								require_once GROUPS_NEWSLETTERS_CORE_LIB . '/class-groups-newsletters-newsletter.php';
								// for all newsletters in this campaign :
								foreach ( $term_ids as $term_id ) {
									if ( $k >= $n ) {
										break;
									}
									if ( $recipients = $wpdb->get_results( $wpdb->prepare(
										"SELECT * FROM $queue_table WHERE campaign_id = %d AND newsletter_id = %d AND status = %d LIMIT %d",
										intval( $this->post->ID ),
										intval( $term_id ),
										0,
										intval( $n )
									) ) ) {
										$newsletter = new Groups_Newsletters_Newsletter( $term_id );
										foreach ( $recipients as $recipient ) {
											$newsletter->send( $recipient->email );
											$wpdb->query( $wpdb->prepare( "UPDATE $queue_table SET status = %d WHERE queue_id = %d", 1, $recipient->queue_id ) );
											$k++;
										}
									}
								}
							}
							if ( $k == 0 ) {
								if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $queue_table WHERE campaign_id = %d AND status = %d", intval( $this->post->ID ), 1 ) ) !== false ) {
									update_post_meta( $this->post->ID, 'campaign_status', 'executed' );
									do_action( 'groups_newsletters_campaign_status_updated', 'executed', $campaign_status, $this->post->ID );
								}
							}
						}

					} else {

						// initial setup is needed
						$term_ids = wp_get_post_terms( $this->post->ID, 'newsletter', array( 'fields' => 'ids' ) );
						if ( count( $term_ids ) > 0 ) {

							// for all newsletters in this campaign :
							foreach ( $term_ids as $term_id ) {

								// @todo to consider later, storing these ...
// 								$story_ids = get_posts( array(
// 									'fields'      => 'ids',
// 									'numberposts' => -1,
// 									'post_type'   => 'story',
// 									'tax_query'  => array( array(
// 										'taxonomy' => 'newsletter',
// 										'field'    => 'id',
// 										'terms'    => $term_id
// 									) )
// 								) );

								$queue_table = Groups_Newsletters_Controller::get_tablename( 'queue' );

								$subscribers            = Groups_Newsletters_Options::get_option( 'subscribers', array() );
								$newsletter_subscribers = isset( $subscribers[$term_id] ) ? $subscribers[$term_id] : false;

								$emails = array();

								if ( $newsletter_subscribers ) {
									$subscriber_table = Groups_Newsletters_Controller::get_tablename( 'subscriber' );
									// all *activated* subscribers should receive it
									if ( $subscribers = $wpdb->get_results( "SELECT email FROM $subscriber_table WHERE status = 1" ) ) {
										foreach ( $subscribers as $subscriber ) {
											$wpdb->query( $wpdb->prepare(
												"INSERT IGNORE INTO $queue_table SET campaign_id = %d, newsletter_id = %d, email = %s",
												intval( $this->post->ID ),
												intval( $term_id ),
												$subscriber->email
											) );
										}
									}
								}

								if ( GROUPS_NEWSLETTERS_GROUPS_IS_ACTIVE ) {
									$terms                  = Groups_Newsletters_Options::get_option( 'terms', array() );
									$newsletter_groups      = isset( $terms[$term_id] ) ? $terms[$term_id] : array();
									$user_group_table = _groups_get_tablename( 'user_group' );
									foreach ( $newsletter_groups as $group_id ) {
										// all *subscribed* users in this group should receive it
										if ( $group = Groups_Group::read( $group_id ) ) {
											$_users = $wpdb->get_results( $wpdb->prepare( "SELECT user_id FROM $user_group_table WHERE group_id = %d", Groups_Utility::id( $group_id ) ) );
											foreach ( $_users as $_user ) {
												if ( $user = get_user_by( 'id', $_user->user_id ) ) {
													$is_subscriber = get_user_meta( $_user->user_id, 'groups_newsletters_subscriber', true );
													if ( $is_subscriber == 'yes' ) { // we assume opt-in must be explicit
														$wpdb->query( $wpdb->prepare(
															"INSERT IGNORE INTO $queue_table SET campaign_id = %d, newsletter_id = %d, email = %s",
															intval( $this->post->ID ),
															intval( $term_id ),
															$user->user_email
														) );
													}
												}
											}
										}
									}
								}

							}

							add_post_meta( $this->post->ID, 'campaign_queued', 'yes', true );

							global $wpdb;
							$queue_table = Groups_Newsletters_Controller::get_tablename( 'queue' );
							$pending_count = $wpdb->get_var( $wpdb->prepare(
								"SELECT COUNT(*) FROM $queue_table WHERE campaign_id = %d AND status = %d",
								intval( $this->post->ID ),
								0
							) );

							add_post_meta( $this->post->ID, 'campaign_total_emails', intval( $pending_count ) );

						}
					}
				}
			}
		}

		return $n;
	}

}

