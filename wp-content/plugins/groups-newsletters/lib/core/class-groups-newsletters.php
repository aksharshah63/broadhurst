<?php
/**
 * class-groups-newsletters.php
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
 * Core newsletter class, handles comments and post/comment rights.
 */
class Groups_Newsletters {

	const DELETE_DATA = 'delete_data';
	const DELETE_DATA_DEFAULT = false;

	/**
	 * Adds actions/filters.
	 */
	public static function init() {

		// Have our say in deciding whether you must be registered to post a
		// comment on a story or not.
		// hooked on apply_filters( 'option_' . $option, maybe_unserialize( $value ) );
		add_filter( 'option_comment_registration', array( __CLASS__, 'option_comment_registration' ) );

		// Decide on comment approval.
		add_filter( 'pre_comment_approved', array( __CLASS__, 'pre_comment_approved' ), 10, 2 );
	}

	/**
	 * Modify the comment_registration option for stories.
	 *
	 * @param bool $value
	 */
	public static function option_comment_registration( $value ) {
		global $post;
		if ( !empty( $post ) ) {
			$post_id = $post->ID;
			if ( 'story' == get_post_type( $post ) ) {
				$comment_registration = Groups_Newsletters_Options::get_option( 'comment-registration', '1' );
				if ( 'default' != $comment_registration ) {
					$value = $comment_registration;
				}
			}
		}
		return $value;
	}

	/**
	 * Decide on comment approval for stories.
	 *
	 * @param int $approved
	 * @param array $commentdata
	 */
	public static function pre_comment_approved( $approved, $commentdata ) {
		$post_id = isset( $commentdata['comment_post_ID'] ) ? $commentdata['comment_post_ID'] : null;
		if ( $post_id ) {

			if ( 'story' == get_post_type( $post_id ) ) {
				$comment_approved     = Groups_Newsletters_Options::get_option( 'default-comment-approved', '0' );
				$comment_status_logic = Groups_Newsletters_Options::get_option( 'default-comment-status-logic', '' );
				switch ( $comment_status_logic ) {
					case '' :
						$approved = intval( $comment_approved );
						break;
					case 'or' :
						$approved = intval( $comment_approved || $approved );
						break;
					case 'and' :
						$approved = intval( $comment_approved && $approved );
						break;
					// if 'default' is used we don't interfere
				}
			}
		}
		return $approved;
	}

}
Groups_Newsletters::init();
