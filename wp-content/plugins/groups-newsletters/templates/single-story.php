<?php
/**
 * single-story.php
 *
 * This is the default template for Stories (story custom post type).
 *
 * To modify this template:
 * - Create a groups-newsletters subfolder in your theme's root folder.
 * - Copy this file there and adjust it as desired.
 *
 * @author itthinx
 * @package groups-newsletters
 * @since groups-newsletters 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

echo '<div id="primary" class="site-content story">';
echo '<div id="content">';

while( have_posts() ) {

	the_post();
	get_template_part( 'content', get_post_format() );
	$newsletters = get_the_term_list( get_the_ID(), 'newsletter', '', ', ', '' );
	if ( !empty( $newsletters ) ) {
		echo '<div class="newsletters">';
		echo sprintf( esc_html__( 'Posted in %s', 'groups-newsletters' ) , $newsletters );
		echo '</div>';
	}

	$tags = get_the_term_list( get_the_ID(), 'story_tag', '', ', ', '' );
	if ( !empty( $tags ) ) {
		echo '<div class="tags">';
		echo sprintf( esc_html__( 'Tags %s', 'groups-newsletters' ) , $tags );
		echo '</div>';
	}

	// If you want to link to the previous / next story ...

	// echo '<div class="previous">';
	// previous_post_link( '%link', '<span class="meta-nav">' . esc_html_x( '&larr;', 'Previous post link', 'twentytwelve' ) . '</span> %title' );
	// echo '</div>';

	// echo '<div class="next">';
	// next_post_link( '%link', '%title <span class="meta-nav">' . esc_html_x( '&rarr;', 'Next post link', 'twentytwelve' ) . '</span>' );
	// echo '</div>';

	comments_template( '', true );
}

echo '</div>';
echo '</div>';

get_sidebar();
get_footer();
