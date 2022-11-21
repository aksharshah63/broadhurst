<?php
/**
 * taxonomy-newsletter.php
 *
 * This is the default template for Newsletters (newsletter taxonomy).
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

echo '<div id="primary" class="site-content newsletter">';
echo '<div id="content">';

// newsletter title & description
if ( is_tax() ) {
	global $wp_query;
	if ( $newsletter = $wp_query->get_queried_object() ) {
		if ( $newsletter && !is_wp_error( $newsletter ) ) {
			echo sprintf( '<h1 class="newsletter-title %s">%s</h1>', $newsletter->slug, wp_strip_all_tags( $newsletter->name ) );
			if ( !empty( $newsletter->description ) ) {
				echo '<div class="newsletter-description">';
				echo wp_filter_kses( $newsletter->description );
				echo '</div>';
			}
		}
	}
}

// newsletter stories
while ( have_posts() ) {
	the_post();
	get_template_part( 'content', get_post_format() );
}

// pagination
global $wp_query;
$paginate_links = paginate_links( array(
	'base'    => str_replace( PHP_INT_MAX, '%#%', esc_url( get_pagenum_link( PHP_INT_MAX ) ) ),
	'format'  => '?paged=%#%',
	'current' => max( 1, get_query_var('paged') ),
	'total'   => $wp_query->max_num_pages
) );
if ( strlen( $paginate_links ) > 0 ) {
	echo '<div class="paginate-links">';
	echo $paginate_links;
	echo '</div>';
}

echo '</div>';
echo '</div>';

get_sidebar();
get_footer();
