<?php
/**
 * Astra functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define Constants
 */
define( 'ASTRA_THEME_VERSION', '3.7.9' );
define( 'ASTRA_THEME_SETTINGS', 'astra-settings' );
define( 'ASTRA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'ASTRA_THEME_URI', trailingslashit( esc_url( get_template_directory_uri() ) ) );


/**
 * Minimum Version requirement of the Astra Pro addon.
 * This constant will be used to display the notice asking user to update the Astra addon to the version defined below.
 */
define( 'ASTRA_EXT_MIN_VER', '3.6.3' );

/**
 * Setup helper functions of Astra.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-theme-options.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-theme-strings.php';
require_once ASTRA_THEME_DIR . 'inc/core/common-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-icons.php';

/**
 * Update theme
 */
require_once ASTRA_THEME_DIR . 'inc/theme-update/class-astra-theme-update.php';
require_once ASTRA_THEME_DIR . 'inc/theme-update/astra-update-functions.php';
require_once ASTRA_THEME_DIR . 'inc/theme-update/class-astra-theme-background-updater.php';
require_once ASTRA_THEME_DIR . 'inc/theme-update/class-astra-pb-compatibility.php';


/**
 * Fonts Files
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-font-families.php';
if ( is_admin() ) {
	require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts-data.php';
}

require_once ASTRA_THEME_DIR . 'inc/lib/webfont/class-astra-webfont-loader.php';
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts.php';

require_once ASTRA_THEME_DIR . 'inc/dynamic-css/custom-menu-old-header.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/container-layouts.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/astra-icons.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/block-editor-compatibility.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-walker-page.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-enqueue-scripts.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-gutenberg-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/inline-on-mobile.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/content-background.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-dynamic-css.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-global-palette.php';

/**
 * Custom template tags for this theme.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-attr.php';
require_once ASTRA_THEME_DIR . 'inc/template-tags.php';

require_once ASTRA_THEME_DIR . 'inc/widgets.php';
require_once ASTRA_THEME_DIR . 'inc/core/theme-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/admin-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/sidebar-manager.php';

/**
 * Markup Functions
 */
require_once ASTRA_THEME_DIR . 'inc/markup-extras.php';
require_once ASTRA_THEME_DIR . 'inc/extras.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog-config.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog.php';
require_once ASTRA_THEME_DIR . 'inc/blog/single-blog.php';

/**
 * Markup Files
 */
require_once ASTRA_THEME_DIR . 'inc/template-parts.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-loop.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-mobile-header.php';

/**
 * Functions and definitions.
 */
require_once ASTRA_THEME_DIR . 'inc/class-astra-after-setup-theme.php';

// Required files.
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-helper.php';

require_once ASTRA_THEME_DIR . 'inc/schema/class-astra-schema.php';

if ( is_admin() ) {

	/**
	 * Admin Menu Settings
	 */
	require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-settings.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/astra-notices/class-astra-notices.php';

}

/**
 * Metabox additions.
 */
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-boxes.php';

require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-box-operations.php';

/**
 * Customizer additions.
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-customizer.php';

/**
 * Astra Modules.
 */
require_once ASTRA_THEME_DIR . 'inc/modules/related-posts/class-astra-related-posts.php';

/**
 * Compatibility
 */
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gutenberg.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-jetpack.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/woocommerce/class-astra-woocommerce.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/edd/class-astra-edd.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/lifterlms/class-astra-lifterlms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/learndash/class-astra-learndash.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bb-ultimate-addon.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-contact-form-7.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-visual-composer.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-site-origin.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gravity-forms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bne-flyout.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-ubermeu.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-divi-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-amp.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-yoast-seo.php';
require_once ASTRA_THEME_DIR . 'inc/addons/transparent-header/class-astra-ext-transparent-header.php';
require_once ASTRA_THEME_DIR . 'inc/addons/breadcrumbs/class-astra-breadcrumbs.php';
require_once ASTRA_THEME_DIR . 'inc/addons/heading-colors/class-astra-heading-colors.php';
require_once ASTRA_THEME_DIR . 'inc/builder/class-astra-builder-loader.php';

// Elementor Compatibility requires PHP 5.4 for namespaces.
if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor-pro.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-web-stories.php';
}

