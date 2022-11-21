<?php
/**
 * class-groups-newsletters-templates.php
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
 * Template handler.
 */
class Groups_Newsletters_Templates {

	/**
	 * Adds the template filter.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
	}

	/**
	 * Filter that decides which template to load.
	 * @param string $template
	 * @return string template
	 */
	public static function template_include( $template ) {
		$new_template   = '';
		$template_names = array();
		$template_base  = apply_filters( 'groups_newsletters_template_base', 'groups-newsletters' );
		if ( is_single() && get_post_type() == 'story' ) {
			$new_template     = 'single-story.php';
			$template_names[] = $new_template;
		} elseif ( is_tax( 'newsletter' ) || is_tax( 'story_tags' ) ) {
			$term = get_queried_object();
			$new_template     = 'taxonomy-' . $term->taxonomy . '.php';
			$template_names[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$template_names[] = $template_base . '/taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$template_names[] = $new_template;
		} elseif ( is_post_type_archive( 'story' ) ) {
			$new_template     = 'archive-story.php';
			$template_names[] = $new_template;
		}
		if ( $new_template ) {
			$template_names[] = $template_base . '/' . $new_template;
			if ( $maybe_template = locate_template( $template_names ) ) {
				$template = $maybe_template;
			} else {
				if ( file_exists( GROUPS_NEWSLETTERS_TEMPLATES . '/' . $new_template ) ) {
					$template = GROUPS_NEWSLETTERS_TEMPLATES . '/' . $new_template;
				}
			}
		}
		return $template;
	}
}
Groups_Newsletters_Templates::init();
