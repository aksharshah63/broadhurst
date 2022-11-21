<?php
/**
 * The header for Astra Theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?><!DOCTYPE html>
<?php astra_html_before(); ?>
<html <?php language_attributes(); ?>>
<head>
<?php astra_head_top(); ?>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">

<?php
$current_page_url = home_url( $_SERVER['REQUEST_URI'] );

if ($current_page_url == "http://localhost/broadhurst/broadhurst/test/"){
    ?>
    <style>
        .ekit-template-content-markup.ekit-template-content-header{
            display: none !important;
        }
    </style>

    <?php
}else{
    ?>
    <style>
        .ekit-template-content-markup.ekit-template-content-header{
            display: block !important;
        }
    </style>

    <?php
}

?>

<?php wp_head(); ?>
<?php astra_head_bottom(); ?>
</head>

<body <?php astra_schema_body(); ?> <?php body_class(); ?>>
<?php astra_body_top(); ?>
<?php wp_body_open(); ?>

<a
	class="skip-link screen-reader-text"
	href="#content"
	role="link"
	title="<?php echo esc_html( astra_default_strings( 'string-header-skip-link', false ) ); ?>">
		<?php echo esc_html( astra_default_strings( 'string-header-skip-link', false ) ); ?>
</a>

<div
<?php
	echo astra_attr(
		'site',
		array(
			'id'    => 'page',
			'class' => 'hfeed site',
		)
	);
	?>
>
	<?php
	astra_header_before();

	astra_header();

	astra_header_after();

	astra_content_before();
	?>
	<div id="content" class="site-content">
		<div class="single_news_image single_news_page_detail">
			<div class="ast-container">
				<?php if(is_single() && 'post' == get_post_type()) :?>
				<div class="breadcrumb"><?php get_breadcrumb(); ?></div>
				<div>
					<?php echo astra_get_post_thumbnail(); ?>
				</div>
				<style>
					.single_news_page_detail {
	background-color:white;
	padding-top: 60px;
}
.single_news_page_detail .ast-container {
  display:block;
}
.single_news_page_detail .breadcrumb {
	padding-bottom:10px;
	background-color:white;
}
.single_news_page_detail .breadcrumb a {
	color:#002F2B;
	font-size:16px;
	font-weight:400;
	opacity:0.5 !important;
	text-decoration: none;
}
.single_news_page_detail .breadcrumb {
	color:#002F2B;
	font-size:16px;
	font-weight:400;
	opacity:0.8;
}

.single_news_page_detail img {
	width:100%;
}
.news-post-detail-main {
	background-color:white;
}
.news-post-detail-main .content-area.primary .ast-breadcrumbs-inner {
	display:none;
}
.news-post-detail-main .content-area.primary .post-thumb-img-content  {
	display:none;
}
.news-post-detail-main .content-area.primary .ast-single-post-order {
	margin-top:0px !important;
}
.news-post-detail-main .content-area.primary article {
	padding: 0px;
	padding-top:60px;
}
.news-post-detail-main {
	margin-top:-125px;
}

@media (max-width: 1024px){
	.single_news_page_detail{
		padding-top:40px;
	}
}
@media (max-width: 921px) {
	.single-news-img {
   display:none;
}
	.single-news-img-mobile {
    display:block;
		width:100%;
		text-align:center;
		margin-top:30px;
}
	.news-post-detail-main {
		margin-top:-25px;
	}
}
@media (max-width: 767px){
/* .elementor-69 .elementor-element.elementor-element-edf9276 .elementor-button {
    width:100%;
}
	.elementor-69 .elementor-element.elementor-element-76b7c8c .elementor-button{
		 width:100%;
	}
	.ekit-wid-con .elementskit-post-body{padding: 20px 0px;
}
	.ekit-wid-con .elementskit-blog-block-post .entry-title{
		margin-bottom:0px;
	}
	.news_post_list  .elementskit-post-body {
    padding: 32px 0px;
}
	.news_post_list .elementskit-entry-header h2 a {
	font-size:24px !important;
}
	.news_post_list .elementskit-entry-header h2 {
    margin-bottom: 10px !important;
}
	.news_post_list.elementskit-blog-block-post .post-meta-list {
    margin-bottom: 10px;
}
	.ekit-wid-con .news_post_list.elementskit-blog-block-post .elementskit-post-footer p:first-child {
		margin-bottom:16px !important;
		margin-right:0px;
	}
	.news_post_list .elementor-button-wrapper .elementor-button {
		padding:0px !important;
	}
	#more_posts {
		margin-top:40px;
		width:100%;
	}
	.news-bottom-left-side-img {
	   width:90px;
		height:90px;
		bottom:1%;
}
.news-bottom-right-side-img {
	    width:124px;
	height:98px;
}
	.news-related-side-sec .news-related-side-heading {
		font-size:32px;
		padding-bottom:25px;
	}
	.news-related-side-sec .news_post_list a img {
    padding-bottom:unset;
    padding-top:8px;
}
	.news-related-side-sec .news_post_list {
    margin-bottom: 0px !important;
}
	.news-related-side-sec .news_post_list .elementskit-entry-header h2 {
  font-size:24px;
	line-height:28.8px;
}
	.single-news-img {
    margin-top:0px;
}
	.news-related-side-sec .news_post_list .post_desc {
	display:block;
}
.news-related-side-sec .news_post_list .readmore {
	display:block;
	padding-bottom:32px;
}
.news-related-side-sec .news_post_list .elementskit-post-body {
		padding-bottom:10px;
	}
	.ast-breadcrumbs-wrapper .breadcrumbs {
		display:none;
	}
	.single-news-img-mobile {
		display:block;
		margin-top:30px;
		text-align:center;
		width: 100%;
	}
	.single-news-img {
		display:none;
	}

	.news-related-side-sec {
		padding-top:30px;
	}
	.widget-area.secondary {
		    margin:0 !important;
	}
	.ast-separate-container #primary {
		padding:0px !important;
	}
	.news-post-detail-main {
		margin-top:-25px;
	} */
	.single_news_page_detail .breadcrumb {
		display:none;
	}
	.single_news_page_detail{
		padding-top:40px;
	}
	.news-post-detail-main .content-area.primary article {
    padding-top: 40px;
}
.single_news_page_detail .ast-container {
	padding-left:20px !important;
	padding-right:20px !important;
}
.news-post-detail-main .ast-container {
	padding-left:20px !important;
	padding-right:20px !important;
}
}
				</style>
				<?php endif; ?>
			</div>
			
			
		</div>
		<div class="news-post-detail-main">
		<div class="ast-container">
		<?php astra_content_top(); ?>