// Beaver Themer compatibility requires PHP 5.3 for anonymus functions.
if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-themer.php';
}

require_once ASTRA_THEME_DIR . 'inc/core/markup/class-astra-markup.php';

/**
 * Load deprecated functions
 */
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-filters.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-functions.php';

function wpc_elementor_shortcode( $atts ) {
	$events = tribe_get_events();
	$test = array_reverse($events);
	$test2= array_slice($test, 0, 3);
?>
	<div class="elementor-container elementor-column-gap-default">
		<?php foreach ($test2 as $event) { ?>
		<div class="elementor-column elementor-col-33 elementor-top-column elementor-element elementor-element-b04a1e0" data-id="b04a1e0" data-element_type="column">
    <div class="elementor-widget-wrap elementor-element-populated">
        <div class="elementor-element elementor-element-ecc7578 elementor-widget elementor-widget-text-editor" data-id="ecc7578" data-element_type="widget" data-widget_type="text-editor.default">
            <div class="elementor-widget-container"><?php echo tribe_get_start_date( $event, false, 'j' ); ?> <span style="font-size: 16px;"><?php echo tribe_get_start_date( $event, false, 'F' ); ?></span></div>
        </div>
        <div
            class="elementor-element elementor-element-5cbfea8 elementor-hidden-tablet elementor-widget-divider--view-line elementor-widget elementor-widget-divider"
            data-id="5cbfea8"
            data-element_type="widget"
            data-widget_type="divider.default"
        >
            <div class="elementor-widget-container">
                <div class="elementor-divider">
                    <span class="elementor-divider-separator"></span>
                </div>
            </div>
        </div>
        <div class="elementor-element elementor-element-faa1e6f elementor-widget elementor-widget-heading" data-id="faa1e6f" data-element_type="widget" data-widget_type="heading.default">
            <div class="elementor-widget-container">
                <h2 class="elementor-heading-title elementor-size-default"><?php echo get_the_title( $event );?></h2>
            </div>
        </div>
        <div class="elementor-element elementor-element-2f1503e fontnew elementor-widget elementor-widget-text-editor" data-id="2f1503e" data-element_type="widget" data-widget_type="text-editor.default">
            <div class="elementor-widget-container">
                <?php echo get_the_excerpt($event->ID); ?>
            </div>
        </div>
        <div class="elementor-element elementor-element-4710e04 elementor-align-left elementor-widget elementor-widget-button" data-id="4710e04" data-element_type="widget" data-widget_type="button.default">
            <div class="elementor-widget-container">
                <div class="elementor-button-wrapper">
                    <a href="<?php echo the_permalink($event->ID); ?>" class="elementor-button-link elementor-button elementor-size-sm" role="button">
                        <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-icon elementor-align-icon-right"> <i aria-hidden="true" class="icon icon-right-arrow"></i> </span>
                            <span class="elementor-button-text">Read More</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }?>
	</div>
<?php
}
	
add_shortcode( 'my_elementor_php_output', 'wpc_elementor_shortcode');

