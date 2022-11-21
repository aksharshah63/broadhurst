<?php
/**
 * class-groups-newsletters-story.php
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
 * Story abstraction.
 */
class Groups_Newsletters_Story {

	/**
	 * Post
	 *
	 * @var object
	 */
	public $post = null;

	/**
	 * Constructor, loads story post by id.
	 *
	 * @param int $post_id of a story
	 */
	public function __construct( $post ) {
		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}
		if ( isset( $post->post_type ) && ( $post->post_type == 'story' ) ) {
			$this->post = $post;
		}
	}

	/**
	 * Returns the story title.
	 *
	 * @return string title
	 */
	public function get_title() {
		$title = '';
		if ( $this->post !== null ) {
			$title = get_the_title( $this->post->ID );
		}
		return $title;
	}

	/**
	 * Returns the rendered story content.
	 *
	 * Allowed options:
	 * - more_link : show a read more link, defaults to true
	 * - more_link_text : the read more link text, default to 'Read more ...'
	 * -
	 *
	 * @param array $options
	 * @return string story content
	 */
	public function get_content( $options = array() ) {
		$content = '';
		if ( $this->post !== null ) {
			$_options = array(
				'more_link' => true,
				'more_link_text' => __( 'Read more ...', 'groups-newsletters' )
			);
			foreach ( $_options as $key => $value ) {
				if ( isset( $options[$key] ) ) {
					$_options[$key] = $options[$key];
				}
			}
			$options = $_options;

			$matches = array();
			$content = $this->post->post_content;
			if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
				if ( is_array( $matches ) ) {
					$parts = explode( $matches[0], $content, 2 );
					$content = $parts[0];
					if ( $options['more_link'] ) {
						$content .= apply_filters(
							'the_content_more_link',
							sprintf(
								'<a href="%s" class="more-link">%s</a>',
								get_permalink( $this->post->ID ),
								strip_tags( wp_kses_no_null( trim( $options['more_link_text'] ) ) )
							)
						);
					}
					$content = force_balance_tags( $content );
				}
			}
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
		}
		return $content;
	}

}