function wpb_postsbycategory() {
// the query
$the_query = new WP_Query( array( 
    'category_name' => 'general', 
    'posts_per_page' => 3 
) ); 
   
// The Loop
if ( $the_query->have_posts() ) { ?>
    <div class="elementor-column elementor-top-column elementor-element elementor-element-1f91d2e" data-id="1f91d2e" data-element_type="column">
    <div class="elementor-widget-wrap elementor-element-populated">
    <?php while ( $the_query->have_posts() ) {
        $the_query->the_post(); ?>
         
        <div class="elementor-element elementor-element-0c72468 elementor-widget-divider--view-line elementor-widget elementor-widget-divider" data-id="0c72468" data-element_type="widget" data-widget_type="divider.default">
            <div class="elementor-widget-container">
                <div class="elementor-divider">
                    <span class="elementor-divider-separator"> </span>
                </div>
            </div>
        </div>
        <div class="elementor-element elementor-element-e9a2973 elementor-widget elementor-widget-heading" data-id="e9a2973" data-element_type="widget" data-widget_type="heading.default">
            <div class="elementor-widget-container">
                <h2 class="elementor-heading-title elementor-size-default"><?php echo get_the_title();?></h2>
            </div>
        </div>
        <div class="elementor-element elementor-element-1914272 elementor-widget elementor-widget-text-editor" data-id="1914272" data-element_type="widget" data-widget_type="text-editor.default">
            <div class="elementor-widget-container">
                <?php the_author(); ?> | <?php echo get_the_date(); ?>
            </div>
        </div>
        <div class="elementor-element elementor-element-2a97e92 fontnew elementor-widget elementor-widget-text-editor" data-id="2a97e92" data-element_type="widget" data-widget_type="text-editor.default">
            <div class="elementor-widget-container">
                <?php echo get_the_excerpt(); ?>
            </div>
        </div>
        <div class="elementor-element elementor-element-0eb21c3 elementor-align-left elementor-widget elementor-widget-button" data-id="0eb21c3" data-element_type="widget" data-widget_type="button.default">
            <div class="elementor-widget-container">
                <div class="elementor-button-wrapper">
                    <a href="<?php echo the_permalink(); ?>" class="elementor-button-link elementor-button elementor-size-sm" role="button">
                        <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-icon elementor-align-icon-right"> <i aria-hidden="true" class="icon icon-right-arrow"></i> </span>
                            <span class="elementor-button-text">Read More</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
<!--         <div class="elementor-element elementor-element-76b7c8c elementor-hidden-desktop elementor-hidden-tablet elementor-widget elementor-widget-button" data-id="76b7c8c" data-element_type="widget" data-widget_type="button.default">
            <div class="elementor-widget-container">
                <div class="elementor-button-wrapper">
                    <a href="https://broadhursthome.org/news/" class="elementor-button-link elementor-button elementor-size-sm" role="button">
                        <span class="elementor-button-content-wrapper">
                            <span class="elementor-button-text">See All News</span>
                        </span>
                    </a>
                </div>
            </div>
        </div> -->
    

		<?php
            }
		?>
</div>
</div>
<?php
    }
/* Restore original Post Data */
wp_reset_postdata();
}
// Add a shortcode
add_shortcode('categoryposts', 'wpb_postsbycategory');


function load_more_news_post()
{
?>
<div class="elementor-element elementor-element-585d031 elementor-widget elementor-widget-elementskit-blog-posts" data-id="585d031" data-element_type="widget" data-widget_type="elementskit-blog-posts.default">
    <div class="elementor-widget-container">
        <div class="ekit-wid-con">
            <div class="row post-items" id="ajax-posts">
                <?php
                    $postsPerPage = 3;
                    $args = array(
                        'category_name' =>'general', 
                        'posts_per_page' => $postsPerPage 
                    ); 
                    $loop = new WP_Query($args); 
                    while ($loop->have_posts()) : $loop->the_post();
                ?>

                <div class="col-md-12">
                    <div class="elementskit-blog-block-post news_post_list">
                        <div class="row no-gutters">
                            <div class="col-md-6 order-1">
                                <a href="<?php echo the_permalink(); ?>" class="elementskit-entry-thumb">
                                    <?php echo get_the_post_thumbnail(); ?>
                                </a>
                                <!-- .elementskit-entry-thumb END -->
                            </div>

                            <div class="col-md-6 order-2">
                                <div class="elementskit-post-body">
                                    <div class="elementskit-entry-header">
                                        <h2 class="entry-title">
                                            <a href="<?php echo the_permalink(); ?>"> <?php echo get_the_title();?> </a>
                                        </h2>

                                        <div class="post-meta-list">
                                            <span class="meta-author">
                                                <a href="https://broadhursthome.org/author/broadhurst_admin/" class="author-name" style="margin-right: 5px;"><?php the_author(); ?></a> | <span class="meta-date-text" style="margin-left: 5px;"><?php echo get_the_date(); ?> </span>
                                            </span>
<!--                                             <span class="meta-date">
                                                <span class="meta-date-text"><?php echo get_the_date(); ?> </span>
                                            </span> -->
                                        </div>
                                    </div>
                                    <!-- .elementskit-entry-header END -->

                                    <div class="elementskit-post-footer">
                                        <p>
                                            <?php echo get_the_excerpt(); ?>
                                        </p>
                                    </div>
                                    <div
                                        class="read_more_btn elementor-element elementor-element-0eb21c3 elementor-align-left elementor-widget elementor-widget-button"
                                        data-id="0eb21c3"
                                        data-element_type="widget"
                                        data-widget_type="button.default"
                                    >
                                        <div class="elementor-widget-container">
                                            <div class="elementor-button-wrapper">
                                                <a href="<?php echo the_permalink(); ?>" class="elementor-button-link elementor-button elementor-size-sm" role="button">
                                                    <span class="elementor-button-content-wrapper">
                                                        <span class="elementor-button-icon elementor-align-icon-right" style="margin-left: 15px;"> <i aria-hidden="true" class="icon icon-right-arrow"></i> </span>
                                                        <span class="elementor-button-text">Read More</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- .elementskit-post-footer END -->
                                </div>
                                <!-- .elementskit-post-body END -->
                            </div>
                        </div>
                    </div>
                    <!-- .elementskit-blog-block-post .radius .gradient-bg END -->
					<div class="elementor-element elementor-element-0c72468 elementor-widget-divider--view-line elementor-widget elementor-widget-divider" data-id="0c72468" data-element_type="widget" data-widget_type="divider.default">
            <div class="elementor-widget-container">
                <div class="elementor-divider">
                    <span class="elementor-divider-separator news_divider"> </span>
                </div>
            </div>
        </div>
                </div>
				

                <?php
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        </div>
    </div>
</div>
<!-- <div class="news-bottom-img-sec">
	<img src="https://broadhursthome.org/wp-content/uploads/2022/11/traced-img2.png" class="news-bottom-left-side-img">
	<img src="https://broadhursthome.org/wp-content/uploads/2022/11/traced-img1.png" class="news-bottom-right-side-img">
</div>	 -->
<!-- </div> -->
<div class="load-more-bottom-btn"></div>
<div id="more_posts" p="2">Load More</div>
<div class="news-bottom-img-sec">
	<img src="https://broadhursthome.org/wp-content/uploads/2022/11/traced-img2.png" class="news-bottom-left-side-img">
	<img src="https://broadhursthome.org/wp-content/uploads/2022/11/traced-img1.png" class="news-bottom-right-side-img">
</div>	
<?php
}

add_shortcode('more_news_post', 'load_more_news_post');

function more_post_ajax(){
    $count_ = wp_count_posts();
	$count = $count_->publish;
	$ppp = (isset($_POST["ppp"])) ? $_POST["ppp"] : 3;
    $page = (isset($_POST['pageNumber'])) ? $_POST['pageNumber'] : 0;

    $page_ = sanitize_text_field($_POST['pageNumber']);
    $cur_page = $page_;
    $page_ -= 1;
    $previous_btn = true;
    $next_btn = true;
    $first_btn = true;
    $last_btn = true;

    header("Content-Type: text/html");

    $args = array(
        'suppress_filters' => true,
        'posts_per_page' => $ppp,
        'paged'    => $page,
		'category_name' => 'general', 
    );

    $loop = new WP_Query($args);

    $out = '';

    if ($loop -> have_posts()) :  while ($loop -> have_posts()) : $loop -> the_post(); ?>
<div class="col-md-12">
    <div class="elementskit-blog-block-post news_post_list">
        <div class="row no-gutters">
            <div class="col-md-6 order-1">
                <a href="<?php echo the_permalink(); ?>" class="elementskit-entry-thumb">
                    <?php echo get_the_post_thumbnail(); ?>
                </a>
                <!-- .elementskit-entry-thumb END -->
            </div>

            <div class="col-md-6 order-2">
                <div class="elementskit-post-body">
                    <div class="elementskit-entry-header">
                        <h2 class="entry-title">
                            <a href="<?php echo the_permalink(); ?>"> <?php echo get_the_title();?> </a>
                        </h2>

                        <div class="post-meta-list">
                            <span class="meta-author">
                                <a href="https://broadhursthome.org/author/broadhurst_admin/" class="author-name"
                                    style="margin-right: 5px;"><?php the_author(); ?></a> | <span class="meta-date-text"
                                    style="margin-left: 5px;"><?php echo get_the_date(); ?> </span>
                            </span>
                            <!--                                             <span class="meta-date">
                                                <span class="meta-date-text"><?php echo get_the_date(); ?> </span>
                                            </span> -->
                        </div>
                    </div>
                    <!-- .elementskit-entry-header END -->

                    <div class="elementskit-post-footer">
                        <p>
                            <?php echo get_the_excerpt(); ?>
                        </p>
                    </div>
                    <div class="read_more_btn elementor-element elementor-element-0eb21c3 elementor-align-left elementor-widget elementor-widget-button"
                        data-id="0eb21c3" data-element_type="widget" data-widget_type="button.default">
                        <div class="elementor-widget-container">
                            <div class="elementor-button-wrapper">
                                <a href="<?php echo the_permalink(); ?>"
                                    class="elementor-button-link elementor-button elementor-size-sm" role="button">
                                    <span class="elementor-button-content-wrapper">
                                        <span class="elementor-button-icon elementor-align-icon-right"
                                            style="margin-left: 15px;"> <i aria-hidden="true"
                                                class="icon icon-right-arrow"></i> </span>
                                        <span class="elementor-button-text">Read More</span>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- .elementskit-post-footer END -->
                </div>
                <!-- .elementskit-post-body END -->
            </div>
        </div>
    </div>
    <!-- .elementskit-blog-block-post .radius .gradient-bg END -->
    <div class="elementor-element elementor-element-0c72468 elementor-widget-divider--view-line elementor-widget elementor-widget-divider"
        data-id="0c72468" data-element_type="widget" data-widget_type="divider.default">
        <div class="elementor-widget-container">
            <div class="elementor-divider">
                <span class="elementor-divider-separator news_divider"> </span>
            </div>
        </div>
    </div>
</div>

<?php
    endwhile;
    $no_of_paginations = ceil($count / $ppp);
         // Pagination Buttons logic     
         if ($last_btn && $cur_page < $no_of_paginations) {
            if ($next_btn && $cur_page < $no_of_paginations) {
               $nex = $cur_page + 1;
            ?>
<div p='<?php echo $nex; ?>' id="more_posts">Load More </div>
<?php
            }
         } else if ($last_btn) {
         }
   endif;
    wp_reset_postdata();
    die();
}

add_action('wp_ajax_nopriv_more_post_ajax', 'more_post_ajax');
add_action('wp_ajax_more_post_ajax', 'more_post_ajax');


function recent_post()
{
?>
<div class="elementor-element elementor-element-585d031 elementor-widget elementor-widget-elementskit-blog-posts news-related-side-sec" data-id="585d031" data-element_type="widget" data-widget_type="elementskit-blog-posts.default">
    <div class="elementor-widget-container">
        <div class="ekit-wid-con">
            <div class="row post-items">
				<div class="col-md-12">
				<h2 class="news-related-side-heading">
									Related Posts
								</h2>
				</div>
                <?php
                    $postsPerPage = 5;
                    $args = array(
                        'category_name' =>'general', 
                        'posts_per_page' => $postsPerPage 
                    ); 
                    $loop = new WP_Query($args); 
                    while ($loop->have_posts()) : $loop->the_post();
                ?>

                <div class="col-md-12">
                    <div class="news_post_list">
                        <div class="row no-gutters">
                            <div class="col-md-12 order-1">
								
                                <a href="<?php echo the_permalink(); ?>" class="elementskit-entry-thumb">
                                    <?php echo get_the_post_thumbnail(); ?>
                                </a>
                                <!-- .elementskit-entry-thumb END -->
                            </div>

                            <div class="col-md-12 order-2">
                                <div class="elementskit-post-body">
                                    <div class="elementskit-entry-header">
                                        <h2 class="entry-title">
                                            <a href="<?php echo the_permalink(); ?>"> <?php echo get_the_title();?> </a>
                                        </h2>

                                        <div class="post-meta-list">
                                            <span class="meta-author">
                                                <a href="https://broadhursthome.org/author/broadhurst_admin/" class="author-name" style="margin-right: 5px;"><?php the_author(); ?></a> | <span class="meta-date-text" style="margin-left: 5px;"><?php echo get_the_date(); ?> </span>
                                            </span>
<!--                                             <span class="meta-date">
                                                <span class="meta-date-text"><?php echo get_the_date(); ?> </span>
                                            </span> -->
                                        </div>
                                    </div>
                                    <!-- .elementskit-entry-header END -->
                                    <!-- .elementskit-post-footer END -->
                                </div>
								<div class="elementskit-post-footer post_desc">
                                        <p>
                                            <?php echo get_the_excerpt(); ?>
                                        </p>
                                    </div>
                                    <div
                                        class="read_more_btn elementor-element elementor-element-0eb21c3 elementor-align-left elementor-widget elementor-widget-button"
                                        data-id="0eb21c3"
                                        data-element_type="widget"
                                        data-widget_type="button.default"
                                    >
                                        <div class="elementor-widget-container readmore">
                                            <div class="elementor-button-wrapper">
                                                <a href="<?php echo the_permalink(); ?>" class="elementor-button-link elementor-button elementor-size-sm" role="button">
                                                    <span class="elementor-button-content-wrapper">
                                                        <span class="elementor-button-icon elementor-align-icon-right" style="margin-left: 15px;"> <i aria-hidden="true" class="icon icon-right-arrow"></i> </span>
                                                        <span class="elementor-button-text">Read More</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <!-- .elementskit-post-body END -->
                            </div>
                        </div>
                    </div>
                    <!-- .elementskit-blog-block-post .radius .gradient-bg END -->
					<div class="elementor-element elementor-element-0c72468 elementor-widget-divider--view-line elementor-widget elementor-widget-divider" data-id="0c72468" data-element_type="widget" data-widget_type="divider.default">
            <div class="elementor-widget-container">
                <div class="elementor-divider">
                    <span class="elementor-divider-separator news_divider"> </span>
                </div>
            </div>
        </div>
                </div>
				

                <?php
                endwhile;
                wp_reset_postdata();
                ?>
<!-- 				<div class="single-news-img-mobile">
			<img src="https://broadhursthome.org/wp-content/uploads/2022/11/image-32-Traced.png">
		</div> -->
				<div class="single-news-img-mobile">
<!-- 	<img src="https://broadhursthome.org/wp-content/uploads/2022/11/traced-img2.png" class="news-bottom-left-side-img"> -->
	<img src="https://broadhursthome.org/wp-content/uploads/2022/11/traced-img1.png">
</div>	
            </div>
        </div>
    </div>
</div>
</div>
<?php
}

add_shortcode('sidebar_recent_post', 'recent_post');

function get_breadcrumb() {
    echo '<a href="'.home_url().'" rel="nofollow">Home</a>';
    if (is_category() || is_single()) {
        echo "&nbsp;&nbsp;>&nbsp;&nbsp;";
        the_category(' &bull; ');
            if (is_single()) {
                echo " &nbsp;&nbsp;>&nbsp;&nbsp; ";
                the_title();
            }
    } elseif (is_page()) {
        echo "&nbsp;&nbsp;>&nbsp;&nbsp;";
        echo the_title();
    } elseif (is_search()) {
        echo "&nbsp;&nbsp;>&nbsp;&nbsp;Search Results for... ";
        echo '"<em>';
        echo the_search_query();
        echo '</em>"';
    }
}

